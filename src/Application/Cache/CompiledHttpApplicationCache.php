<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

use PCF\Addendum\Application\App;
use RuntimeException;

final readonly class CompiledHttpApplicationCache
{
    public function __construct(
        private ApplicationCacheConfiguration $configuration,
        private CompiledHttpApplicationGenerator $generator,
        private PhpFileWriter $writer
    ) {
    }

    public function warmup(): void
    {
        $this->writer->write($this->configuration->appFile(), $this->generator->generate());
    }

    public function load(): App
    {
        $filePath = $this->configuration->appFile();

        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Compiled HTTP app file "%s" does not exist', $filePath));
        }

        $factory = require $filePath;

        if (!is_callable($factory)) {
            throw new RuntimeException(sprintf('Compiled HTTP app file "%s" must return a callable', $filePath));
        }

        $app = $factory();

        if (!$app instanceof App) {
            throw new RuntimeException(sprintf('Compiled HTTP app file "%s" must return App', $filePath));
        }

        return $app;
    }
}
