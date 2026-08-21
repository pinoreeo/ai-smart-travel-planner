<?php

namespace App\Services\PlaceImports;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PlaceImportNormalizer
{
    public function normalizeManual(array $item, string $defaultSource = 'manual'): array
    {
        $name = trim((string) ($item['name'] ?? ''));
        $slug = $this->slug($item['slug'] ?? $name);

        return $this->withQualityWarnings([
            'source' => $item['source'] ?? $defaultSource,
            'source_id' => $item['source_id'] ?? null,
            'source_url' => $item['source_url'] ?? null,
            'name' => $name,
            'slug' => $slug,
            'short_description' => $item['short_description'] ?? null,
            'description' => $item['description'] ?? null,
            'address' => $item['address'] ?? null,
            'city' => $item['city'] ?? null,
            'province' => $item['province'] ?? null,
            'postal_code' => $item['postal_code'] ?? null,
            'latitude' => $item['latitude'] ?? null,
            'longitude' => $item['longitude'] ?? null,
            'ticket_price' => $item['ticket_price'] ?? null,
            'parking_price_motorcycle' => $item['parking_price_motorcycle'] ?? null,
            'parking_price_car' => $item['parking_price_car'] ?? null,
            'recommended_duration_minutes' => $item['recommended_duration_minutes'] ?? null,
            'place_type' => $item['place_type'] ?? null,
            'phone' => $item['phone'] ?? null,
            'website_url' => $item['website_url'] ?? null,
            'instagram_url' => $item['instagram_url'] ?? null,
            'image_url' => $item['image_url'] ?? null,
            'category_slugs' => $this->cleanList($item['category_slugs'] ?? []),
            'facility_slugs' => $this->cleanList($item['facility_slugs'] ?? []),
            'opening_hours' => $item['opening_hours'] ?? null,
            'raw_payload' => $item['raw_payload'] ?? $item,
        ]);
    }

    public function normalizeOsmElement(array $element, string $province): ?array
    {
        $tags = $element['tags'] ?? [];
        $name = trim((string) ($tags['name'] ?? $tags['name:id'] ?? ''));

        if ($name === '') {
            return null;
        }

        $latitude = $element['lat'] ?? Arr::get($element, 'center.lat');
        $longitude = $element['lon'] ?? Arr::get($element, 'center.lon');
        $type = $element['type'] ?? 'node';
        $id = $element['id'] ?? null;

        return $this->withQualityWarnings([
            'source' => 'osm',
            'source_id' => $id !== null ? "{$type}/{$id}" : null,
            'source_url' => $id !== null ? "https://www.openstreetmap.org/{$type}/{$id}" : null,
            'name' => $name,
            'slug' => $this->slug($name),
            'short_description' => $this->shortDescription($tags),
            'description' => $tags['description'] ?? $tags['description:id'] ?? null,
            'address' => $this->address($tags),
            'city' => $tags['addr:city'] ?? $tags['addr:subdistrict'] ?? $tags['addr:district'] ?? null,
            'province' => $province,
            'postal_code' => $tags['addr:postcode'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ticket_price' => null,
            'parking_price_motorcycle' => null,
            'parking_price_car' => null,
            'recommended_duration_minutes' => null,
            'place_type' => $this->placeType($tags),
            'phone' => $tags['phone'] ?? $tags['contact:phone'] ?? null,
            'website_url' => $tags['website'] ?? $tags['contact:website'] ?? null,
            'instagram_url' => $tags['contact:instagram'] ?? null,
            'image_url' => $this->imageUrl($tags),
            'category_slugs' => $this->categorySlugs($tags),
            'facility_slugs' => $this->facilitySlugs($tags),
            'opening_hours' => $this->openingHours($tags),
            'raw_payload' => $element,
        ]);
    }

    public function uniqueBySourceId(array $items): array
    {
        $seen = [];

        return array_values(array_filter($items, function (array $item) use (&$seen): bool {
            $key = ($item['source'] ?? '').':'.($item['source_id'] ?? $item['slug'] ?? Str::random());

            if (array_key_exists($key, $seen)) {
                return false;
            }

            $seen[$key] = true;

            return true;
        }));
    }

    private function withQualityWarnings(array $item): array
    {
        $warnings = [];

        foreach (['name', 'slug'] as $field) {
            if (empty($item[$field])) {
                $warnings[] = "{$field} kosong";
            }
        }

        if (empty($item['latitude']) || empty($item['longitude'])) {
            $warnings[] = 'koordinat belum lengkap';
        }

        if (empty($item['address'])) {
            $warnings[] = 'alamat belum lengkap';
        }

        if (empty($item['category_slugs'])) {
            $warnings[] = 'kategori perlu dicek admin';
        }

        $item['quality_warnings'] = $warnings;

        return $item;
    }

    private function shortDescription(array $tags): ?string
    {
        if (! empty($tags['description']) || ! empty($tags['description:id'])) {
            return Str::limit($tags['description:id'] ?? $tags['description'], 255);
        }

        if (! empty($tags['tourism'])) {
            return 'Calon destinasi wisata dari OpenStreetMap.';
        }

        if (! empty($tags['historic'])) {
            return 'Calon destinasi sejarah dari OpenStreetMap.';
        }

        if (! empty($tags['natural'])) {
            return 'Calon destinasi alam dari OpenStreetMap.';
        }

        return null;
    }

    private function address(array $tags): ?string
    {
        if (! empty($tags['addr:full'])) {
            return $tags['addr:full'];
        }

        $parts = array_filter([
            $tags['addr:street'] ?? null,
            $tags['addr:subdistrict'] ?? null,
            $tags['addr:district'] ?? null,
            $tags['addr:city'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function placeType(array $tags): string
    {
        if (in_array($tags['tourism'] ?? null, ['museum', 'gallery'], true)) {
            return 'indoor';
        }

        if (in_array($tags['tourism'] ?? null, ['theme_park', 'zoo'], true)) {
            return 'mixed';
        }

        return 'outdoor';
    }

    private function categorySlugs(array $tags): array
    {
        $slugs = [];

        if (! empty($tags['natural']) || in_array($tags['tourism'] ?? null, ['viewpoint', 'attraction'], true)) {
            $slugs[] = 'alam';
        }

        if (! empty($tags['historic']) || in_array($tags['tourism'] ?? null, ['museum'], true)) {
            $slugs[] = 'sejarah';
        }

        if (in_array($tags['tourism'] ?? null, ['gallery'], true)) {
            $slugs[] = 'budaya';
        }

        if (in_array($tags['tourism'] ?? null, ['theme_park'], true)) {
            $slugs[] = 'hiburan';
        }

        if (in_array($tags['tourism'] ?? null, ['zoo', 'theme_park'], true)) {
            $slugs[] = 'keluarga';
        }

        if (in_array($tags['natural'] ?? null, ['peak', 'cave_entrance', 'waterfall'], true)) {
            $slugs[] = 'petualangan';
        }

        return array_values(array_unique($slugs));
    }

    private function facilitySlugs(array $tags): array
    {
        $slugs = [];

        if (($tags['parking'] ?? null) !== null) {
            $slugs[] = 'area-parkir';
        }

        if (($tags['toilets'] ?? null) === 'yes' || ($tags['amenity'] ?? null) === 'toilets') {
            $slugs[] = 'toilet';
        }

        if (($tags['wheelchair'] ?? null) === 'yes') {
            $slugs[] = 'akses-disabilitas';
        }

        return array_values(array_unique($slugs));
    }

    private function openingHours(array $tags): ?array
    {
        if (empty($tags['opening_hours'])) {
            return null;
        }

        return [
            [
                'raw' => $tags['opening_hours'],
                'notes' => 'Perlu dicek admin karena format masih dari OSM.',
            ],
        ];
    }

    private function imageUrl(array $tags): ?string
    {
        $image = $tags['image'] ?? $tags['wikimedia_commons'] ?? null;

        return is_string($image) && filter_var($image, FILTER_VALIDATE_URL) ? $image : null;
    }

    private function cleanList(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => Str::slug((string) $value),
            $values
        ))));
    }

    private function slug(mixed $value): string
    {
        return Str::slug((string) $value);
    }
}
