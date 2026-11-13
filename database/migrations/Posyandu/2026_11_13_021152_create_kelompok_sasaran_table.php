<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contoh kelompok yang nantinya dapat kita masukkan:
     * - Bayi
     * - Balita
     * - Anak prasekolah
     * - Anak usia sekolah
     * - Remaja
     * - Dewasa
     * - Lansia
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('kelompok_sasaran', function (Blueprint $table) {
            $table->id();

            $table->string('kode', 30)->unique();
            $table->string('nama', 100);

            $table->unsignedInteger('usia_min_bulan')->default(0);
            $table->unsignedInteger('usia_max_bulan')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('kelompok_sasaran');
    }
};