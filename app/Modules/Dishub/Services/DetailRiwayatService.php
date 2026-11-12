<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\HasilUji;
use Carbon\Carbon;

class DetailRiwayatService
{
    public function getByIdHasilUji(int $idHasilUji, string $noUji): array
    {
        $hasil = HasilUji::query()
            ->with([
                'kendaraan',
                'kendaraan.riwayat',
                'daftar.retribusi',
                'daftar.prosesTerakhir',
                'uji',
            ])
            ->where('id_hasil_uji', $idHasilUji)
            ->whereHas('kendaraan', function ($query) use ($noUji) {
            $query->whereRaw(
                'UPPER(TRIM(no_uji)) = ?',
                [strtoupper(trim($noUji))]
            );
        })
            ->first();

        if (!$hasil) {
            return [
                'status' => 'error',
                'message' => 'Data Kosong',
                'data' => $this->emptyData(),
            ];
        }

        $kendaraan = $hasil->kendaraan;
        $daftar = $hasil->daftar;
        $proses = $daftar?->prosesTerakhir;
        // Ambil riwayat uji kendaraan yang paling baru
        $riwayatTerakhir = $kendaraan?->riwayat
            ?->sortByDesc('tgl_uji')
            ->first();

        $data = [
            'no_uji' => $kendaraan?->no_uji ?? '',
            'no_kendaraan' => $kendaraan?->no_kendaraan ?? '',
            'merk' => $kendaraan?->merk ?? '',
            'tipe' => $kendaraan?->tipe ?? '',
            'no_chasis' => $kendaraan?->no_chasis ?? '',
            'no_mesin' => $kendaraan?->no_mesin ?? '',
            'pemilik' => $kendaraan?->nama_pemilik ?? '',
            'alamat' => $kendaraan?->alamat ?? '',

            'tgl_retribusi' => $this->formatTanggal(
                $daftar?->retribusi?->tgl_retribusi
            ),
            'tgl_uji' => $this->formatTanggal($daftar?->tgl_uji),
            'tglmati' => $this->formatTanggalMati($daftar?->tgl_uji),

            'nm_uji' => $hasil->uji?->nm_uji ?? '',

            'ptgs_prauji' => $proses?->ptgs_prauji ?? '',
            'ptgs_smoke' => $proses?->ptgs_smoke ?? '',
            'ptgs_pitlift' => $proses?->ptgs_pitlift ?? '',
            'ptgs_lampu' => $proses?->ptgs_lampu ?? '',
            'ptgs_break' => $proses?->ptgs_break ?? '',

            'nm_penguji' => $hasil->nm_penguji ?? '',

            'ems_diesel' => $hasil->ems_diesel ?? '',
            'ems_mesin_co' => $hasil->ems_mesin_co ?? '',
            'ems_mesin_hc' => $hasil->ems_mesin_hc ?? '',

            'ktlamp_kanan' => $hasil->ktlamp_kanan ?? '',
            'ktlamp_kiri' => $hasil->ktlamp_kiri ?? '',
            'dev_kanan' => $hasil->dev_kanan ?? '',
            'dev_kiri' => $hasil->dev_kiri ?? '',

            'selrem_sb1' => $hasil->selrem_sb1 ?? '',
            'selrem_sb2' => $hasil->selrem_sb2 ?? '',
            'selrem_sb3' => $hasil->selrem_sb3 ?? '',
            'selrem_sb4' => $hasil->selrem_sb4 ?? '',

            'selgaya1' => $hasil->selgaya1 ?? '',
            'selgaya2' => $hasil->selgaya2 ?? '',
            'selgaya3' => $hasil->selgaya3 ?? '',
            'selgaya4' => $hasil->selgaya4 ?? '',

            'status_lulus' => $this->statusKelulusan($hasil),

            'img_depan' => $hasil->img_depan ?? '',
            'img_belakang' => $hasil->img_belakang ?? '',
            'img_kanan' => $hasil->img_kanan ?? '',
            'img_kiri' => $hasil->img_kiri ?? '',

            'lulus_prauji' => $hasil->lulus_prauji ?? '',
            'lulus_smoke' => $hasil->lulus_smoke ?? '',
            'lulus_pitlift' => $hasil->lulus_pitlift ?? '',
            'lulus_lampu' => $hasil->lulus_lampu ?? '',
            'lulus_break' => $hasil->lulus_break ?? '',
        ];

        return [
            'status' => 'ok',
            'message' => '-',
            'data' => $data,
        ];
    }

    private function emptyData(): array
    {
        return array_fill_keys([
            'no_uji',
            'no_kendaraan',
            'merk',
            'tipe',
            'no_chasis',
            'no_mesin',
            'pemilik',
            'alamat',
            'tgl_retribusi',
            'tgl_uji',
            'tglmati',
            'nm_uji',
            'ptgs_prauji',
            'ptgs_smoke',
            'ptgs_pitlift',
            'ptgs_lampu',
            'ptgs_break',
            'nm_penguji',
            'ems_diesel',
            'ems_mesin_co',
            'ems_mesin_hc',
            'ktlamp_kanan',
            'ktlamp_kiri',
            'dev_kanan',
            'dev_kiri',
            'selrem_sb1',
            'selrem_sb2',
            'selrem_sb3',
            'selrem_sb4',
            'selgaya1',
            'selgaya2',
            'selgaya3',
            'selgaya4',
            'status_lulus',
            'img_depan',
            'img_belakang',
            'img_kanan',
            'img_kiri',
            'lulus_prauji',
            'lulus_smoke',
            'lulus_pitlift',
            'lulus_lampu',
            'lulus_break',
        ], '');
    }

    private function statusKelulusan(HasilUji $hasil): string
    {
        $fields = [
            'lulus_prauji',
            'lulus_smoke',
            'lulus_break',
            'lulus_pitlift',
            'lulus_lampu',
        ];

        foreach ($fields as $field) {
            if (!$this->isTrue($hasil->getAttribute($field))) {
                return 'TIDAK LULUS';
            }
        }

        return 'LULUS';
    }

    private function isTrue(mixed $value): bool
    {
        return in_array(
            strtolower((string) $value),
            ['1', 'true', 't', 'yes', 'y'],
            true
        );
    }

    private function formatTanggal(mixed $tanggal): string
    {
        if (blank($tanggal)) {
            return '';
        }

        return Carbon::parse($tanggal)
            ->locale('id')
            ->translatedFormat('d F Y');
    }

    private function formatTanggalMati(mixed $tanggalUji): string
    {
        if (blank($tanggalUji)) {
            return '';
        }

        return Carbon::parse($tanggalUji)
            ->addMonths(6)
            ->locale('id')
            ->translatedFormat('d F Y');
    }
}
