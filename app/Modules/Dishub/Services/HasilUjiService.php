<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\HasilUji;
use Illuminate\Database\Eloquent\Builder;

class HasilUjiService
{
    public function hasilUji(string $noUji): array
    {
        $normalized = $this->normalize($noUji);

        $hasil = HasilUji::query()
            ->with([
                'kendaraan' => function ($query) {
                    $query->select([
                        'id_kendaraan',
                        'no_uji',
                        'no_kendaraan',
                        'nama_pemilik',
                        'tgl_mati_uji',
                    ]);
                },
            ])
            ->whereHas('kendaraan', function (Builder $query) use ($normalized) {
                $query
                    ->whereRaw(
                        "REPLACE(LOWER(COALESCE(no_uji, '')), ' ', '') LIKE ?",
                        ['%' . $normalized]
                    )
                    ->orWhereRaw(
                        "REPLACE(LOWER(COALESCE(no_kendaraan, '')), ' ', '') = ?",
                        [$normalized]
                    );
            })
            ->orderByDesc('id_hasil_uji')
            ->first();

        if (!$hasil || !$hasil->kendaraan) {
            return [
                'status' => false,
                'message' => 'Data hasil uji tidak ditemukan.',
                'data' => $this->emptyData(),
            ];
        }

        $kendaraan = $hasil->kendaraan;

        $tahapan = [
            $this->isTrue($hasil->prauji),
            $this->isTrue($hasil->smoke),
            $this->isTrue($hasil->pitlift),
            $this->isTrue($hasil->lampu),
            $this->isTrue($hasil->getAttribute('break')),
        ];

        $kelulusan = [
            $this->isTrue($hasil->lulus_prauji),
            $this->isTrue($hasil->lulus_smoke),
            $this->isTrue($hasil->lulus_pitlift),
            $this->isTrue($hasil->lulus_lampu),
            $this->isTrue($hasil->lulus_break),
        ];

        $semuaTahapSelesai = !in_array(false, $tahapan, true);
        $semuaLulus = !in_array(false, $kelulusan, true);

        $ltl = !$semuaTahapSelesai
            ? 'PROSES'
            : ($semuaLulus ? 'LULUS' : 'TIDAK LULUS');

        $data = [
            'id_hasil_uji' => $hasil->id_hasil_uji ?? 0,
            'no_uji' => $kendaraan->no_uji ?? '',
            'no_kendaraan' => $kendaraan->no_kendaraan ?? '',
            'pemilik' => $kendaraan->nama_pemilik ?? '',

            'hasil_tgl_terakhir_uji' => $this->formatTanggal(
                $hasil->jdatang
            ),
            'hasil_tgl_mati_uji' => $this->formatTanggal(
                $kendaraan->tgl_mati_uji
            ),

            'hasil_prauji' => $this->statusTahap(
                $hasil->prauji,
                $hasil->lulus_prauji
            ),
            'hasil_emisi' => $this->statusTahap(
                $hasil->smoke,
                $hasil->lulus_smoke
            ),
            'hasil_pitlift' => $this->statusTahap(
                $hasil->pitlift,
                $hasil->lulus_pitlift
            ),
            'hasil_lampu' => $this->statusTahap(
                $hasil->lampu,
                $hasil->lulus_lampu
            ),
            'hasil_break' => $this->statusTahap(
                $hasil->getAttribute('break'),
                $hasil->lulus_break
            ),

            'ltl' => $ltl,

            'img_depan' => $hasil->img_depan ?? '',
            'img_belakang' => $hasil->img_belakang ?? '',
            'img_kanan' => $hasil->img_kanan ?? '',
            'img_kiri' => $hasil->img_kiri ?? '',
        ];

        return [
            'status' => true,
            'message' => 'Data hasil uji berhasil ditemukan.',
            'data' => $data,
        ];
    }

    private function emptyData(): array
    {
        return [
            'id_hasil_uji' => 0,
            'no_uji' => '',
            'no_kendaraan' => '',
            'pemilik' => '',
            'hasil_tgl_terakhir_uji' => '',
            'hasil_tgl_mati_uji' => '',
            'hasil_prauji' => '',
            'hasil_emisi' => '',
            'hasil_pitlift' => '',
            'hasil_lampu' => '',
            'hasil_break' => '',
            'ltl' => '',
            'img_depan' => '',
            'img_belakang' => '',
            'img_kanan' => '',
            'img_kiri' => '',
        ];
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(
            preg_replace('/\s+/', '', trim($value)) ?? ''
        );
    }

    private function isTrue(mixed $value): bool
    {
        return in_array(
            strtolower((string) $value),
            ['1', 'true', 't', 'yes', 'y'],
            true
        );
    }

    private function statusTahap(
        mixed $proses,
        mixed $lulus
    ): string {
        if (!$this->isTrue($proses)) {
            return 'BELUM DIPROSES';
        }

        return $this->isTrue($lulus)
            ? 'LULUS'
            : 'TIDAK LULUS';
    }

    private function formatTanggal(mixed $tanggal): string
    {
        if (blank($tanggal)) {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($tanggal)
            ->locale('id')
            ->translatedFormat('d F Y');
    }
}
