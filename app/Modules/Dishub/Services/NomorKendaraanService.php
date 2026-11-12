<?php

namespace App\Modules\Dishub\Services;

class NomorKendaraanService
{
    /**
     * Normalisasi nomor agar tidak sensitif
     * terhadap huruf besar/kecil dan spasi.
     */
    public function normalize(string $value): string
    {
        return mb_strtolower(
            preg_replace('/\s+/', '', trim($value)) ?? ''
        );
    }

    /**
     * Normalisasi dan validasi pasangan nomor kendaraan
     * dan 5 digit terakhir nomor rangka/chasis.
     */
    public function normalizeInput(
        string $noUji,
        string $noChasis
    ): array {
        $normalizedNoUji = $this->normalize($noUji);
        $normalizedNoChasis = $this->normalize($noChasis);

        return [
            'no_uji' => $normalizedNoUji,
            'no_chasis' => $normalizedNoChasis,
            'valid' => $normalizedNoUji !== ''
                && preg_match('/^\d{5}$/', $normalizedNoChasis) === 1,
        ];
    }
}