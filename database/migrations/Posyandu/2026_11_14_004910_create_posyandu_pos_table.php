<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection('posyandu')->create('posyandu_pos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('posyandu_id')
                ->constrained('posyandu')
                ->restrictOnDelete();

            $table->foreignId('pos_pemeriksaan_id')
                ->constrained('pos_pemeriksaan')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(
                ['posyandu_id', 'pos_pemeriksaan_id'],
                'posyandu_pos_unique'
            );

            $table->index(['posyandu_id', 'status']);
        });

        // Salin konfigurasi awal master ke setiap Posyandu.
        $posyanduList = DB::connection('posyandu')
            ->table('posyandu')
            ->pluck('id');

        $posList = DB::connection('posyandu')
            ->table('pos_pemeriksaan')
            ->get(['id', 'urutan', 'status']);

        $now = now();
        $data = [];

        foreach ($posyanduList as $posyanduId) {
            foreach ($posList as $pos) {
                $data[] = [
                    'posyandu_id' => $posyanduId,
                    'pos_pemeriksaan_id' => $pos->id,
                    'urutan' => $pos->urutan,
                    'status' => $pos->status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($data)) {
            DB::connection('posyandu')
                ->table('posyandu_pos')
                ->insert($data);
        }
    }

    public function down(): void
    {
        Schema::connection('posyandu')
            ->dropIfExists('posyandu_pos');
    }
};
