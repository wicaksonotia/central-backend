<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\KunjunganPos;
use App\Modules\Posyandu\Models\JenisPemeriksaan;
use App\Modules\Posyandu\Models\Pemeriksaan;
use App\Modules\Posyandu\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PemeriksaanService
{
    public function __construct(
        protected PetugasContextService $petugasContext
    ) {}

    /**
     * Menyimpan beberapa hasil pemeriksaan sekaligus.
     */
    public function simpan(
        int $kunjunganPosId,
        array $data,
        int $userId
    ): KunjunganPos {
        return DB::connection('posyandu')->transaction(function () use (
            $kunjunganPosId,
            $data,
            $userId
        ) {
            $kunjunganPos = KunjunganPos::query()
                ->with([
                    'kunjungan.kegiatan',
                    'posyanduPos',
                ])
                ->whereKey($kunjunganPosId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($kunjunganPos->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'kunjungan_pos_id' => 'Pemeriksaan hanya dapat dilakukan ketika antrean sedang berlangsung.',
                ]);
            }

            $posyanduPos = $kunjunganPos->posyanduPos;

            if (!$posyanduPos || !$posyanduPos->status) {
                throw ValidationException::withMessages([
                    'posyandu_pos_id' => 'Pos pemeriksaan tidak ditemukan atau tidak aktif.',
                ]);
            }

            $user = User::query()->findOrFail($userId);

            $petugasPosyanduId = $this->cariPetugas(
                $user,
                $kunjunganPos
            );

            // Validasi kombinasi jenis pemeriksaan dan nomor pengukuran.
            $kombinasi = collect($data)->map(function ($item) {
                $jenisId = $item['jenis_pemeriksaan_id'] ?? '';
                $pengukuranKe = $item['pengukuran_ke'] ?? 1;

                return $jenisId . '-' . $pengukuranKe;
            });

            if ($kombinasi->unique()->count() !== $kombinasi->count()) {
                throw ValidationException::withMessages([
                    'pemeriksaan' => 'Jenis pemeriksaan dan nomor pengukuran tidak boleh duplikat.',
                ]);
            }

            foreach ($data as $item) {
                $jenis = JenisPemeriksaan::query()
                    ->whereKey($item['jenis_pemeriksaan_id'])
                    ->where(
                        'pos_pemeriksaan_id',
                        $posyanduPos->pos_pemeriksaan_id
                    )
                    ->where('status', true)
                    ->first();

                if (!$jenis) {
                    throw ValidationException::withMessages([
                        'pemeriksaan' => 'Jenis pemeriksaan tidak valid atau bukan bagian dari pos ini.',
                    ]);
                }

                $nilaiNumerik = $item['nilai_numerik'] ?? null;
                $nilaiTeks = $item['nilai_teks'] ?? null;

                if (
                    $jenis->tipe_nilai === 'numeric' &&
                    $nilaiNumerik === null
                ) {
                    throw ValidationException::withMessages([
                        'pemeriksaan' => "Nilai numerik untuk {$jenis->nama} wajib diisi.",
                    ]);
                }

                if (
                    $jenis->tipe_nilai !== 'numeric' &&
                    blank($nilaiTeks)
                ) {
                    throw ValidationException::withMessages([
                        'pemeriksaan' => "Nilai teks untuk {$jenis->nama} wajib diisi.",
                    ]);
                }

                Pemeriksaan::updateOrCreate(
                    [
                        'kunjungan_pos_id' => $kunjunganPos->id,
                        'jenis_pemeriksaan_id' => $jenis->id,
                        'pengukuran_ke' => $item['pengukuran_ke'] ?? 1,
                    ],
                    [
                        'nilai_numerik' => $nilaiNumerik,
                        'nilai_teks' => $nilaiTeks,
                        'keterangan' => $item['keterangan'] ?? null,
                        'petugas_posyandu_id' => $petugasPosyanduId,
                        'waktu_pemeriksaan' => now(),
                    ]
                );
            }

            return $kunjunganPos->fresh([
                'kunjungan.warga',
                'posyanduPos.posPemeriksaan',
                'pemeriksaan.jenisPemeriksaan',
            ]);
        });
    }

    /**
     * Mengambil detail pemeriksaan per kunjungan pos.
     */
    public function detail(int $kunjunganPosId, int $userId): array
    {
        $kunjunganPos = KunjunganPos::query()
            ->with([
                'kunjungan.warga',
                'kunjungan.kegiatan',
                'posyanduPos.posPemeriksaan',
                'pemeriksaan',
            ])
            ->findOrFail($kunjunganPosId);

        $posyanduPos = $kunjunganPos->posyanduPos;

        if (!$posyanduPos || !$posyanduPos->status) {
            throw ValidationException::withMessages([
                'posyandu_pos_id' => 'Konfigurasi pos pemeriksaan tidak ditemukan atau tidak aktif.',
            ]);
        }

        $user = User::query()->findOrFail($userId);

        // Validasi hak akses Admin atau Kader.
        $this->cariPetugas($user, $kunjunganPos);

        $jenisPemeriksaan = JenisPemeriksaan::query()
            ->where(
                'pos_pemeriksaan_id',
                $posyanduPos->pos_pemeriksaan_id
            )
            ->where('status', true)
            ->orderBy('id')
            ->get();

        $hasilPemeriksaan = $kunjunganPos->pemeriksaan
            ->groupBy('jenis_pemeriksaan_id');

        $posPemeriksaan = $posyanduPos->posPemeriksaan;

        return [
            'kunjungan_pos' => [
                'id' => $kunjunganPos->id,
                'status' => $kunjunganPos->status,
                'catatan' => $kunjunganPos->catatan,
                'waktu_mulai' => $kunjunganPos->waktu_mulai,
                'waktu_selesai' => $kunjunganPos->waktu_selesai,
            ],

            'kunjungan' => [
                'id' => $kunjunganPos->kunjungan->id,
                'nomor_antrian' => $kunjunganPos->kunjungan->nomor_antrian,
                'warga' => [
                    'id' => $kunjunganPos->kunjungan->warga->id,
                    'nama' => $kunjunganPos->kunjungan->warga->nama,
                    'jenis_kelamin' => $kunjunganPos->kunjungan->warga->jenis_kelamin,
                    'tanggal_lahir' => $kunjunganPos->kunjungan->warga->tanggal_lahir,
                ],
            ],

            'pos_pemeriksaan' => [
                'id' => $posPemeriksaan->id,
                'posyandu_pos_id' => $posyanduPos->id,
                'kode' => $posPemeriksaan->kode,
                'nama' => $posPemeriksaan->nama,
                'urutan' => $posyanduPos->urutan,
            ],

            'jenis_pemeriksaan' => $jenisPemeriksaan
                ->map(function ($jenis) use ($hasilPemeriksaan) {
                    $hasil = $hasilPemeriksaan->get(
                        $jenis->id,
                        collect()
                    );

                    return [
                        'id' => $jenis->id,
                        'kode' => $jenis->kode,
                        'nama' => $jenis->nama,
                        'satuan' => $jenis->satuan,
                        'tipe_nilai' => $jenis->tipe_nilai,
                        'wajib' => $jenis->wajib,
                        'keterangan' => $jenis->keterangan,

                        'hasil' => $hasil->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'pengukuran_ke' => $item->pengukuran_ke,
                                'nilai_numerik' => $item->nilai_numerik,
                                'nilai_teks' => $item->nilai_teks,
                                'keterangan' => $item->keterangan,
                                'waktu_pemeriksaan' => $item->waktu_pemeriksaan,
                            ];
                        })->values(),
                    ];
                })
                ->values(),
        ];
    }

    /**
     * Memastikan petugas memiliki akses ke POS kunjungan.
     */
    private function cariPetugas(
        User $user,
        KunjunganPos $kunjunganPos
    ): int {
        $kegiatan = $kunjunganPos->kunjungan?->kegiatan;
        $posyanduPos = $kunjunganPos->posyanduPos;

        if (!$kegiatan || !$posyanduPos) {
            throw ValidationException::withMessages([
                'kunjungan_pos' => 'Data kunjungan atau konfigurasi pos tidak valid.',
            ]);
        }

        if (
            (int) $kegiatan->posyandu_id !==
            (int) $posyanduPos->posyandu_id
        ) {
            throw ValidationException::withMessages([
                'kunjungan_pos' => 'Pos pemeriksaan tidak sesuai dengan Posyandu kegiatan.',
            ]);
        }

        $assignment = $this->petugasContext->authorizePosPemeriksaan(
            $user,
            (int) $kegiatan->posyandu_id,
            (int) $posyanduPos->id
        );

        return (int) $assignment->id;
    }
}
