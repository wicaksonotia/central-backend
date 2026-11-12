<?php

namespace App\Modules\Dishub\Services;

use Carbon\Carbon;

class TanggalUjiService
{
    /**
     * Format tanggal ke bahasa Indonesia.
     * Contoh: 26 Oktober 2026.
     */
    public function formatTanggal(mixed $tanggal): string
    {
        if (blank($tanggal)) {
            return '';
        }

        return Carbon::parse($tanggal)
            ->locale('id')
            ->translatedFormat('d F Y');
    }

    /**
     * Tanggal mati uji = tanggal uji + 6 bulan.
     */
    public function tanggalMatiUji(mixed $tanggalUji): string
    {
        if (blank($tanggalUji)) {
            return '';
        }

        return Carbon::parse($tanggalUji)
            ->addMonths(6)
            ->locale('id')
            ->translatedFormat('d F Y');
    }

    /**
     * Periksa apakah masa berlaku uji sudah lewat.
     */
    public function isMati(mixed $tanggalUji): bool
    {
        if (blank($tanggalUji)) {
            return false;
        }

        return Carbon::parse($tanggalUji)
            ->addMonths(6)
            ->startOfDay()
            ->lt(now()->startOfDay());
    }
}
