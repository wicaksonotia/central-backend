<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan:
     * - NIK tidak diwajibkan karena bayi atau anak tertentu mungkin belum memiliki NIK.
     * - NIK belum dibuat unique agar data warga yang belum lengkap tidak menghambat pendaftaran.
     * - Jenis kelamin menggunakan L atau P, yang akan kita validasi di Laravel.
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('warga', function (Blueprint $table) {
            $table->id();

            $table->foreignId('posyandu_id')
                ->constrained('posyandu')
                ->restrictOnDelete();

            $table->string('no_kk', 16)->nullable();
            $table->string('nik', 16)->nullable();

            $table->string('nama', 150);
            $table->string('jenis_kelamin', 1);
            $table->date('tanggal_lahir');

            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index(['posyandu_id', 'nama']);
            $table->index('tanggal_lahir');
            $table->index('nik');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('warga');
    }
};