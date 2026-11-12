<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\Kendaraan;
use Illuminate\Database\Eloquent\Builder;

class KendaraanService
{
    public function __construct(
        private NomorKendaraanService $nomorKendaraanService,
        private TanggalUjiService $tanggalUjiService
    ) {
    }

    /**
     * Detail kendaraan berdasarkan Nomor Uji atau Nomor Kendaraan,
     * serta 5 digit terakhir Nomor Rangka/Chasis.
     */
    public function detail(string $noUji, string $noChasis): array
    {
        $input = $this->nomorKendaraanService->normalizeInput(
            $noUji,
            $noChasis
        );

        if (!$input['valid']) {
            return [
                'status' => false,
                'message' => 'Nomor Uji dan 5 digit terakhir Nomor Rangka wajib diisi dengan benar.',
                'data' => null,
            ];
        }

        $normalizedNoUji = $input['no_uji'];
        $normalizedNoChasis = $input['no_chasis'];

        $kendaraan = Kendaraan::query()
            ->with([
                'type',
                'sertifikasi',
                'jenisKendaraan',
            ])
            ->where(function (Builder $query) use ($normalizedNoUji) {
                $query
                    ->whereRaw(
                        "REPLACE(LOWER(COALESCE(no_uji, '')), ' ', '') LIKE ?",
                        ['%' . $normalizedNoUji]
                    )
                    ->orWhereRaw(
                        "REPLACE(LOWER(COALESCE(no_kendaraan, '')), ' ', '') = ?",
                        [$normalizedNoUji]
                    );
            })
            ->whereRaw(
                "RIGHT(REPLACE(LOWER(COALESCE(no_chasis, '')), ' ', ''), 5) = ?",
                [$normalizedNoChasis]
            )
            ->first();

        if (!$kendaraan) {
            return [
                'status' => false,
                'message' => 'Data kendaraan tidak ditemukan.',
                'data' => null,
            ];
        }

        $type = $kendaraan->type;

        $riwayatTerakhir = $kendaraan->riwayat()
            ->orderByDesc('tgl_uji')
            ->first();

        $hasilUjiTerakhir = $kendaraan->hasilUji()
            ->orderByDesc('jdatang')
            ->first();

        $tanggalUjiTerakhir = $riwayatTerakhir?->tgl_uji;

        $tanggalMati = $this->tanggalUjiService->tanggalMatiUji(
            $tanggalUjiTerakhir
        );

        $sumbu = [
            $this->toNumber($type?->bsumbu1),
            $this->toNumber($type?->bsumbu2),
            $this->toNumber($type?->bsumbu3),
            $this->toNumber($type?->bsumbu4),
            $this->toNumber($type?->bsumbu5),
        ];

        $jbi = round(
            $this->toNumber($type?->kemorang)
                + $this->toNumber($type?->kembarang)
                + array_sum($sumbu)
        );

        $kondisi = $this->tanggalUjiService->isMati(
            $tanggalUjiTerakhir
        ) ? 'mati' : 'hidup';

        $data = [
            'no_uji' => $kendaraan->no_uji ?? '',
            'no_kendaraan' => $kendaraan->no_kendaraan ?? '',
            'merk' => $kendaraan->merk ?? '',
            'tipe' => $kendaraan->tipe ?? '',
            'no_chasis' => $kendaraan->no_chasis ?? '',
            'no_mesin' => $kendaraan->no_mesin ?? '',
            'pemilik' => $kendaraan->nama_pemilik ?? '',
            'jns_kend' => $type?->karoseri_jenis ?? '',
            'mati_uji' => $tanggalMati,
            'nama_komersil' => $type?->nm_komersil ?? '',
            'jenis_karoseri' => $type?->karoseri_jenis ?? '',
            'bahan_utama' => $type?->karoseri_bahan ?? '',

            'panjang' => $this->formatUnit(
                $type?->ukuran_panjang,
                'Cm'
            ),
            'lebar' => $this->formatUnit(
                $type?->ukuran_lebar,
                'Cm'
            ),
            'tinggi' => $this->formatUnit(
                $type?->ukuran_tinggi,
                'Cm'
            ),

            'dimpanjang' => $type?->dimpanjang ?? '',
            'dimlebar' => $type?->dimlebar ?? '',
            'dimtinggi' => $type?->dimtinggi ?? '',

            'jbb' => $this->formatUnit($type?->kemjbb, 'Kg'),
            'orang' => $this->formatUnit($type?->kemorang, 'Kg'),
            'barang' => $this->formatUnit($type?->kembarang, 'Kg'),

            'sb1' => $this->formatUnit($type?->bsumbu1, 'Kg'),
            'sb2' => $this->formatUnit($type?->bsumbu2, 'Kg'),
            'sb3' => $this->formatUnit($type?->bsumbu3, 'Kg'),
            'sb4' => $this->formatUnit($type?->bsumbu4, 'Kg'),
            'sb5' => $this->formatUnit($type?->bsumbu5, 'Kg'),

            'total_sb' => $this->formatUnit(
                array_sum($sumbu),
                'Kg'
            ),
            'jbi' => $this->formatUnit($jbi, 'Kg'),
            'mst' => $this->formatUnit($type?->mst, 'Kg'),

            'kondisi' => $kondisi,

            'img_depan' => $hasilUjiTerakhir?->img_depan ?? '',
            'img_belakang' => $hasilUjiTerakhir?->img_belakang ?? '',
            'img_kanan' => $hasilUjiTerakhir?->img_kanan ?? '',
            'img_kiri' => $hasilUjiTerakhir?->img_kiri ?? '',
        ];

        return [
            'status' => true,
            'message' => 'Data kendaraan berhasil ditemukan.',
            'data' => $data,
        ];
    }

    /**
     * Konversi nilai numerik dengan aman.
     */
    private function toNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) $value;
    }

    /**
     * Format nilai dengan satuan.
     */
    private function formatUnit(mixed $value, string $unit): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return $value . ' ' . $unit;
    }
}