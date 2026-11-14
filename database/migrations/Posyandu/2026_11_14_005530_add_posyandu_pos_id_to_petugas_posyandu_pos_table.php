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
            'petugas_posyandu_pos',
            function (Blueprint $table) {
                $table->foreignId('posyandu_pos_id')
                    ->nullable()
                    ->after('pos_pemeriksaan_id')
                    ->constrained('posyandu_pos')
                    ->restrictOnDelete();
            }
        );

        // Hubungkan penugasan lama ke konfigurasi pos Posyandu terkait.
        DB::connection('posyandu')->statement("
            UPDATE petugas_posyandu_pos AS ppp
            SET posyandu_pos_id = pp.id
            FROM petugas_posyandu AS p,
                 posyandu_pos AS pp
            WHERE p.id = ppp.petugas_posyandu_id
              AND pp.posyandu_id = p.posyandu_id
              AND pp.pos_pemeriksaan_id = ppp.pos_pemeriksaan_id
        ");

        // Pastikan semua penugasan lama berhasil dipetakan.
        $unmapped = DB::connection('posyandu')
            ->table('petugas_posyandu_pos')
            ->whereNull('posyandu_pos_id')
            ->exists();

        if ($unmapped) {
            throw new RuntimeException(
                'Ada penugasan kader yang belum terhubung ke konfigurasi pos Posyandu.'
            );
        }

        Schema::connection('posyandu')->table(
            'petugas_posyandu_pos',
            function (Blueprint $table) {
                $table->dropUnique(
                    ['petugas_posyandu_id', 'pos_pemeriksaan_id']
                );

                $table->unique(
                    ['petugas_posyandu_id', 'posyandu_pos_id'],
                    'petugas_posyandu_pos_assignment_unique'
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
            'petugas_posyandu_pos',
            function (Blueprint $table) {
                $table->dropUnique(
                    'petugas_posyandu_pos_assignment_unique'
                );

                $table->dropIndex(['posyandu_pos_id']);

                $table->dropConstrainedForeignId('posyandu_pos_id');

                $table->unique(
                    ['petugas_posyandu_id', 'pos_pemeriksaan_id']
                );
            }
        );
    }
};
