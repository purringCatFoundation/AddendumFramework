<?php
declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode(
    [
        'status' => 'ok',
        'server' => 'frankenphp',
        'xdebug' => extension_loaded('xdebug'),
    ],
    JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
