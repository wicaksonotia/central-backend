<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('posyandu')->create('puskesmas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_puskesmas', 30)->unique();
            $table->string('nama_puskesmas', 150);
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('posyandu')->dropIfExists('puskesmas');
    }
};