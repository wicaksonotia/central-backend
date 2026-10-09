
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $connection = 'pengaduan';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        /*
        |--------------------------------------------------------------------------
        | 1. Instansi
        |--------------------------------------------------------------------------
        */
        $schema->create('instansi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('singkatan', 50)->nullable();
            $table->string('alamat')->nullable();
            $table->string('email')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Unit organisasi
        |--------------------------------------------------------------------------
        */
        $schema->create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')
                ->constrained('instansi')
                ->restrictOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('alamat')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 3. Roles
        |--------------------------------------------------------------------------
        */
        $schema->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 4. Hak akses pengguna
        |--------------------------------------------------------------------------
        */
        $schema->create('user_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->unique(
                ['user_id', 'role_id', 'unit_id'],
                'user_roles_unique_assignment'
            );

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 5. Kategori pengaduan
        |--------------------------------------------------------------------------
        */
        $schema->create('complaint_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 6. Pengaduan utama
        |--------------------------------------------------------------------------
        */
        $schema->create('complaints', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_pengaduan', 50)->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->constrained('complaint_categories')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->restrictOnDelete();

            $table->string('judul');
            $table->text('isi_pengaduan');

            $table->string('status', 40)->default('diajukan');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('alamat')->nullable();

            $table->timestampTz('diajukan_pada')->nullable();
            $table->timestampTz('selesai_pada')->nullable();
            $table->timestampTz('ditutup_pada')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['unit_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        /*
        |--------------------------------------------------------------------------
        | 7. Foto pengaduan
        |--------------------------------------------------------------------------
        */
        $schema->create('complaint_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->cascadeOnDelete();

            $table->string('path');
            $table->string('nama_file')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 8. Penugasan petugas
        |--------------------------------------------------------------------------
        */
        $schema->create('complaint_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('catatan')->nullable();
            $table->timestampTz('ditugaskan_pada')->nullable();
            $table->timestampTz('selesai_pada')->nullable();
            $table->timestamps();

            $table->index(['complaint_id', 'user_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | 9. Tanggapan dan tindak lanjut
        |--------------------------------------------------------------------------
        */
        $schema->create('complaint_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('isi');
            $table->boolean('untuk_publik')->default(true);
            $table->timestamps();

            $table->index(['complaint_id', 'created_at']);
        });

        /*
        |--------------------------------------------------------------------------
        | 10. Verifikasi penyelesaian
        |--------------------------------------------------------------------------
        */
        $schema->create('complaint_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tahap', 30);
            // unit atau admin_utama

            $table->string('keputusan', 30);
            // disetujui atau ditolak

            $table->text('catatan')->nullable();
            $table->timestampTz('diverifikasi_pada')->nullable();
            $table->timestamps();

            $table->index(['complaint_id', 'tahap']);
        });

        /*
        |--------------------------------------------------------------------------
        | 11. Riwayat perubahan status
        |--------------------------------------------------------------------------
        */
        $schema->create('complaint_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained('complaints')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status_sebelumnya', 40)->nullable();
            $table->string('status_baru', 40);
            $table->text('catatan')->nullable();
            $table->timestampTz('berubah_pada')->useCurrent();
            $table->timestamps();

            $table->index(['complaint_id', 'berubah_pada']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->dropIfExists('complaint_status_histories');
        $schema->dropIfExists('complaint_verifications');
        $schema->dropIfExists('complaint_responses');
        $schema->dropIfExists('complaint_assignments');
        $schema->dropIfExists('complaint_photos');
        $schema->dropIfExists('complaints');
        $schema->dropIfExists('complaint_categories');
        $schema->dropIfExists('user_roles');
        $schema->dropIfExists('roles');
        $schema->dropIfExists('units');
        $schema->dropIfExists('instansi');
    }
};