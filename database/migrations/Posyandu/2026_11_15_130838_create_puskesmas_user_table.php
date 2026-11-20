<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('posyandu')->create('puskesmas_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('puskesmas_id')
                ->constrained('puskesmas')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('peran', 30)->default('admin_puskesmas');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                ['puskesmas_id', 'user_id'],
                'puskesmas_user_unique'
            );

            $table->index(
                ['puskesmas_id', 'status'],
                'puskesmas_user_puskesmas_status_idx'
            );

            $table->index(
                ['user_id', 'status'],
                'puskesmas_user_user_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::connection('posyandu')->dropIfExists('puskesmas_user');
    }
};