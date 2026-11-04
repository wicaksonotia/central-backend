
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pengaduan')
            ->table('complaint_responses', function (Blueprint $table) {
                $table->foreignId('assignment_id')
                    ->nullable()
                    ->after('complaint_id')
                    ->constrained('complaint_assignments')
                    ->nullOnDelete();
            });
    }

    public function down(): void
    {
        Schema::connection('pengaduan')
            ->table('complaint_responses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assignment_id');
            });
    }
};
