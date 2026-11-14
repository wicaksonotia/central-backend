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
        Schema::connection('posyandu')->table(
            'kunjungan_pos',
            function (Blueprint $table) {
                $table->foreignId('posyandu_pos_id')
                    ->nullable()
                    ->after('pos_pemeriksaan_id')
                    ->constrained('posyandu_pos')
                    ->restrictOnDelete();
            }
        );

        // Petakan antrean lama berdasarkan Posyandu dari kegiatan.
        DB::connection('posyandu')->statement("
            UPDATE kunjungan_pos AS kp
            SET posyandu_pos_id = pp.id
            FROM kunjungan AS k,
                 kegiatan_posyandu AS kg,
                 posyandu_pos AS pp
            WHERE k.id = kp.kunjungan_id
              AND kg.id = k.kegiatan_id
              AND pp.posyandu_id = kg.posyandu_id
              AND pp.pos_pemeriksaan_id = kp.pos_pemeriksaan_id
        ");

        $unmapped = DB::connection('posyandu')
            ->table('kunjungan_pos')
            ->whereNull('posyandu_pos_id')
            ->exists();

        if ($unmapped) {
            throw new RuntimeException(
                'Ada antrean yang belum terhubung ke konfigurasi pos Posyandu.'
            );
        }

        Schema::connection('posyandu')->table(
            'kunjungan_pos',
            function (Blueprint $table) {
                $table->dropUnique(
                    ['kunjungan_id', 'pos_pemeriksaan_id']
                );

                $table->unique(
                    ['kunjungan_id', 'posyandu_pos_id'],
                    'kunjungan_pos_config_unique'
                );

                $table->index('posyandu_pos_id');

                $table->foreignId('posyandu_pos_id')
                    ->nullable(false)
                    ->change();
            }
        );
    }

    public function down(): void
    {
        Schema::connection('posyandu')->table(
            'kunjungan_pos',
            function (Blueprint $table) {
                $table->dropUnique('kunjungan_pos_config_unique');

                $table->dropIndex(['posyandu_pos_id']);

                $table->dropConstrainedForeignId('posyandu_pos_id');

                $table->unique(
                    ['kunjungan_id', 'pos_pemeriksaan_id']
                );
            }
        );
    }
};
