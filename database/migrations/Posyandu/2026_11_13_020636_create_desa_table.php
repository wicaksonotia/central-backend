<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('desa', function (Blueprint $table) {
            $table->id();
            $table->string('kode_desa', 30)->unique();
            $table->string('nama_desa', 150);
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('desa');
    }
};