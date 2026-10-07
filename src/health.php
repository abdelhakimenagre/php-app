<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

// Used by Docker HEALTHCHECK and to verify a deployment
$storageOk = is_dir(dirname(DATA_FILE)) && is_writable(dirname(DATA_FILE));

header('Content-Type: application/json');
http_response_code($storageOk ? 200 : 503);

echo json_encode([
    'status'  => $storageOk ? 'ok' : 'degraded',
    'app'     => APP_NAME,
    'version' => APP_VERSION,
    'php'     => PHP_VERSION,
    'time'    => date('c'),
]);
