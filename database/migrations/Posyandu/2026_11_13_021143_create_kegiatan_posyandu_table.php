<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status kegiatan:
     * - draft
     * - berlangsung
     * - selesai
     * - dibatalkan
     */
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('kegiatan_posyandu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('posyandu_id')
                ->constrained('posyandu')
                ->restrictOnDelete();

            $table->string('nama_kegiatan', 150);
            $table->date('tanggal_kegiatan');

            $table->string('status', 20)->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['posyandu_id', 'tanggal_kegiatan']);
            $table->index(['posyandu_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('kegiatan_posyandu');
    }
};