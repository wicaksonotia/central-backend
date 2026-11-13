// CARA MENJALANKAN SEEDER
php artisan db:seed --class="Database\\Seeders\\Posyandu\\DatabaseSeeder" --database=posyandu

// CARA MEMBUAT SEEDER
php artisan make:seeder Posyandu/PosPemeriksaanSeeder
php artisan make:seeder Posyandu/KelompokSasaranSeeder
php artisan make:seeder Posyandu/JenisPemeriksaanSeeder
php artisan make:seeder Posyandu/MasterKeluhanSeeder
php artisan make:seeder Posyandu/PosyanduSeeder