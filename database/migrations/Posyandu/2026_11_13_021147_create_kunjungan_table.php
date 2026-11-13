<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penting untuk antrean: nomor antrean dibuat ketika pendaftaran. 
     * Nanti proses penomorannya menggunakan database transaction dan penguncian baris kegiatan agar dua perangkat pendaftaran tidak menghasilkan nomor yang sama.
     * Nomor antrean ini tidak akan berubah meskipun urutan pemeriksaan di setiap pos berubah.
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('kunjungan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kegiatan_id')
                ->constrained('kegiatan_posyandu')
                ->restrictOnDelete();

            $table->foreignId('warga_id')
                ->constrained('warga')
                ->restrictOnDelete();

            $table->unsignedInteger('nomor_antrian');

            $table->timestamp('waktu_daftar')->useCurrent();

            $table->string('status', 20)->default('berlangsung');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['kegiatan_id', 'warga_id']);
            $table->unique(['kegiatan_id', 'nomor_antrian']);

            $table->index(['kegiatan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('kunjungan');
    }
};