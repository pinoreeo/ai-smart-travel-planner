<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where('email', 'admin@travelplanner.local')
            ->firstOrFail();

        $places = [
            [
                'data' => [
                    'name' => 'Lokawisata Baturraden',
                    'slug' => 'lokawisata-baturraden',
                    'short_description' => 'Destinasi wisata alam dan rekreasi keluarga di kawasan Baturraden.',
                    'description' => 'Lokawisata Baturraden menawarkan pemandangan alam, taman, area rekreasi, dan fasilitas keluarga.',
                    'address' => 'Karangmangu, Kecamatan Baturraden, Kabupaten Banyumas',
                    'city' => 'Banyumas',
                    'province' => 'Jawa Tengah',
                    'postal_code' => '53151',
                    'latitude' => -7.3132500,
                    'longitude' => 109.2295700,
                    'ticket_price' => 25000,
                    'parking_price_motorcycle' => 3000,
                    'parking_price_car' => 5000,
                    'recommended_duration_minutes' => 150,
                    'place_type' => 'mixed',
                    'phone' => null,
                    'website_url' => null,
                    'instagram_url' => null,
                    'status' => 'published',
                    'is_featured' => true,
                    'last_verified_at' => now(),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
                'categories' => ['alam', 'hiburan', 'keluarga'],
                'facilities' => [
                    'area-parkir',
                    'toilet',
                    'musala',
                    'restoran',
                    'area-bermain-anak',
                ],
                'opening_hours' => [
                    ['day_of_week' => 'monday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'tuesday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'wednesday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'thursday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'friday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'saturday', 'open_time' => '06:30', 'close_time' => '18:00'],
                    ['day_of_week' => 'sunday', 'open_time' => '06:30', 'close_time' => '18:00'],
                ],
                'image' => [
                    'image_url' => 'https://placehold.co/1200x800?text=Lokawisata+Baturraden',
                    'public_id' => null,
                    'caption' => 'Lokawisata Baturraden',
                    'alt_text' => 'Pemandangan Lokawisata Baturraden',
                    'is_primary' => true,
                    'sort_order' => 1,
                ],
            ],
            [
                'data' => [
                    'name' => 'Curug Bayan',
                    'slug' => 'curug-bayan',
                    'short_description' => 'Air terjun alami yang cocok untuk wisata santai dan fotografi.',
                    'description' => 'Curug Bayan merupakan wisata air terjun di kawasan Baturraden dengan suasana alam yang sejuk.',
                    'address' => 'Ketenger, Kecamatan Baturraden, Kabupaten Banyumas',
                    'city' => 'Banyumas',
                    'province' => 'Jawa Tengah',
                    'postal_code' => null,
                    'latitude' => -7.3268500,
                    'longitude' => 109.2213500,
                    'ticket_price' => 15000,
                    'parking_price_motorcycle' => 3000,
                    'parking_price_car' => 5000,
                    'recommended_duration_minutes' => 120,
                    'place_type' => 'outdoor',
                    'phone' => null,
                    'website_url' => null,
                    'instagram_url' => null,
                    'status' => 'published',
                    'is_featured' => true,
                    'last_verified_at' => now(),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
                'categories' => ['alam'],
                'facilities' => [
                    'area-parkir',
                    'toilet',
                    'musala',
                ],
                'opening_hours' => [
                    ['day_of_week' => 'monday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'tuesday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'wednesday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'thursday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'friday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'saturday', 'open_time' => '07:00', 'close_time' => '17:00'],
                    ['day_of_week' => 'sunday', 'open_time' => '07:00', 'close_time' => '17:00'],
                ],
                'image' => [
                    'image_url' => 'https://placehold.co/1200x800?text=Curug+Bayan',
                    'public_id' => null,
                    'caption' => 'Curug Bayan',
                    'alt_text' => 'Pemandangan air terjun Curug Bayan',
                    'is_primary' => true,
                    'sort_order' => 1,
                ],
            ],
            [
                'data' => [
                    'name' => 'Museum Bank Rakyat Indonesia',
                    'slug' => 'museum-bank-rakyat-indonesia',
                    'short_description' => 'Museum sejarah perbankan yang berada di Purwokerto.',
                    'description' => 'Museum Bank Rakyat Indonesia menyimpan koleksi sejarah perkembangan perbankan di Indonesia.',
                    'address' => 'Jalan Jenderal Sudirman, Purwokerto',
                    'city' => 'Purwokerto',
                    'province' => 'Jawa Tengah',
                    'postal_code' => null,
                    'latitude' => -7.4246500,
                    'longitude' => 109.2317900,
                    'ticket_price' => 0,
                    'parking_price_motorcycle' => 2000,
                    'parking_price_car' => 5000,
                    'recommended_duration_minutes' => 90,
                    'place_type' => 'indoor',
                    'phone' => null,
                    'website_url' => null,
                    'instagram_url' => null,
                    'status' => 'published',
                    'is_featured' => false,
                    'last_verified_at' => now(),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
                'categories' => ['sejarah', 'keluarga'],
                'facilities' => [
                    'area-parkir',
                    'toilet',
                ],
                'opening_hours' => [
                    ['day_of_week' => 'monday', 'open_time' => '08:00', 'close_time' => '15:00'],
                    ['day_of_week' => 'tuesday', 'open_time' => '08:00', 'close_time' => '15:00'],
                    ['day_of_week' => 'wednesday', 'open_time' => '08:00', 'close_time' => '15:00'],
                    ['day_of_week' => 'thursday', 'open_time' => '08:00', 'close_time' => '15:00'],
                    ['day_of_week' => 'friday', 'open_time' => '08:00', 'close_time' => '14:00'],
                    [
                        'day_of_week' => 'saturday',
                        'open_time' => null,
                        'close_time' => null,
                        'is_closed' => true,
                        'notes' => 'Tutup',
                    ],
                    [
                        'day_of_week' => 'sunday',
                        'open_time' => null,
                        'close_time' => null,
                        'is_closed' => true,
                        'notes' => 'Tutup',
                    ],
                ],
                'image' => [
                    'image_url' => 'https://placehold.co/1200x800?text=Museum+BRI',
                    'public_id' => null,
                    'caption' => 'Museum Bank Rakyat Indonesia',
                    'alt_text' => 'Bangunan Museum Bank Rakyat Indonesia',
                    'is_primary' => true,
                    'sort_order' => 1,
                ],
            ],
            [
                'data' => [
                    'name' => 'Menara Pandang Teratai',
                    'slug' => 'menara-pandang-teratai',
                    'short_description' => 'Menara pandang ikonik untuk melihat panorama Kota Purwokerto.',
                    'description' => 'Menara Pandang Teratai merupakan destinasi rekreasi dengan pemandangan kota dari ketinggian.',
                    'address' => 'Kedungwuluh, Purwokerto Barat',
                    'city' => 'Purwokerto',
                    'province' => 'Jawa Tengah',
                    'postal_code' => null,
                    'latitude' => -7.4275800,
                    'longitude' => 109.2207600,
                    'ticket_price' => 20000,
                    'parking_price_motorcycle' => 3000,
                    'parking_price_car' => 5000,
                    'recommended_duration_minutes' => 90,
                    'place_type' => 'mixed',
                    'phone' => null,
                    'website_url' => null,
                    'instagram_url' => null,
                    'status' => 'published',
                    'is_featured' => true,
                    'last_verified_at' => now(),
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ],
                'categories' => ['hiburan', 'keluarga'],
                'facilities' => [
                    'area-parkir',
                    'toilet',
                    'musala',
                    'restoran',
                ],
                'opening_hours' => [
                    ['day_of_week' => 'monday', 'open_time' => '09:00', 'close_time' => '22:00'],
                    ['day_of_week' => 'tuesday', 'open_time' => '09:00', 'close_time' => '22:00'],
                    ['day_of_week' => 'wednesday', 'open_time' => '09:00', 'close_time' => '22:00'],
                    ['day_of_week' => 'thursday', 'open_time' => '09:00', 'close_time' => '22:00'],
                    ['day_of_week' => 'friday', 'open_time' => '09:00', 'close_time' => '22:00'],
                    ['day_of_week' => 'saturday', 'open_time' => '09:00', 'close_time' => '22:00'],
                    ['day_of_week' => 'sunday', 'open_time' => '09:00', 'close_time' => '22:00'],
                ],
                'image' => [
                    'image_url' => 'https://placehold.co/1200x800?text=Menara+Pandang+Teratai',
                    'public_id' => null,
                    'caption' => 'Menara Pandang Teratai',
                    'alt_text' => 'Menara Pandang Teratai Purwokerto',
                    'is_primary' => true,
                    'sort_order' => 1,
                ],
            ],
        ];

        foreach ($places as $placeData) {
            $place = Place::updateOrCreate(
                [
                    'slug' => $placeData['data']['slug'],
                ],
                $placeData['data']
            );

            $categoryIds = Category::query()
                ->whereIn('slug', $placeData['categories'])
                ->pluck('id')
                ->all();

            $place->categories()->sync($categoryIds);

            $facilityIds = Facility::query()
                ->whereIn('slug', $placeData['facilities'])
                ->pluck('id')
                ->all();

            $place->facilities()->sync($facilityIds);

            foreach ($placeData['opening_hours'] as $openingHour) {
                $place->openingHours()->updateOrCreate(
                    [
                        'day_of_week' => $openingHour['day_of_week'],
                    ],
                    [
                        'open_time' => $openingHour['open_time'],
                        'close_time' => $openingHour['close_time'],
                        'is_closed' => $openingHour['is_closed'] ?? false,
                        'notes' => $openingHour['notes'] ?? null,
                    ]
                );
            }

            $place->images()->updateOrCreate(
                [
                    'sort_order' => $placeData['image']['sort_order'],
                ],
                [
                    ...$placeData['image'],
                    'uploaded_by' => $admin->id,
                ]
            );
        }
    }
}
