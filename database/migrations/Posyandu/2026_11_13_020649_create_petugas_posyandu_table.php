<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('petugas_posyandu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('posyandu_id')
                ->constrained('posyandu')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('peran', 30)->default('kader');
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['posyandu_id', 'user_id']);
            $table->index(['posyandu_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('petugas_posyandu');
    }
};