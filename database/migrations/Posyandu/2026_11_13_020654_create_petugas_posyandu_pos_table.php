<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('petugas_posyandu_pos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('petugas_posyandu_id')
                ->constrained('petugas_posyandu')
                ->cascadeOnDelete();

            $table->foreignId('pos_pemeriksaan_id')
                ->constrained('pos_pemeriksaan')
                ->restrictOnDelete();

            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique([
                'petugas_posyandu_id',
                'pos_pemeriksaan_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('petugas_posyandu_pos');
    }
};