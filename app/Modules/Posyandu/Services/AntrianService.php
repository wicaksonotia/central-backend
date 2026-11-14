<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Models\KegiatanPosyandu;
use App\Modules\Posyandu\Models\Kunjungan;
use App\Modules\Posyandu\Models\PosyanduPos;
use App\Modules\Posyandu\Models\KunjunganPos;
use App\Modules\Posyandu\Models\Pemeriksaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AntrianService
{
    public function __construct(
        protected PetugasContextService $petugasContext
    ) {}

    /**
     * Menampilkan antrean berdasarkan kegiatan dan pos.
     */
    public function daftar(
        User $user,
        int $kegiatanId,
        int $posyanduPosId
    ) {
        $this->validasiAkses(
            $user,
            $kegiatanId,
            $posyanduPosId
        );

        return KunjunganPos::query()
            ->with([
                'kunjungan.warga',
                'posyanduPos.posPemeriksaan',
            ])
            ->where('posyandu_pos_id', $posyanduPosId)
            ->whereHas('kunjungan', function ($query) use ($kegiatanId) {
                $query->where('kegiatan_id', $kegiatanId);
            })
            ->orderByRaw("
                CASE status
                    WHEN 'in_progress' THEN 1
                    WHEN 'waiting' THEN 2
                    WHEN 'skipped' THEN 3
                    WHEN 'completed' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('urutan_panggil')
            ->orderBy('id')
            ->get();
    }

    /**
     * Memanggil warga.
     */
    public function panggil(
        User $user,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): KunjunganPos {
        return DB::connection('posyandu')->transaction(function () use (
            $user,
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        ) {
            $this->validasiAkses(
                $user,
                $kegiatanId,
                $posyanduPosId
            );

            $pos = PosyanduPos::query()
                ->whereKey($posyanduPosId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$pos->status) {
                throw ValidationException::withMessages([
                    'posyandu_pos_id' => 'Pos pemeriksaan sudah tidak aktif.',
                ]);
            }

            $antrian = $this->ambilAntrian(
                $kegiatanId,
                $posyanduPosId,
                $kunjunganPosId,
                true
            );

            if ($antrian->status !== 'waiting') {
                throw ValidationException::withMessages([
                    'antrian' => 'Hanya antrean yang sedang menunggu yang dapat dipanggil.',
                ]);
            }

            $urutanBerikutnya = (int) KunjunganPos::query()
                ->where('posyandu_pos_id', $posyanduPosId)
                ->whereHas('kunjungan', function ($query) use ($kegiatanId) {
                    $query->where('kegiatan_id', $kegiatanId);
                })
                ->max('urutan_panggil') + 1;

            $antrian->update([
                'status' => 'in_progress',
                'urutan_panggil' => $urutanBerikutnya,
                'waktu_mulai' => now(),
            ]);

            return $antrian->fresh([
                'kunjungan.warga',
                'posyanduPos.posPemeriksaan',
            ]);
        });
    }

    /**
     * Melewati antrean.
     */
    public function lewati(
        User $user,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): KunjunganPos {
        return DB::connection('posyandu')->transaction(function () use (
            $user,
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        ) {
            $this->validasiAkses(
                $user,
                $kegiatanId,
                $posyanduPosId
            );

            $antrian = $this->ambilAntrian(
                $kegiatanId,
                $posyanduPosId,
                $kunjunganPosId,
                true
            );

            if (!in_array($antrian->status, ['waiting', 'in_progress'])) {
                throw ValidationException::withMessages([
                    'antrian' => 'Antrean ini tidak dapat dilewati.',
                ]);
            }

            $antrian->update([
                'status' => 'skipped',
            ]);

            return $antrian->fresh([
                'kunjungan.warga',
                'posyanduPos.posPemeriksaan',
            ]);
        });
    }

    /**
     * Memasukkan kembali antrean yang dilewati.
     */
    public function panggilUlang(
        User $user,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): KunjunganPos {
        return DB::connection('posyandu')->transaction(function () use (
            $user,
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        ) {
            $this->validasiAkses(
                $user,
                $kegiatanId,
                $posyanduPosId
            );

            $antrian = $this->ambilAntrian(
                $kegiatanId,
                $posyanduPosId,
                $kunjunganPosId,
                true
            );

            if ($antrian->status !== 'skipped') {
                throw ValidationException::withMessages([
                    'antrian' => 'Hanya antrean yang dilewati yang dapat dimasukkan kembali.',
                ]);
            }

            $antrian->update([
                'status' => 'waiting',
                'urutan_panggil' => null,
                'waktu_mulai' => null,
                'waktu_selesai' => null,
            ]);

            return $antrian->fresh([
                'kunjungan.warga',
                'posyanduPos.posPemeriksaan',
            ]);
        });
    }

    /**
     * Menyelesaikan pemeriksaan di pos.
     */
    public function selesai(
        User $user,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): KunjunganPos {
        return DB::connection('posyandu')->transaction(function () use (
            $user,
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        ) {
            $posyanduPos = $this->validasiAkses(
                $user,
                $kegiatanId,
                $posyanduPosId
            );

            $kunjunganPos = $this->ambilAntrian(
                $kegiatanId,
                $posyanduPosId,
                $kunjunganPosId,
                true
            );

            if ($kunjunganPos->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pemeriksaan yang sedang berlangsung yang dapat diselesaikan.',
                ]);
            }

            // Jenis pemeriksaan tetap berasal dari master pos_pemeriksaan.
            $jenisWajib = $posyanduPos->jenisPemeriksaan()
                ->where('status', true)
                ->where('wajib', true)
                ->get();

            $hasil = Pemeriksaan::query()
                ->where('kunjungan_pos_id', $kunjunganPos->id)
                ->get()
                ->keyBy('jenis_pemeriksaan_id');

            $belumDiisi = [];

            foreach ($jenisWajib as $jenis) {
                $pemeriksaan = $hasil->get($jenis->id);

                $sudahDiisi = false;

                if ($pemeriksaan) {
                    if ($jenis->tipe_nilai === 'numeric') {
                        $sudahDiisi = $pemeriksaan->nilai_numerik !== null;
                    } else {
                        $sudahDiisi = filled($pemeriksaan->nilai_teks);
                    }
                }

                if (!$sudahDiisi) {
                    $belumDiisi[] = $jenis->nama;
                }
            }

            if (count($belumDiisi) > 0) {
                throw ValidationException::withMessages([
                    'pemeriksaan' => [
                        'Pemeriksaan berikut belum diisi: '
                            . implode(', ', $belumDiisi),
                    ],
                ]);
            }

            $kunjunganPos->update([
                'status' => 'completed',
                'waktu_selesai' => now(),
            ]);

            // Periksa apakah seluruh POS pada kunjungan sudah selesai.
            $kunjungan = Kunjungan::query()
                ->whereKey($kunjunganPos->kunjungan_id)
                ->lockForUpdate()
                ->firstOrFail();

            $masihAdaAntrian = KunjunganPos::query()
                ->where('kunjungan_id', $kunjungan->id)
                ->where('status', '!=', 'completed')
                ->exists();

            // Jika seluruh POS selesai, selesaikan kunjungan.
            if (!$masihAdaAntrian && $kunjungan->status !== 'selesai') {
                $kunjungan->update([
                    'status' => 'selesai',
                ]);
            }

            return $kunjunganPos->fresh([
                'kunjungan.warga',
                'posyanduPos.posPemeriksaan',
                'pemeriksaan.jenisPemeriksaan',
            ]);
        });
    }

    /**
     * Memastikan kegiatan, pos, dan petugas berada dalam satu lingkup akses.
     */
    private function validasiAkses(
        User $user,
        int $kegiatanId,
        int $posyanduPosId
    ): PosyanduPos {
        $posyanduId = $this->ambilPosyanduIdKegiatan($kegiatanId);

        $assignment = $this->petugasContext->petugasAssignment(
            $user,
            $posyanduId
        );

        $pos = PosyanduPos::query()
            ->whereKey($posyanduPosId)
            ->where('posyandu_id', $posyanduId)
            ->where('status', true)
            ->firstOrFail();

        // Admin boleh mengakses semua pos aktif di Posyandunya.
        if ($assignment->peran === 'admin') {
            return $pos;
        }

        // Kader hanya boleh mengakses pos yang ditugaskan.
        $this->petugasContext->authorizePosPemeriksaan(
            $user,
            $posyanduId,
            $posyanduPosId
        );

        return $pos;
    }

    /**
     * Memastikan kegiatan benar-benar ada dan mengambil Posyandu-nya.
     */
    private function ambilPosyanduIdKegiatan(int $kegiatanId): int
    {
        $kegiatan = KegiatanPosyandu::query()
            ->findOrFail($kegiatanId);

        return (int) $kegiatan->posyandu_id;
    }

    /**
     * Mengambil antrean berdasarkan kegiatan dan pos.
     */
    private function ambilAntrian(
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId,
        bool $lock = false
    ): KunjunganPos {
        $query = KunjunganPos::query()
            ->whereKey($kunjunganPosId)
            ->where('posyandu_pos_id', $posyanduPosId)
            ->whereHas('kunjungan', function ($query) use ($kegiatanId) {
                $query->where('kegiatan_id', $kegiatanId);
            });

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
