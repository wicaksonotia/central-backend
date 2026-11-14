<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['petugas_posyandu_pos', 'kunjungan_pos'] as $table) {
            DB::statement("
                DO \$\$
                DECLARE
                    item RECORD;
                BEGIN
                    FOR item IN
                        SELECT DISTINCT c.conname
                        FROM pg_constraint c
                        JOIN pg_class t ON t.oid = c.conrelid
                        JOIN pg_namespace n ON n.oid = t.relnamespace
                        JOIN unnest(c.conkey) AS k(attnum) ON true
                        JOIN pg_attribute a
                            ON a.attrelid = t.oid
                            AND a.attnum = k.attnum
                        WHERE n.nspname = 'public'
                          AND t.relname = '{$table}'
                          AND c.contype IN ('f', 'u')
                          AND a.attname = 'pos_pemeriksaan_id'
                    LOOP
                        EXECUTE format(
                            'ALTER TABLE public.%I DROP CONSTRAINT %I',
                            '{$table}',
                            item.conname
                        );
                    END LOOP;
                END
                \$\$;
            ");

            DB::statement("
                ALTER TABLE public.{$table}
                DROP COLUMN IF EXISTS pos_pemeriksaan_id CASCADE
            ");
        }
    }

    public function down(): void
    {
        // Kolom lama tidak dipulihkan karena identitas pos
        // sekarang menggunakan posyandu_pos_id.
    }
};
