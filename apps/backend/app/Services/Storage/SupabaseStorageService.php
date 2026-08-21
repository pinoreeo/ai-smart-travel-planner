<?php

namespace App\Services\Storage;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorageService
{
    public function uploadImage(UploadedFile $file, string $directory = 'places'): array
    {
        $url = rtrim((string) config('services.supabase.url'), '/');
        $key = (string) config('services.supabase.service_role_key');
        $bucket = (string) config('services.supabase.storage_bucket', 'place-images');

        if ($url === '' || $key === '' || $bucket === '') {
            throw new RuntimeException('Supabase storage is not configured.');
        }

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $path = trim($directory, '/').'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        $endpoint = "{$url}/storage/v1/object/{$bucket}/{$path}";

        try {
            Http::withToken($key)
                ->withHeaders([
                    'apikey' => $key,
                    'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
                    'x-upsert' => 'false',
                ])
                ->withBody($file->get(), $file->getMimeType() ?: 'application/octet-stream')
                ->post($endpoint)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            $message = $exception instanceof RequestException
                ? ($exception->response?->json('message') ?? $exception->response?->body() ?? 'Supabase upload failed.')
                : 'Could not connect to Supabase Storage.';

            throw new RuntimeException($message, previous: $exception);
        }

        return [
            'provider' => 'supabase',
            'bucket' => $bucket,
            'path' => $path,
            'public_id' => $path,
            'image_url' => "{$url}/storage/v1/object/public/{$bucket}/{$path}",
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
