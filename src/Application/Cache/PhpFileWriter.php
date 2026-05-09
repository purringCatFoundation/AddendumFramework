<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

use RuntimeException;

final readonly class PhpFileWriter
{
    public function write(string $filePath, string $contents): void
    {
        $directory = dirname($filePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Cannot create cache directory "%s"', $directory));
        }

        $temporaryFile = tempnam($directory, '.tmp-');

        if ($temporaryFile === false) {
            throw new RuntimeException(sprintf('Cannot create temporary cache file in "%s"', $directory));
        }

        if (file_put_contents($temporaryFile, $contents) === false) {
            @unlink($temporaryFile);
            throw new RuntimeException(sprintf('Cannot write cache file "%s"', $filePath));
        }

        if (!rename($temporaryFile, $filePath)) {
            @unlink($temporaryFile);
            throw new RuntimeException(sprintf('Cannot move cache file into "%s"', $filePath));
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($filePath, true);
        }
    }
}
