<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pengaduan';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)
            ->table('user_roles', function (Blueprint $table) {
                $table->boolean('is_active')
                    ->default(true);

                $table->timestamp('deactivated_at')
                    ->nullable();
            });

        // Satu user hanya boleh memiliki satu role aktif.
        DB::connection($this->connection)->statement("
            CREATE UNIQUE INDEX user_roles_one_active_per_user
            ON user_roles (user_id)
            WHERE is_active = TRUE
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection($this->connection)->statement("
            DROP INDEX IF EXISTS user_roles_one_active_per_user
        ");

        Schema::connection($this->connection)
            ->table('user_roles', function (Blueprint $table) {
                $table->dropColumn([
                    'is_active',
                    'deactivated_at',
                ]);
            });
    }
};
