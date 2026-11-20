<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\Warga;
use Carbon\Carbon;

class WargaPublicService
{
    /**
     * Mencari warga berdasarkan nama atau NIK.
     */
    public function search(string $keyword): array
    {
        $keyword = trim($keyword);

        $isNik = ctype_digit($keyword);

        $query = Warga::query()
            ->select([
                'id',
                'posyandu_id',
                'nik',
                'nama',
                'jenis_kelamin',
                'tanggal_lahir',
            ])
            ->with('posyandu:id,nama_posyandu');

        if ($isNik) {
            $query->where('nik', $keyword);
        } else {
            $query->where('nama', 'ilike', '%' . $keyword . '%');
        }

        $warga = $query
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return [
            'count' => $warga->count(),

            'items' => $warga->map(fn($item) => [
                'id' => $item->id,
                'posyandu_id' => $item->posyandu_id,
                'posyandu_nama' => $item->posyandu?->nama_posyandu,
                'nik_masked' => $this->maskNik($item->nik),
                'nama' => $item->nama,
                'jenis_kelamin' => $item->jenis_kelamin,
                'tanggal_lahir' => $item->tanggal_lahir?->format('Y-m-d'),
                'umur' => $this->calculateAge($item->tanggal_lahir),
            ])->values()->all(),
        ];
    }

    /**
     * Mengambil profil warga beserta seluruh riwayat kunjungan.
     */
    public function profile(int $wargaId): ?array
    {
        $warga = Warga::query()
            ->with([
                'posyandu:id,nama_posyandu',

                'kunjungan' => fn($query) => $query
                    ->orderBy('waktu_daftar'),

                'kunjungan.kegiatan:id,nama_kegiatan,tanggal_kegiatan',

                'kunjungan.kunjunganPos' => fn($query) => $query
                    ->orderBy('id'),

                'kunjungan.kunjunganPos.posyanduPos.posPemeriksaan',

                'kunjungan.kunjunganPos.pemeriksaan.jenisPemeriksaan',

                'kunjungan.kunjunganPos.pemeriksaanKeluhan.masterKeluhan',
            ])
            ->find($wargaId);

        if (!$warga) {
            return null;
        }

        return [
            'id' => $warga->id,
            'posyandu_id' => $warga->posyandu_id,
            'posyandu_nama' => $warga->posyandu?->nama_posyandu,
            'nik_masked' => $this->maskNik($warga->nik),
            'nama' => $warga->nama,
            'jenis_kelamin' => $warga->jenis_kelamin,
            'tanggal_lahir' => $warga->tanggal_lahir?->format('Y-m-d'),
            'umur' => $this->calculateAge($warga->tanggal_lahir),

            'riwayat' => $warga->kunjungan
                ->map(fn($kunjungan) => [
                    'id' => $kunjungan->id,
                    'nomor_antrian' => $kunjungan->nomor_antrian,
                    'waktu_daftar' => $kunjungan->waktu_daftar?->toDateTimeString(),
                    'status' => $kunjungan->status,

                    'kegiatan' => [
                        'nama' => $kunjungan->kegiatan?->nama_kegiatan,
                        'tanggal' => $kunjungan->kegiatan?->tanggal_kegiatan?->format('Y-m-d'),
                    ],

                    'pelayanan' => $kunjungan->kunjunganPos
                        ->map(fn($pos) => [
                            'posyandu_pos_id' => $pos->posyandu_pos_id,
                            'pos_pemeriksaan_id' => $pos->posyanduPos?->pos_pemeriksaan_id,
                            'nama_pos' => $pos->posyanduPos?->posPemeriksaan?->nama,
                            'urutan' => $pos->posyanduPos?->urutan,
                            'status' => $pos->status,
                            'catatan' => $pos->catatan,

                            'pemeriksaan' => $pos->pemeriksaan
                                ->map(fn($pemeriksaan) => [
                                    'id' => $pemeriksaan->id,
                                    'jenis_pemeriksaan_id' => $pemeriksaan->jenis_pemeriksaan_id,
                                    'jenis' => $pemeriksaan->jenisPemeriksaan?->nama,
                                    'satuan' => $pemeriksaan->jenisPemeriksaan?->satuan,
                                    'pengukuran_ke' => $pemeriksaan->pengukuran_ke,
                                    'nilai_numerik' => $pemeriksaan->nilai_numerik,
                                    'nilai_teks' => $pemeriksaan->nilai_teks,
                                    'keterangan' => $pemeriksaan->keterangan,
                                    'waktu_pemeriksaan' => $pemeriksaan->waktu_pemeriksaan?->toDateTimeString(),
                                ])
                                ->values(),

                            'keluhan' => $pos->pemeriksaanKeluhan
                                ->map(fn($keluhan) => [
                                    'id' => $keluhan->id,
                                    'master_keluhan_id' => $keluhan->master_keluhan_id,
                                    'nama_keluhan' => $keluhan->masterKeluhan?->nama,
                                    'keluhan_lainnya' => $keluhan->keluhan_lainnya,
                                    'catatan' => $keluhan->catatan,
                                ])
                                ->values(),
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }

    /**
     * Menghitung umur dalam tahun, bulan, dan hari.
     */
    private function calculateAge($tanggalLahir): ?array
    {
        if (!$tanggalLahir) {
            return null;
        }

        $birthDate = Carbon::parse($tanggalLahir);
        $today = Carbon::today();

        if ($birthDate->greaterThan($today)) {
            return null;
        }

        $age = $birthDate->diff($today);

        return [
            'tahun' => $age->y,
            'bulan' => $age->m,
            'hari' => $age->d,
            'label' => "{$age->y} tahun {$age->m} bulan",
        ];
    }

    /**
     * Menyamarkan lima digit pertama NIK.
     */
    private function maskNik(?string $nik): ?string
    {
        if (!$nik) {
            return null;
        }

        return str_repeat('x', min(5, strlen($nik)))
            . substr($nik, 5);
    }
}
