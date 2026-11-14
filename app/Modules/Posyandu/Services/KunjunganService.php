<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\KegiatanPosyandu;
use App\Modules\Posyandu\Models\Kunjungan;
use App\Modules\Posyandu\Models\KunjunganPos;
use App\Modules\Posyandu\Models\KelompokSasaran;
use App\Modules\Posyandu\Models\PosyanduPos;
use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Models\Warga;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KunjunganService
{
    public function __construct(
        protected PetugasContextService $petugasContext
    ) {}

    /**
     * Mendaftarkan warga ke kegiatan Posyandu
     * sekaligus membentuk antrean pemeriksaan.
     */
    public function daftarWarga(
        int $kegiatanId,
        int $wargaId,
        User $user
    ): Kunjungan {
        return DB::connection('posyandu')->transaction(function () use (
            $kegiatanId,
            $wargaId,
            $user
        ) {
            // Ambil kegiatan dan kunci selama proses pendaftaran.
            $kegiatan = KegiatanPosyandu::query()
                ->whereKey($kegiatanId)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan petugas bertugas pada Posyandu kegiatan.
            $this->petugasContext->petugasAssignment(
                $user,
                (int) $kegiatan->posyandu_id
            );

            // Pastikan kegiatan sudah dipublikasikan.
            if ($kegiatan->status !== 'published') {
                throw ValidationException::withMessages([
                    'kegiatan_id' => 'Pendaftaran hanya dapat dilakukan pada kegiatan yang sudah dipublikasikan.',
                ]);
            }

            // Ambil data warga.
            $warga = Warga::query()
                ->whereKey($wargaId)
                ->firstOrFail();

            // Pastikan warga terdaftar pada Posyandu kegiatan.
            if ((int) $warga->posyandu_id !== (int) $kegiatan->posyandu_id) {
                throw ValidationException::withMessages([
                    'warga_id' => 'Warga bukan anggota Posyandu pada kegiatan ini.',
                ]);
            }

            // Cegah pendaftaran ganda.
            $sudahTerdaftar = Kunjungan::query()
                ->where('kegiatan_id', $kegiatanId)
                ->where('warga_id', $wargaId)
                ->exists();

            if ($sudahTerdaftar) {
                throw ValidationException::withMessages([
                    'warga_id' => 'Warga sudah terdaftar pada kegiatan ini.',
                ]);
            }

            // Tentukan nomor antrean berikutnya.
            $nomorAntrian = (int) Kunjungan::query()
                ->where('kegiatan_id', $kegiatanId)
                ->max('nomor_antrian') + 1;

            // Hitung usia warga pada tanggal kegiatan.
            $tanggalKegiatan = Carbon::parse($kegiatan->tanggal_kegiatan);
            $tanggalLahir = Carbon::parse($warga->tanggal_lahir);

            if ($tanggalLahir->greaterThan($tanggalKegiatan)) {
                throw ValidationException::withMessages([
                    'warga_id' => 'Tanggal lahir warga tidak valid.',
                ]);
            }

            $usiaBulan = (int) $tanggalLahir->diffInMonths($tanggalKegiatan);

            // Tentukan kelompok sasaran berdasarkan usia.
            $kelompokUsia = KelompokSasaran::query()
                ->where('status', true)
                ->where('usia_min_bulan', '<=', $usiaBulan)
                ->where(function ($query) use ($usiaBulan) {
                    $query->whereNull('usia_max_bulan')
                        ->orWhere('usia_max_bulan', '>=', $usiaBulan);
                })
                ->first();

            if (!$kelompokUsia) {
                throw ValidationException::withMessages([
                    'warga_id' => 'Kelompok usia warga belum tersedia.',
                ]);
            }

            // Ambil master POS berdasarkan jenis pemeriksaan
            // yang berlaku untuk kelompok usia warga.
            $posPemeriksaanIds = DB::connection('posyandu')
                ->table('jenis_pemeriksaan')
                ->join(
                    'jenis_pemeriksaan_kelompok',
                    'jenis_pemeriksaan.id',
                    '=',
                    'jenis_pemeriksaan_kelompok.jenis_pemeriksaan_id'
                )
                ->join(
                    'pos_pemeriksaan',
                    'pos_pemeriksaan.id',
                    '=',
                    'jenis_pemeriksaan.pos_pemeriksaan_id'
                )
                ->where('jenis_pemeriksaan.status', true)
                ->where('pos_pemeriksaan.status', true)
                ->where(
                    'jenis_pemeriksaan_kelompok.kelompok_sasaran_id',
                    $kelompokUsia->id
                )
                ->distinct()
                ->pluck('pos_pemeriksaan.id')
                ->map(fn($id) => (int) $id)
                ->all();

            // POS-04 (Pencatatan dan Keluhan) berlaku
            // untuk semua kelompok usia.
            $posKeluhanId = DB::connection('posyandu')
                ->table('pos_pemeriksaan')
                ->where('kode', 'POS-04')
                ->where('status', true)
                ->value('id');

            if ($posKeluhanId) {
                $posPemeriksaanIds[] = (int) $posKeluhanId;
            }

            $posPemeriksaanIds = array_values(
                array_unique($posPemeriksaanIds)
            );

            // Ambil konfigurasi POS aktif milik Posyandu kegiatan.
            // Urutan mengikuti konfigurasi masing-masing Posyandu.
            $daftarPos = PosyanduPos::query()
                ->where('posyandu_id', $kegiatan->posyandu_id)
                ->where('status', true)
                ->whereIn('pos_pemeriksaan_id', $posPemeriksaanIds)
                ->orderBy('urutan')
                ->get();

            // Pastikan tersedia POS pemeriksaan untuk kunjungan.
            if ($daftarPos->isEmpty()) {
                throw ValidationException::withMessages([
                    'posyandu_pos' => 'Belum ada pos pemeriksaan aktif yang sesuai dengan kelompok usia warga.',
                ]);
            }

            // Buat data kunjungan.
            $kunjungan = Kunjungan::create([
                'kegiatan_id' => $kegiatanId,
                'warga_id' => $wargaId,
                'nomor_antrian' => $nomorAntrian,
                'status' => 'berlangsung',
                'created_by' => $user->id,
            ]);

            // Buat antrean pada setiap POS yang berlaku.
            foreach ($daftarPos as $pos) {
                KunjunganPos::create([
                    'kunjungan_id' => $kunjungan->id,
                    'posyandu_pos_id' => $pos->id,
                    'status' => 'waiting',
                ]);
            }

            return $kunjungan->load([
                'warga',
                'kunjunganPos.posyanduPos.posPemeriksaan',
            ]);
        });
    }
}
