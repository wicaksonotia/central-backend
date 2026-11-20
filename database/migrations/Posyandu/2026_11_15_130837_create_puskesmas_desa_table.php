<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('posyandu')->create('puskesmas_desa', function (Blueprint $table) {
            $table->id();

            $table->foreignId('puskesmas_id')
                ->constrained('puskesmas')
                ->restrictOnDelete();

            $table->foreignId('desa_id')
                ->constrained('desa')
                ->restrictOnDelete();

            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                ['puskesmas_id', 'desa_id'],
                'puskesmas_desa_unique'
            );

            $table->index(
                ['puskesmas_id', 'status'],
                'puskesmas_desa_puskesmas_status_idx'
            );

            $table->index(
                ['desa_id', 'status'],
                'puskesmas_desa_desa_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::connection('posyandu')->dropIfExists('puskesmas_desa');
    }
};