<?php
declare(strict_types=1);

define('APP_NAME', 'TaskFlow');
define('APP_VERSION', getenv('APP_VERSION') ?: 'dev');
define('DATA_FILE', getenv('DATA_FILE') ?: '/var/www/data/tasks.json');

// Load classes from src/lib automatically (Task -> lib/Task.php)
spl_autoload_register(function (string $class): void {
    $path = __DIR__ . '/lib/' . $class . '.php';
    if (is_file($path)) {
        require $path;
    }
});

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
