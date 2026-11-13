<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status antrean:
     * Status	:
     * waiting	-> Menunggu pemeriksaan
     * in_progress	-> Sedang diperiksa
     * completed	-> Pemeriksaan selesai
     * skipped	-> Dilewati sementara
     * cancelled	-> Antrean dibatalkan
     * Jika warga yang skipped ingin dipanggil kembali, statusnya dikembalikan menjadi waiting. 
     * Nomor pendaftaran tidak berubah.
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('kunjungan_pos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kunjungan_id')
                ->constrained('kunjungan')
                ->cascadeOnDelete();

            $table->foreignId('pos_pemeriksaan_id')
                ->constrained('pos_pemeriksaan')
                ->restrictOnDelete();

            $table->foreignId('petugas_posyandu_id')
                ->nullable()
                ->constrained('petugas_posyandu')
                ->nullOnDelete();

            $table->string('status', 20)->default('waiting');

            // Urutan pemanggilan lokal pada masing-masing pos.
            $table->unsignedInteger('urutan_panggil')->nullable();

            $table->timestamp('waktu_masuk')->nullable();
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique([
                'kunjungan_id',
                'pos_pemeriksaan_id',
            ]);

            $table->index([
                'pos_pemeriksaan_id',
                'status',
                'urutan_panggil',
            ]);

            $table->index([
                'petugas_posyandu_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('kunjungan_pos');
    }
};