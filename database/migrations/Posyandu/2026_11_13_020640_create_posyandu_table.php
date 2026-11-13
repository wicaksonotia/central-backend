<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('posyandu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('desa_id')
                ->constrained('desa')
                ->restrictOnDelete();

            $table->string('kode_posyandu', 30);
            $table->string('nama_posyandu', 150);
            $table->text('alamat')->nullable();
            $table->string('nama_ketua', 150)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['desa_id', 'kode_posyandu']);
            $table->index(['desa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('posyandu');
    }
};