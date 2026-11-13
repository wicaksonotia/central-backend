<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pengaduan')->create(
            'berita_pengumuman',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('instansi_id')
                    ->constrained('instansi')
                    ->restrictOnDelete();

                $table->enum('jenis', [
                    'berita',
                    'pengumuman',
                ]);

                $table->string('judul', 200);
                $table->string('slug', 230);
                $table->string('ringkasan', 500)->nullable();
                $table->longText('konten');

                $table->string('gambar')->nullable();

                $table->enum('status', [
                    'draft',
                    'published',
                    'archived',
                ])->default('draft');

                $table->timestamp('published_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['instansi_id', 'slug'],
                    'berita_instansi_slug_unique'
                );

                $table->index(
                    ['instansi_id', 'jenis', 'status'],
                    'berita_instansi_jenis_status_index'
                );

                $table->index('published_at');
            }
        );
    }

    public function down(): void
    {
        Schema::connection('pengaduan')
            ->dropIfExists('berita_pengumuman');
    }
};
