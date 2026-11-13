<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tipe_nilai nantinya dapat berisi:
     * - numeric untuk berat badan, tinggi badan, tensi, dan gula darah.
     * - text untuk catatan hasil.
     * - boolean untuk hasil ya/tidak.
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('jenis_pemeriksaan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pos_pemeriksaan_id')
                ->constrained('pos_pemeriksaan')
                ->restrictOnDelete();

            $table->string('kode', 50)->unique();
            $table->string('nama', 150);

            $table->string('satuan', 30)->nullable();

            $table->string('tipe_nilai', 20)->default('numeric');

            $table->text('keterangan')->nullable();

            $table->boolean('wajib')->default(false);
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->index(['pos_pemeriksaan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('jenis_pemeriksaan');
    }
};