<?php

namespace Database\Seeders\Pengaduan;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MainAdminSeeder::class,
            InstansiSeeder::class,
            UnitSeeder::class,
            ComplaintCategorySeeder::class,
            UserSeeder::class,
            UserRoleSeeder::class,
        ]);
    }
}
