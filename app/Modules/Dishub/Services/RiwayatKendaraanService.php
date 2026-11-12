<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\HasilUji;
use Illuminate\Database\Eloquent\Builder;

class RiwayatKendaraanService
{
    public function __construct(
        private NomorKendaraanService $nomorKendaraanService,
        private TanggalUjiService $tanggalUjiService
    ) {
    }

    /**
     * Riwayat uji berdasarkan Nomor Uji dan
     * 5 digit terakhir Nomor Rangka/Chasis.
     */
    public function getByNomor(
        string $nomor,
        string $noChasis
    ): array {
        $input = $this->nomorKendaraanService->normalizeInput(
            $nomor,
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

        $riwayat = HasilUji::query()
            ->with([
                'kendaraan' => function ($query) {
                    $query->select([
                        'id_kendaraan',
                        'no_uji',
                        'no_kendaraan',
                        'no_chasis',
                    ]);
                },
                'daftar:id_daftar,tgl_uji',
                'uji:id_uji,nm_uji',
            ])
            ->whereHas('kendaraan', function (Builder $query) use (
                $normalizedNoUji,
                $normalizedNoChasis
            ) {
                $query
                    ->whereRaw(
                        "REPLACE(LOWER(COALESCE(no_uji, '')), ' ', '') = ?",
                        [$normalizedNoUji]
                    )
                    ->whereRaw(
                        "RIGHT(REPLACE(LOWER(COALESCE(no_chasis, '')), ' ', ''), 5) = ?",
                        [$normalizedNoChasis]
                    );
            })
            ->get()
            ->sortByDesc(function (HasilUji $item) {
                return $item->daftar?->tgl_uji;
            })
            ->values();

        if ($riwayat->isEmpty()) {
            return [
                'status' => 'error',
                'status_message' => 'Data Kosong',
                'data' => [],
            ];
        }

        $data = $riwayat
            ->map(function (HasilUji $item) {
                $tanggalUji = $item->daftar?->tgl_uji;

                return [
                    'no_uji' => $item->kendaraan?->no_uji ?? '',
                    'no_kendaraan' => $item->kendaraan?->no_kendaraan ?? '',
                    'tgl_uji' => $this->tanggalUjiService
                        ->formatTanggal($tanggalUji),
                    'tglmati' => $this->tanggalUjiService
                        ->tanggalMatiUji($tanggalUji),
                    'nm_uji' => $item->uji?->nm_uji ?? '',
                    'status_lulus' => $this->statusKelulusan($item),
                    'nm_penguji' => $item->nm_penguji ?? '',
                    'id_hasil_uji' => $item->id_hasil_uji,
                ];
            })
            ->all();

        return [
            'status' => 'ok',
            'status_message' => '-',
            'data' => $data,
        ];
    }

    /**
     * Status kelulusan berdasarkan seluruh tahapan pengujian.
     */
    private function statusKelulusan(HasilUji $hasil): string
    {
        $kolomKelulusan = [
            'lulus_prauji',
            'lulus_smoke',
            'lulus_break',
            'lulus_pitlift',
            'lulus_lampu',
        ];

        foreach ($kolomKelulusan as $kolom) {
            if (!$this->isTrue($hasil->getAttribute($kolom))) {
                return 'TIDAK LULUS';
            }
        }

        return 'LULUS';
    }

    /**
     * Konversi nilai boolean dari database.
     */
    private function isTrue(mixed $value): bool
    {
        return in_array(
            strtolower((string) $value),
            ['1', 'true', 't', 'yes', 'y'],
            true
        );
    }
}