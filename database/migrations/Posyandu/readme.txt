// CARA JALANKAN MIGRATE
php artisan migrate --database=posyandu --path=database/migrations/Posyandu

// ================================================
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT tablename
        FROM pg_tables
        WHERE schemaname = 'public'
          AND tablename <> 'migrations'
    LOOP
        EXECUTE format(
            'TRUNCATE TABLE public.%I RESTART IDENTITY CASCADE',
            r.tablename
        );
    END LOOP;
END $$;

php artisan migrate:fresh --database=pengaduan
// ================================================

// CARA BIKIN MIGRATION
php artisan make:migration create_users_table --path=database/migrations/Posyandu
php artisan make:migration create_personal_access_tokens_table --path=database/migrations/Posyandu
php artisan make:migration create_desa_table --path=database/migrations/Posyandu
php artisan make:migration create_posyandu_table --path=database/migrations/Posyandu
php artisan make:migration create_pos_pemeriksaan_table --path=database/migrations/Posyandu
php artisan make:migration create_petugas_posyandu_table --path=database/migrations/Posyandu
php artisan make:migration create_petugas_posyandu_pos_table --path=database/migrations/Posyandu

php artisan make:migration create_warga_table --path=database/migrations/Posyandu
php artisan make:migration create_kegiatan_posyandu_table --path=database/migrations/Posyandu
php artisan make:migration create_kunjungan_table --path=database/migrations/Posyandu
php artisan make:migration create_kelompok_sasaran_table --path=database/migrations/Posyandu
php artisan make:migration create_jenis_pemeriksaan_table --path=database/migrations/Posyandu
php artisan make:migration create_jenis_pemeriksaan_kelompok_table --path=database/migrations/Posyandu

php artisan make:migration create_kunjungan_pos_table --path=database/migrations/Posyandu
php artisan make:migration create_pemeriksaan_table --path=database/migrations/Posyandu
php artisan make:migration create_master_keluhan_table --path=database/migrations/Posyandu
php artisan make:migration create_pemeriksaan_keluhan_table --path=database/migrations/Posyandu