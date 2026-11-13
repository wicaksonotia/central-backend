<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemeriksaan	Ke-	Hasil
     * Tensi	1	150/90
     * Tensi	2	140/90
     * Berat badan	1	65 kg
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('pemeriksaan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kunjungan_pos_id')
                ->constrained('kunjungan_pos')
                ->cascadeOnDelete();

            $table->foreignId('jenis_pemeriksaan_id')
                ->constrained('jenis_pemeriksaan')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('pengukuran_ke')->default(1);

            $table->decimal('nilai_numerik', 12, 3)->nullable();
            $table->text('nilai_teks')->nullable();

            $table->text('keterangan')->nullable();

            $table->foreignId('petugas_posyandu_id')
                ->nullable()
                ->constrained('petugas_posyandu')
                ->nullOnDelete();

            $table->timestamp('waktu_pemeriksaan')->nullable();

            $table->timestamps();

            $table->unique([
                'kunjungan_pos_id',
                'jenis_pemeriksaan_id',
                'pengukuran_ke',
            ]);

            $table->index([
                'jenis_pemeriksaan_id',
                'waktu_pemeriksaan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('pemeriksaan');
    }
};