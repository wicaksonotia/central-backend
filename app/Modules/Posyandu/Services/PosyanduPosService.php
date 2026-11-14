<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\PosPemeriksaan;
use App\Modules\Posyandu\Models\PosyanduPos;
use App\Modules\Posyandu\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosyanduPosService
{
    public function __construct(
        protected PetugasContextService $petugasContext
    ) {}

    /**
     * Daftar konfigurasi pos milik Admin.
     */
    public function daftar(User $admin)
    {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($admin);

        return PosyanduPos::with('posPemeriksaan')
            ->where('posyandu_id', $posyanduId)
            ->orderBy('urutan')
            ->get();
    }

    /**
     * Simpan urutan dan status pos.
     */
    public function simpan(User $admin, array $data)
    {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($admin);

        return DB::connection('posyandu')->transaction(
            function () use ($posyanduId, $data) {

                $pos = collect($data['pos']);

                $ids = $pos->pluck('posyandu_pos_id');

                if ($ids->unique()->count() !== $ids->count()) {
                    throw ValidationException::withMessages([
                        'pos' => 'Pos pemeriksaan tidak boleh duplikat.',
                    ]);
                }

                $konfigurasi = PosyanduPos::where(
                    'posyandu_id',
                    $posyanduId
                )
                    ->whereIn('id', $ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($konfigurasi->count() !== $ids->count()) {
                    throw ValidationException::withMessages([
                        'pos' => 'Terdapat pos pemeriksaan yang tidak valid.',
                    ]);
                }

                foreach ($pos as $item) {
                    $konfigurasi[$item['posyandu_pos_id']]->update([
                        'urutan' => $item['urutan'],
                        'status' => $item['status'],
                    ]);
                }

                return PosyanduPos::with('posPemeriksaan')
                    ->where('posyandu_id', $posyanduId)
                    ->orderBy('urutan')
                    ->get();
            }
        );
    }
}
