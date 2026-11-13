<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('jenis_pemeriksaan_kelompok', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jenis_pemeriksaan_id')
                ->constrained('jenis_pemeriksaan')
                ->cascadeOnDelete();

            $table->foreignId('kelompok_sasaran_id')
                ->constrained('kelompok_sasaran')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'jenis_pemeriksaan_id',
                'kelompok_sasaran_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('jenis_pemeriksaan_kelompok');
    }
};