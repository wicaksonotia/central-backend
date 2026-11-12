<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\HasilUji;
use App\Modules\Dishub\Models\Kendaraan;
use App\Modules\Dishub\Models\Riwayat;
use RuntimeException;
use Throwable;

class VehicleLookupService
{
    private const DETAIL_FIELDS = [
        'no_chasis' => 'Nomor Rangka',
        'no_mesin' => 'Nomor Mesin',
        'merk' => 'Merek',
        'tipe' => 'Tipe',
        'jenis' => 'Jenis Kendaraan',
        'bahan_bakar' => 'Bahan Bakar',
        'jbb' => 'JBB',
        'jbi' => 'JBI',
        'mst' => 'MST',
        'berat_kosong' => 'Berat Kosong',
        'panjang' => 'Panjang Kendaraan',
        'tinggi' => 'Tinggi Kendaraan',
        'lebar' => 'Lebar Kendaraan',
        'kelas_jalan' => 'Kelas Jalan Terendah',
    ];

    private const PHOTO_FIELDS = [
        'img_depan' => 'Tampak Depan',
        'img_belakang' => 'Tampak Belakang',
        'img_kanan' => 'Tampak Kanan',
        'img_kiri' => 'Tampak Kiri',
    ];

    private const TEST_RESULTS = [
        'lulus_prauji' => 'Prauji',
        'lulus_smoke' => 'Emisi',
        'lulus_pitlift' => 'Pit Lift',
        'lulus_lampu' => 'Lampu',
        'lulus_break' => 'Rem',
    ];

    public function find(
        string $testNumber,
        string $lastFive
    ): ?array {
        $testNumber = $this->normalize($testNumber);
        $lastFive = $this->normalize($lastFive);

        if (
            $testNumber === ''
            || !preg_match('/^[0-9]{5}$/', $lastFive)
        ) {
            return null;
        }

        try {
            $kendaraan = Kendaraan::query()
                ->with([
                    'type',
                    'sertifikasi',
                    'jenisKendaraan',
                ])
                ->whereRaw(
                    "regexp_replace(
                        upper(coalesce(no_uji, '')),
                        '[[:space:]]',
                        '',
                        'g'
                    ) = ?",
                    [$testNumber]
                )
                ->whereRaw(
                    "right(
                        regexp_replace(
                            upper(coalesce(no_chasis, '')),
                            '[[:space:]]',
                            '',
                            'g'
                        ),
                        5
                    ) = ?",
                    [$lastFive]
                )
                ->first();

            if (!$kendaraan) {
                return null;
            }

            return [
                'nouji' => (string) ($kendaraan->no_uji ?? ''),
                'masaberlakuuji' => (string) (
                    $kendaraan->tgl_mati_uji ?? ''
                ),
                'details' => $this->formatDetails($kendaraan),

                // Foto tidak lagi ditampilkan pada bagian identitas kendaraan.
                // Foto disediakan per riwayat melalui key foto_kendaraan.
                'photos' => [],

                'riwayat' => $this->getRiwayat(
                    (int) $kendaraan->id_kendaraan
                ),
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Tidak dapat membaca data kendaraan.',
                previous: $exception
            );
        }
    }

    private function normalize(string $value): string
    {
        return mb_strtoupper(
            preg_replace('/\s+/u', '', trim($value)) ?? '',
            'UTF-8'
        );
    }

    private function formatDetails(Kendaraan $kendaraan): array
    {
        $type = $kendaraan->type;
        $jenisKendaraan = $kendaraan->jenisKendaraan;

        $raw = [
            'no_chasis' => $kendaraan->no_chasis,
            'no_mesin' => $kendaraan->no_mesin,
            'merk' => $kendaraan->merk,
            'tipe' => $kendaraan->tipe,
            'jenis' => $jenisKendaraan?->jenis
                ?? $kendaraan->jenis,
            'bahan_bakar' => $type?->bahan_bakar,
            'jbb' => $type?->kemjbb,
            'jbi' => $this->calculateJbi($type),
            'mst' => $type?->mst,
            'berat_kosong' => null,
            'panjang' => $type?->ukuran_panjang,
            'tinggi' => $type?->ukuran_tinggi,
            'lebar' => $type?->ukuran_lebar,
            'kelas_jalan' => $type?->kls_jln,
        ];

        $details = [];

        foreach (self::DETAIL_FIELDS as $field => $label) {
            $value = $raw[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $details[] = [
                'label' => $label,
                'value' => (string) $value,
            ];
        }

        return $details;
    }

    private function calculateJbi($type): ?float
    {
        if (!$type) {
            return null;
        }

        $fields = [
            'kemorang',
            'kembarang',
            'bsumbu1',
            'bsumbu2',
            'bsumbu3',
            'bsumbu4',
            'bsumbu5',
        ];

        $total = 0.0;
        $hasValue = false;

        foreach ($fields as $field) {
            $value = $type->{$field};

            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $number = preg_replace(
                '/[^0-9,.-]/',
                '',
                (string) $value
            );

            $number = str_replace(',', '.', $number);

            if (!is_numeric($number)) {
                continue;
            }

            $total += (float) $number;
            $hasValue = true;
        }

        return $hasValue ? round($total, 2) : null;
    }

    private function formatPhotos($hasilUji): array
    {
        if (!$hasilUji) {
            return [];
        }

        $photos = [];

        foreach (self::PHOTO_FIELDS as $field => $label) {
            $src = $this->imageDataUri($hasilUji->{$field});

            if ($src === null) {
                continue;
            }

            $photos[] = [
                'label' => $label,
                'src' => $src,
            ];
        }

        return $photos;
    }

    private function imageDataUri(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match(
            '/^data:image\/(jpeg|png|webp);base64,/i',
            $value
        )) {
            return $value;
        }

        $decoded = base64_decode($value, true);

        if ($decoded !== false && $decoded !== '') {
            $mime = $this->detectImageMime($decoded);

            if ($mime !== null) {
                return 'data:' . $mime
                    . ';base64,'
                    . base64_encode($decoded);
            }
        }

        // Mendukung kolom yang menyimpan data gambar dalam bentuk biner.
        $mime = $this->detectImageMime($value);

        if ($mime !== null) {
            return 'data:' . $mime
                . ';base64,'
                . base64_encode($value);
        }

        return null;
    }

    private function detectImageMime(string $binary): ?string
    {
        $imageInfo = @getimagesizefromstring($binary);

        if ($imageInfo === false) {
            return null;
        }

        $mime = $imageInfo['mime'] ?? '';

        return in_array(
            $mime,
            ['image/jpeg', 'image/png', 'image/webp'],
            true
        ) ? $mime : null;
    }

    private function getRiwayat(int $idKendaraan): array
    {
        $riwayat = Riwayat::query()
            ->where('id_kendaraan', $idKendaraan)
            ->orderByDesc('tgl_uji')
            ->orderByDesc('id_riwayat')
            ->get([
                'id_riwayat',
                'tgl_uji',
                'tempat',
                'catatan',
                'nama_penguji',
                'id_hasil_uji',
                'tempat_tujuan',
            ]);

        if ($riwayat->isEmpty()) {
            return [];
        }

        $hasilUjiIds = $riwayat
            ->pluck('id_hasil_uji')
            ->filter()
            ->unique()
            ->values();

        // Ambil foto dan hasil pemeriksaan dari hasil uji yang
        // benar-benar terhubung dengan setiap baris riwayat.
        $hasilUjiMap = HasilUji::query()
            ->whereIn('id_hasil_uji', $hasilUjiIds)
            ->get([
                'id_hasil_uji',
                'jdatang',
                'jselesai',
                'lulus_prauji',
                'lulus_smoke',
                'lulus_pitlift',
                'lulus_lampu',
                'lulus_break',
                'uji_ulang',
                'img_depan',
                'img_belakang',
                'img_kanan',
                'img_kiri',
            ])
            ->keyBy('id_hasil_uji');

        return $riwayat->map(function ($item) use ($hasilUjiMap) {
            $hasil = $hasilUjiMap->get($item->id_hasil_uji);

            return [
                'id_riwayat' => $item->id_riwayat,
                'tanggal_uji' => $item->tgl_uji,
                'tempat' => $item->tempat,
                'tempat_tujuan' => $item->tempat_tujuan,
                'nama_penguji' => $item->nama_penguji,
                'catatan' => $item->catatan,
                'hasil_uji' => $this->formatHasilUji($hasil),
                'foto_kendaraan' => $this->formatPhotos($hasil),
            ];
        })->all();
    }

    private function formatHasilUji($hasil): ?array
    {
        if (!$hasil) {
            return null;
        }

        $results = [];

        foreach (self::TEST_RESULTS as $field => $label) {
            $value = $hasil->{$field};
            $key = str_replace('lulus_', '', $field);

            $results[$key] = array_merge(
                ['label' => $label],
                $this->formatStatus($value)
            );
        }

        return [
            'tanggal_datang' => $hasil->jdatang,
            'tanggal_selesai' => $hasil->jselesai,
            'uji_ulang' => $hasil->uji_ulang,
            ...$results,
        ];
    }

    private function formatStatus(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [
                'status' => 'BELUM ADA DATA',
                'status_class' => 'unknown',
            ];
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['t', 'true', '1'], true)) {
                $value = true;
            } elseif (in_array($normalized, ['f', 'false', '0'], true)) {
                $value = false;
            } else {
                return [
                    'status' => 'BELUM ADA DATA',
                    'status_class' => 'unknown',
                ];
            }
        }

        return (bool) $value
            ? [
                'status' => 'LULUS',
                'status_class' => 'passed',
            ]
            : [
                'status' => 'TIDAK LULUS',
                'status_class' => 'failed',
            ];
    }
}