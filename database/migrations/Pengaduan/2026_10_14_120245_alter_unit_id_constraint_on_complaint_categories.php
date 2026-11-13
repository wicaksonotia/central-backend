
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pengaduan';

    public function up(): void
    {
        // Pastikan semua kategori memiliki unit.
        $hasNullUnit = DB::connection('pengaduan')
            ->table('complaint_categories')
            ->whereNull('unit_id')
            ->exists();

        if ($hasNullUnit) {
            throw new RuntimeException(
                'Migration dibatalkan: masih ada kategori tanpa unit_id.'
            );
        }

        // Pastikan constraint lama dihapus.
        DB::connection('pengaduan')->statement(
            'ALTER TABLE public.complaint_categories
             DROP CONSTRAINT complaint_categories_unit_id_foreign'
        );

        // unit_id wajib diisi.
        DB::connection('pengaduan')->statement(
            'ALTER TABLE public.complaint_categories
             ALTER COLUMN unit_id SET NOT NULL'
        );

        // Unit tidak boleh dihapus jika masih memiliki kategori.
        DB::connection('pengaduan')->statement(
            'ALTER TABLE public.complaint_categories
             ADD CONSTRAINT complaint_categories_unit_id_foreign
             FOREIGN KEY (unit_id)
             REFERENCES public.units(id)
             ON DELETE RESTRICT'
        );
    }

    public function down(): void
    {
        // Hapus constraint baru.
        DB::connection('pengaduan')->statement(
            'ALTER TABLE public.complaint_categories
             DROP CONSTRAINT complaint_categories_unit_id_foreign'
        );

        // Kembalikan unit_id agar boleh NULL.
        DB::connection('pengaduan')->statement(
            'ALTER TABLE public.complaint_categories
             ALTER COLUMN unit_id DROP NOT NULL'
        );

        // Kembalikan perilaku foreign key sebelumnya.
        DB::connection('pengaduan')->statement(
            'ALTER TABLE public.complaint_categories
             ADD CONSTRAINT complaint_categories_unit_id_foreign
             FOREIGN KEY (unit_id)
             REFERENCES public.units(id)
             ON DELETE SET NULL'
        );
    }
};
