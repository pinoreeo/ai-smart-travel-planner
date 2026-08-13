<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Alam',
                'slug' => 'alam',
                'description' => 'Wisata alam seperti air terjun, pegunungan, taman, dan pemandangan terbuka.',
                'icon' => 'leaf',
                'is_active' => true,
            ],
            [
                'name' => 'Kuliner',
                'slug' => 'kuliner',
                'description' => 'Tempat makan, pusat kuliner, dan destinasi wisata berbasis makanan.',
                'icon' => 'utensils',
                'is_active' => true,
            ],
            [
                'name' => 'Sejarah',
                'slug' => 'sejarah',
                'description' => 'Museum, monumen, situs sejarah, dan tempat bernilai budaya.',
                'icon' => 'landmark',
                'is_active' => true,
            ],
            [
                'name' => 'Hiburan',
                'slug' => 'hiburan',
                'description' => 'Tempat rekreasi, taman hiburan, dan destinasi santai.',
                'icon' => 'ticket',
                'is_active' => true,
            ],
            [
                'name' => 'Keluarga',
                'slug' => 'keluarga',
                'description' => 'Tempat wisata yang cocok dikunjungi bersama keluarga dan anak-anak.',
                'icon' => 'users',
                'is_active' => true,
            ],
            [
                'name' => 'Budaya',
                'slug' => 'budaya',
                'description' => 'Destinasi yang menonjolkan seni, tradisi, dan kehidupan lokal.',
                'icon' => 'palette',
                'is_active' => true,
            ],
            [
                'name' => 'Petualangan',
                'slug' => 'petualangan',
                'description' => 'Aktivitas wisata yang cocok untuk eksplorasi aktif dan pengalaman outdoor.',
                'icon' => 'mountain',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
