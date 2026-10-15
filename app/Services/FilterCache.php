<?php

namespace App\Services;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FilterCache
{
    public const SECONDS = 30;

    private function directory(): string { return storage_path('framework/cache/filtered-results'); }

    public function revision(): string
    {
        $path = $this->directory().'/revision';
        if (! File::exists($path)) $this->invalidate();
        return trim(File::get($path));
    }

    public function invalidate(): void
    {
        try {
            File::ensureDirectoryExists($this->directory());
            File::replace($this->directory().'/revision', (string) Str::uuid());
        } catch (\Throwable $error) {
            Log::warning('Could not invalidate filtered results.', ['message' => $error->getMessage()]);
        }
    }

    public function remember(string $scope, array $filters, callable $load): mixed
    {
        // This store always uses local files, even when session/throttle caches use SQL.
        try {
            // Only this private store accepts server-created model/collection objects.
            // Do not change the application's default cache unserialization policy.
            $store = new Repository(new FileStore(app('files'), $this->directory().'/data', null, true));
            ksort($filters);
            $user = auth()->user();
            $connection = config('database.default');
            $key = hash('sha256', serialize([
                'v2', $scope, $filters, $this->revision(), request()->root(),
                $user?->id, $user?->role, app()->environment(), $connection,
                config('database.connections.'.$connection.'.database'),
                now()->toDateString(), app(DeviceClock::class)->now()->toDateString(),
            ]));
            $cached = $store->get($key);
            if (is_array($cached) && array_key_exists('result', $cached)) return $cached['result'];
        } catch (\Throwable $error) {
            Log::warning('Filter cache unavailable; reading current data.', ['message' => $error->getMessage()]);
            return $load();
        }
        $result = $load(); // Validation and database errors must propagate normally.
        try {
            $store->put($key, ['result' => $result], self::SECONDS);
            $this->prune();
        } catch (\Throwable $error) {
            Log::warning('Could not save filtered results.', ['message' => $error->getMessage()]);
        }
        return $result;
    }

    private function prune(): void
    {
        $marker = $this->directory().'/pruned';
        if (File::exists($marker) && File::lastModified($marker) > time() - 30) return;
        File::put($marker, '');
        $files = collect(File::allFiles($this->directory().'/data'))->sortByDesc(fn ($file) => $file->getMTime());
        foreach ($files->values() as $index => $file) {
            if ($index >= 128 || $file->getMTime() < time() - self::SECONDS) File::delete($file->getPathname());
        }
    }
}
