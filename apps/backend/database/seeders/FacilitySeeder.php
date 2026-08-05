<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            [
                'name' => 'Area Parkir',
                'slug' => 'area-parkir',
                'icon' => 'parking',
                'description' => 'Tersedia area parkir kendaraan.',
                'is_active' => true,
            ],
            [
                'name' => 'Toilet',
                'slug' => 'toilet',
                'icon' => 'restroom',
                'description' => 'Tersedia toilet umum.',
                'is_active' => true,
            ],
            [
                'name' => 'Musala',
                'slug' => 'musala',
                'icon' => 'mosque',
                'description' => 'Tersedia tempat ibadah.',
                'is_active' => true,
            ],
            [
                'name' => 'Restoran',
                'slug' => 'restoran',
                'icon' => 'utensils',
                'description' => 'Tersedia tempat makan atau restoran.',
                'is_active' => true,
            ],
            [
                'name' => 'Area Bermain Anak',
                'slug' => 'area-bermain-anak',
                'icon' => 'child',
                'description' => 'Tersedia area bermain untuk anak-anak.',
                'is_active' => true,
            ],
            [
                'name' => 'Akses Disabilitas',
                'slug' => 'akses-disabilitas',
                'icon' => 'wheelchair',
                'description' => 'Tersedia fasilitas atau akses untuk pengunjung disabilitas.',
                'is_active' => true,
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::updateOrCreate(
                ['slug' => $facility['slug']],
                $facility
            );
        }
    }
}
