<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\HasilUji;
use App\Modules\Dishub\Models\ListKelulusan;

class KeteranganTidakLulusService
{
    /**
     * Mengambil keterangan tidak lulus berdasarkan:
     * ID hasil uji, nomor uji, dan kategori.
     */
    public function getByHasilUji(
        int $idHasilUji,
        string $noUji,
        string $kategori
    ): array {
        $normalizedNoUji = mb_strtoupper(
            preg_replace('/\s+/', '', trim($noUji)) ?? ''
        );

        // Pastikan ID hasil uji benar-benar milik nomor uji tersebut.
        $hasilUjiValid = HasilUji::query()
            ->where('id_hasil_uji', $idHasilUji)
            ->whereHas('kendaraan', function ($query) use ($normalizedNoUji) {
                $query->whereRaw(
                    "REPLACE(UPPER(COALESCE(no_uji, '')), ' ', '') = ?",
                    [$normalizedNoUji]
                );
            })
            ->exists();

        if (!$hasilUjiValid) {
            return [
                'status' => 'error',
                'status_message' => 'Data hasil uji tidak ditemukan.',
                'data' => [],
            ];
        }

        $query = ListKelulusan::query()
            ->select([
                'id_hasil_uji',
                'input_tl',
                'kelulusan',
            ])
            ->where('id_hasil_uji', $idHasilUji);

        if (mb_strtolower(trim($kategori)) !== 'all') {
            $query->where('input_tl', trim($kategori));
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return [
                'status' => 'error',
                'status_message' => 'Data Kosong',
                'data' => [],
            ];
        }

        return [
            'status' => 'ok',
            'status_message' => '-',
            'data' => $items->map(function (ListKelulusan $item) {
                return [
                    'kategori_tidak_lulus' => $item->input_tl,
                    'keterangan' => $item->kelulusan,
                ];
            })->values()->all(),
        ];
    }
}
