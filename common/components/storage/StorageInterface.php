<?php

declare(strict_types=1);

namespace common\components\storage;

interface StorageInterface
{
    public function delete(string $path): void;

    public function getAbsolutePath(string $path): string;

    public function getBaseUrl(): string;

    public function getStorageName(): string;

    public function getPublicUrl(string $path): string;

    public function has(string $path): bool;

    public function save(string $path, string $content): string;

    public function saveFile(string $path, string $sourcePath): string;
}
