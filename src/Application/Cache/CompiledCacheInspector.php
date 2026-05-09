<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

use Throwable;

final readonly class CompiledCacheInspector
{
    /**
     * @return array<string, mixed>
     */
    public function inspect(ApplicationCacheConfiguration $configuration): array
    {
        return [
            'mode' => $configuration->mode->value,
            'environment' => $configuration->environment,
            'compiledDirectory' => $configuration->compiledDirectory,
            'routes' => $this->fileStatus($configuration->routesFile(), true),
            'metadata' => $this->fileStatus($configuration->metadataFile(), false),
            'app' => $this->fileStatus($configuration->appFile(), true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileStatus(string $filePath, bool $load): array
    {
        $status = [
            'path' => $filePath,
            'exists' => is_file($filePath),
            'readable' => is_readable($filePath),
            'valid' => false,
            'error' => null,
        ];

        if (!$status['exists'] || !$status['readable']) {
            return $status;
        }

        try {
            if ($load) {
                $factory = require $filePath;
                $status['valid'] = is_callable($factory);
            } else {
                $metadata = require $filePath;
                $status['valid'] = is_array($metadata);
                $status['data'] = is_array($metadata) ? $metadata : null;
            }
        } catch (Throwable $exception) {
            $status['error'] = $exception->getMessage();
        }

        return $status;
    }
}
