php artisan make:seeder Posyandu/PuskesmasUserSeeder
// CARA MENJALANKAN SEEDER
php artisan db:seed --class="Database\\Seeders\\Posyandu\\DatabaseSeeder" --database=posyandu

// CARA MEMBUAT SEEDER
php artisan make:seeder Posyandu/PosPemeriksaanSeeder
php artisan make:seeder Posyandu/KelompokSasaranSeeder
php artisan make:seeder Posyandu/JenisPemeriksaanSeeder
php artisan make:seeder Posyandu/MasterKeluhanSeeder
php artisan make:seeder Posyandu/DesaSeeder
php artisan make:seeder Posyandu/PosyanduSeeder
php artisan make:seeder Posyandu/AdminPosyanduSeeder
php artisan make:seeder Posyandu/PetugasKaderSeeder
php artisan make:seeder Posyandu/PuskesmasSeeder
php artisan make:seeder Posyandu/PuskesmasDesaSeeder

php artisan db:seed \
  --database=posyandu \
  --class="Database\\Seeders\\Posyandu\\PosyanduSeeder"