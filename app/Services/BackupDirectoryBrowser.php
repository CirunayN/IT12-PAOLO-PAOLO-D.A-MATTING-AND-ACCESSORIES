<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Throwable;

class BackupDirectoryBrowser
{
    public function browse(?string $path = null): array
    {
        if ($path === null || $path === '') {
            $roots = PHP_OS_FAMILY === 'Windows'
                ? array_map(fn ($letter) => $letter.':\\', range('A', 'Z'))
                : ['/'];

            return [
                'path' => null,
                'parent' => null,
                'can_select' => false,
                'folders' => array_values(array_map(
                    fn ($root) => ['name' => $root, 'path' => $root],
                    array_filter($roots, fn ($root) => is_dir($root) && is_readable($root))
                )),
            ];
        }

        $folder = $this->resolve($path);
        try {
            $directories = File::directories($folder);
        } catch (Throwable) {
            throw new InvalidArgumentException('This folder cannot be opened. Choose another folder.');
        }
        $directories = array_values(array_filter(
            $directories,
            fn ($directory) => is_dir($directory) && is_readable($directory)
        ));
        $parent = dirname($folder);

        return [
            'path' => $folder,
            'parent' => $parent === $folder || $parent === '.' ? null : $parent,
            'can_select' => is_writable($folder),
            'folders' => array_map(fn ($directory) => [
                'name' => basename(str_replace('\\', '/', $directory)),
                'path' => $directory,
            ], $directories),
        ];
    }

    public function select(string $path): string
    {
        $folder = $this->resolve($path);
        if (! is_writable($folder)) {
            throw new InvalidArgumentException('Backups cannot be saved in this folder. Choose a writable folder.');
        }

        return $folder;
    }

    private function resolve(string $path): string
    {
        $absolute = PHP_OS_FAMILY === 'Windows'
            ? preg_match('/^[a-zA-Z]:[\\\\\/]/', $path)
            : str_starts_with($path, '/');
        $folder = $absolute ? realpath($path) : false;
        if ($folder === false || ! is_dir($folder) || ! is_readable($folder)) {
            throw new InvalidArgumentException('This folder is unavailable. Choose an existing folder.');
        }

        return $folder;
    }
}
