<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('users');
    }
};