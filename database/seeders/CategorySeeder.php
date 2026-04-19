<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    // Jalankan seeder database
    public function run(): void
    {
        $categories = [
            // Kategori Utama
            [
                'name' => 'Kerusakan Fasilitas Umum',
                'slug' => 'kerusakan-fasilitas-umum',
                'children' => [
                    ['name' => 'Kursi Rusak', 'slug' => 'kursi-rusak'],
                    ['name' => 'Meja Patah', 'slug' => 'meja-patah'],
                    ['name' => 'Pintu/Jendela Bermasalah', 'slug' => 'pintu-jendela-bermasalah'],
                ],
            ],
            [
                'name' => 'Kelistrikan',
                'slug' => 'kelistrikan',
                'children' => [
                    ['name' => 'Lampu Mati', 'slug' => 'lampu-mati'],
                    ['name' => 'Stop Kontak Rusak', 'slug' => 'stop-kontak-rusak'],
                    ['name' => 'Kabel Terbuka', 'slug' => 'kabel-terbuka'],
                ],
            ],
            [
                'name' => 'Air & Sanitasi',
                'slug' => 'air-sanitasi',
                'children' => [
                    ['name' => 'Keran Bocor', 'slug' => 'keran-bocor'],
                    ['name' => 'Toilet Rusak', 'slug' => 'toilet-rusak'],
                    ['name' => 'Saluran Mampet', 'slug' => 'saluran-mampet'],
                ],
            ],
            [
                'name' => 'Kebersihan',
                'slug' => 'kebersihan',
                'children' => [
                    ['name' => 'Sampah Menumpuk', 'slug' => 'sampah-menumpuk'],
                    ['name' => 'Area Kotor', 'slug' => 'area-kotor'],
                    ['name' => 'Bau Tidak Sedap', 'slug' => 'bau-tidak-sedap'],
                ],
            ],
            [
                'name' => 'Infrastruktur',
                'slug' => 'infrastruktur',
                'children' => [
                    ['name' => 'Jalan Retak', 'slug' => 'jalan-retak'],
                    ['name' => 'Atap Bocor', 'slug' => 'atap-bocor'],
                    ['name' => 'Plafon Rusak', 'slug' => 'plafon-rusak'],
                ],
            ],

            // Kategori Tambahan
            [
                'name' => 'Keamanan',
                'slug' => 'keamanan',
                'children' => [
                    ['name' => 'CCTV Rusak', 'slug' => 'cctv-rusak'],
                    ['name' => 'Pagar/Pengaman Rusak', 'slug' => 'pagar-pengaman-rusak'],
                ],
            ],
            [
                'name' => 'Peralatan Elektronik',
                'slug' => 'peralatan-elektronik',
                'children' => [
                    ['name' => 'AC Tidak Dingin', 'slug' => 'ac-tidak-dingin'],
                    ['name' => 'Proyektor Rusak', 'slug' => 'proyektor-rusak'],
                    ['name' => 'Komputer Bermasalah', 'slug' => 'komputer-bermasalah'],
                ],
            ],
            [
                'name' => 'Lingkungan',
                'slug' => 'lingkungan',
                'children' => [
                    ['name' => 'Pohon Tumbang', 'slug' => 'pohon-tumbang'],
                    ['name' => 'Drainase Buruk', 'slug' => 'drainase-buruk'],
                ],
            ],

            // Kategori Lainnya
            [
                'name' => 'Lainnya',
                'slug' => 'lainnya',
                'children' => [],
            ],
        ];

        foreach ($categories as $catData) {
            $children = $catData['children'] ?? [];
            unset($catData['children']);

            $parent = Category::updateOrCreate(
                ['slug' => $catData['slug']],
                ['name' => $catData['name']]
            );

            foreach ($children as $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    ['name' => $child['name'], 'parent_id' => $parent->id]
                );
            }
        }
    }
}