
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pengaduan')->statement(
            'ALTER TABLE complaints ALTER COLUMN category_id DROP NOT NULL'
        );

        DB::connection('pengaduan')->statement(
            'ALTER TABLE complaints ALTER COLUMN unit_id DROP NOT NULL'
        );
    }

    public function down(): void
    {
        $connection = DB::connection('pengaduan');

        $hasNullValues = $connection->table('complaints')
            ->whereNull('category_id')
            ->orWhereNull('unit_id')
            ->exists();

        if ($hasNullValues) {
            throw new \RuntimeException(
                'Tidak dapat membatalkan migration: masih ada pengaduan tanpa kategori atau unit.'
            );
        }

        $connection->statement(
            'ALTER TABLE complaints ALTER COLUMN category_id SET NOT NULL'
        );

        $connection->statement(
            'ALTER TABLE complaints ALTER COLUMN unit_id SET NOT NULL'
        );
    }
};