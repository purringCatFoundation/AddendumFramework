<?php
declare(strict_types=1);

$devActionDir = __DIR__ . '/Action';

if (!is_dir($devActionDir)) {
    return;
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($devActionDir));

foreach ($files as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    require_once $file->getPathname();
}
