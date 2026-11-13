<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'posyandu';

    public function up(): void
    {
        Schema::connection($this->connection)->create('pos_pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->text('keterangan')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('pos_pemeriksaan');
    }
};