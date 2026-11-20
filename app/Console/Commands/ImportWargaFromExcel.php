<?php

namespace App\Console\Commands;

use App\Modules\Posyandu\Models\Warga;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportWargaFromExcel extends Command
{
    /**
     * no_kk, alamat, dan no_hp tidak diubah karena tidak tersedia di Excel.
     * status otomatis diisi true.
     * Jika NIK sudah ada pada Posyandu ID 2, datanya diperbarui.
     * Jika NIK sama tetapi berada di Posyandu lain, data tersebut tidak disentuh.
     * Jika ada NIK duplikat dalam file, baris duplikat dilaporkan sebagai gagal.
     * Jika ada satu saja data tidak valid, transaksi dibatalkan seluruhnya.
     */
    
    protected $signature = 'posyandu:import-warga
                            {file : Lokasi file Excel}
                            {--posyandu=2 : ID Posyandu tujuan}
                            {--dry-run : Simulasi tanpa menyimpan data}';

    protected $description = 'Import data warga dari file Excel';

    public function handle(): int
    {
        $file = $this->argument('file');
        $posyanduId = (int) $this->option('posyandu');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        if ($posyanduId <= 0) {
            $this->error('ID Posyandu tidak valid.');
            return self::FAILURE;
        }

        $connection = DB::connection('posyandu');

        if (!$connection->table('posyandu')->where('id', $posyanduId)->exists()) {
            $this->error("Posyandu dengan ID {$posyanduId} tidak ditemukan.");
            return self::FAILURE;
        }

        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray(null, true, true, false);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            $seenNik = [];

            $this->info("Import warga ke Posyandu ID: {$posyanduId}");

            if ($dryRun) {
                $this->warn('Mode simulasi aktif. Database tidak akan diubah.');
            }

            $connection->beginTransaction();

            try {
                foreach ($rows as $index => $row) {
                    $rowNumber = $index + 1;

                    // Lewati baris kosong.
                    if (count(array_filter(
                        $row,
                        fn($value) => $value !== null && trim((string) $value) !== ''
                    )) === 0) {
                        $skipped++;
                        continue;
                    }

                    $nik = preg_replace('/\s+/', '', (string) ($row[0] ?? ''));
                    $nama = trim((string) ($row[1] ?? ''));
                    $tanggalLahirRaw = $row[2] ?? null;
                    $jenisKelaminRaw = strtoupper(trim((string) ($row[3] ?? '')));

                    // Validasi NIK.
                    if (!preg_match('/^[0-9]{16}$/', $nik)) {
                        $errors[] = "Baris {$rowNumber}: NIK harus 16 digit.";
                        continue;
                    }

                    // Cegah NIK ganda di file yang sama.
                    if (isset($seenNik[$nik])) {
                        $errors[] = "Baris {$rowNumber}: NIK duplikat di file (baris {$seenNik[$nik]}).";
                        continue;
                    }

                    $seenNik[$nik] = $rowNumber;

                    if ($nama === '') {
                        $errors[] = "Baris {$rowNumber}: Nama wajib diisi.";
                        continue;
                    }

                    // Konversi jenis kelamin.
                    $jenisKelamin = match ($jenisKelaminRaw) {
                        'LAKI-LAKI', 'LAKI LAKI', 'L', 'PRIA' => 'L',
                        'PEREMPUAN', 'WANITA', 'P' => 'P',
                        default => null,
                    };

                    if ($jenisKelamin === null) {
                        $errors[] = "Baris {$rowNumber}: Jenis kelamin tidak dikenali.";
                        continue;
                    }

                    // Konversi tanggal lahir.
                    try {
                        if (is_numeric($tanggalLahirRaw)) {
                            $tanggalLahir = Carbon::instance(
                                ExcelDate::excelToDateTimeObject((float) $tanggalLahirRaw)
                            )->startOfDay();
                        } else {
                            $tanggalLahir = Carbon::createFromFormat(
                                '!d-m-Y',
                                trim((string) $tanggalLahirRaw)
                            );
                        }

                        if (
                            !$tanggalLahir || $tanggalLahir->format('d-m-Y') !==
                            (is_numeric($tanggalLahirRaw)
                                ? $tanggalLahir->format('d-m-Y')
                                : trim((string) $tanggalLahirRaw))
                        ) {
                            throw new \RuntimeException('Format tanggal tidak valid.');
                        }
                    } catch (\Throwable) {
                        $errors[] = "Baris {$rowNumber}: Tanggal lahir tidak valid.";
                        continue;
                    }

                    $warga = Warga::query()
                        ->where('posyandu_id', $posyanduId)
                        ->where('nik', $nik)
                        ->first();

                    $isNew = !$warga;

                    if (!$warga) {
                        $warga = new Warga();
                    }

                    $warga->forceFill([
                        'posyandu_id' => $posyanduId,
                        'nik' => $nik,
                        'nama' => $nama,
                        'tanggal_lahir' => $tanggalLahir->toDateString(),
                        'jenis_kelamin' => $jenisKelamin,
                        'status' => true,
                    ]);

                    if (!$dryRun) {
                        $warga->save();
                    }

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }

                if ($dryRun || count($errors) > 0) {
                    $connection->rollBack();
                } else {
                    $connection->commit();
                }
            } catch (\Throwable $e) {
                $connection->rollBack();
                throw $e;
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $this->newLine();
            $this->table(
                ['Keterangan', 'Jumlah'],
                [
                    ['Data baru', $created],
                    ['Data diperbarui', $updated],
                    ['Baris kosong', $skipped],
                    ['Data gagal', count($errors)],
                ]
            );

            if ($errors) {
                $this->newLine();
                $this->warn('Detail data yang gagal:');

                foreach ($errors as $error) {
                    $this->line("- {$error}");
                }
            }

            if ($dryRun) {
                $this->warn('Simulasi selesai. Tidak ada data yang disimpan.');
            } elseif (count($errors) > 0) {
                $this->warn('Import dibatalkan karena terdapat data yang tidak valid.');
            } else {
                $this->info('Import warga berhasil.');
            }

            return count($errors) > 0 ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Import gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
