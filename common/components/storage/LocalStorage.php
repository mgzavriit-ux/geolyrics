<?php

declare(strict_types=1);

namespace common\components\storage;

use RuntimeException;
use yii\base\Component;
use yii\helpers\FileHelper;

final class LocalStorage extends Component implements StorageInterface
{
    public string $basePath = '';
    public string $baseUrl = '/uploads';

    public function delete(string $path): void
    {
        $absolutePath = $this->getAbsolutePath($path);

        if (is_file($absolutePath) === false) {
            return;
        }

        unlink($absolutePath);
    }

    public function getAbsolutePath(string $path): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->normalizePath($path);
    }

    public function getPublicUrl(string $path): string
    {
        $segments = array_map(
            static fn (string $segment): string => rawurlencode($segment),
            explode('/', $this->normalizePath($path)),
        );

        return rtrim($this->baseUrl, '/') . '/' . implode('/', $segments);
    }

    public function has(string $path): bool
    {
        return is_file($this->getAbsolutePath($path));
    }

    public function save(string $path, string $content): string
    {
        $absolutePath = $this->getAbsolutePath($path);
        $this->createDirectory(dirname($absolutePath));

        if (file_put_contents($absolutePath, $content) === false) {
            throw new RuntimeException('Failed to save file into local storage.');
        }

        return $path;
    }

    public function saveFile(string $path, string $sourcePath): string
    {
        $absolutePath = $this->getAbsolutePath($path);
        $this->createDirectory(dirname($absolutePath));

        if (copy($sourcePath, $absolutePath) === false) {
            throw new RuntimeException('Failed to copy file into local storage.');
        }

        return $path;
    }

    private function createDirectory(string $directoryPath): void
    {
        if (is_dir($directoryPath) === true) {
            return;
        }

        FileHelper::createDirectory($directoryPath);
    }

    private function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
