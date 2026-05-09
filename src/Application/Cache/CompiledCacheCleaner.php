<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

final readonly class CompiledCacheCleaner
{
    /**
     * @return list<string>
     */
    public function cleanup(ApplicationCacheConfiguration $configuration): array
    {
        $removed = [];

        foreach (['routes.php', 'metadata.php', 'app.php'] as $fileName) {
            $filePath = $configuration->compiledDirectory . '/' . $fileName;

            if (is_file($filePath)) {
                unlink($filePath);
                $removed[] = $filePath;
            }
        }

        return $removed;
    }
}
