<?php

namespace App\Services\PlaceImports;

use Illuminate\Support\Facades\Http;

class OverpassPlaceImportService
{
    public function __construct(
        private readonly PlaceImportNormalizer $normalizer
    ) {}

    public function preview(string $province = 'Jawa Tengah', int $limit = 50): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->post(config('services.overpass.endpoint'), [
                'data' => $this->query($province, $limit),
            ]);

        $response->throw();

        $items = collect($response->json('elements', []))
            ->map(fn (array $element): ?array => $this->normalizer->normalizeOsmElement($element, $province))
            ->filter()
            ->values()
            ->all();

        return $this->normalizer->uniqueBySourceId($items);
    }

    private function query(string $province, int $limit): string
    {
        return <<<OVERPASS
[out:json][timeout:30];
area["name"="{$province}"]["boundary"="administrative"]["admin_level"="4"]->.searchArea;
(
  nwr["tourism"~"^(attraction|museum|viewpoint|zoo|theme_park|gallery)$"](area.searchArea);
  nwr["historic"](area.searchArea);
  nwr["natural"~"^(waterfall|peak|beach|cave_entrance)$"](area.searchArea);
);
out center tags {$limit};
OVERPASS;
    }
}
