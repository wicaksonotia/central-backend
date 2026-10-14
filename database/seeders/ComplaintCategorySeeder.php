<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComplaintCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'nama' => 'Infrastruktur Jalan',
                'unit_id' => '1',
                'deskripsi' => 'Jalan rusak, berlubang, trotoar, dan fasilitas jalan.',
            ],
            [
                'nama' => 'Transportasi dan Lalu Lintas',
                'unit_id' => '2',
                'deskripsi' => 'Kemacetan, rambu lalu lintas, parkir, dan angkutan umum.',
            ],
            [
                'nama' => 'Lingkungan dan Kebersihan',
                'unit_id' => '1',
                'deskripsi' => 'Sampah, kebersihan lingkungan, dan pencemaran.',
            ],
            [
                'nama' => 'Drainase dan Banjir',
                'unit_id' => '1',
                'deskripsi' => 'Saluran air tersumbat, genangan, dan banjir.',
            ],
            [
                'nama' => 'Penerangan Jalan',
                'unit_id' => '2',
                'deskripsi' => 'Lampu penerangan jalan mati atau rusak.',
            ],
            [
                'nama' => 'Fasilitas Umum',
                'unit_id' => '1',
                'deskripsi' => 'Kerusakan taman, halte, dan fasilitas umum lainnya.',
            ],
            [
                'nama' => 'Pelayanan Publik',
                'unit_id' => '1',
                'deskripsi' => 'Keluhan terhadap pelayanan instansi pemerintah.',
            ],
            [
                'nama' => 'Ketertiban Umum',
                'unit_id' => '1',
                'deskripsi' => 'Gangguan ketertiban dan penggunaan fasilitas umum.',
            ],
            [
                'nama' => 'Lainnya',
                'unit_id' => '1',
                'deskripsi' => 'Pengaduan yang belum sesuai dengan kategori lainnya.',
            ],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug($category['nama']);

            ComplaintCategory::firstOrCreate(
                ['slug' => $slug],
                [
                    'nama' => $category['nama'],
                    'deskripsi' => $category['deskripsi'],
                ]
            );
        }
    }
}
