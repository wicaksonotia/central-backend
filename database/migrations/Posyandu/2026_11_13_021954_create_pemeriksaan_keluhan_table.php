<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('pemeriksaan_keluhan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kunjungan_pos_id')
                ->constrained('kunjungan_pos')
                ->cascadeOnDelete();

            $table->foreignId('master_keluhan_id')
                ->nullable()
                ->constrained('master_keluhan')
                ->restrictOnDelete();

            $table->text('keluhan_lainnya')->nullable();
            $table->text('catatan')->nullable();

            $table->foreignId('petugas_posyandu_id')
                ->nullable()
                ->constrained('petugas_posyandu')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('kunjungan_pos_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('pemeriksaan_keluhan');
    }
};