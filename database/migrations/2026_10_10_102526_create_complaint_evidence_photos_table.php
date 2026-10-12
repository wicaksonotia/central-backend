<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pengaduan';

    public function up(): void
    {
        Schema::connection('pengaduan')
            ->create('complaint_evidence_photos', function (Blueprint $table) {
                $table->id();

                $table->foreignId('complaint_id')
                    ->constrained('complaints')
                    ->cascadeOnDelete();

                $table->foreignId('assignment_id')
                    ->constrained('complaint_assignments')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('path');
                $table->string('nama_file');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('ukuran');

                $table->timestamps();

                $table->index(['complaint_id', 'assignment_id']);
            });
    }

    public function down(): void
    {
        Schema::connection('pengaduan')
            ->dropIfExists('complaint_evidence_photos');
    }
};
