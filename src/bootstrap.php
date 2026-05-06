<?php

declare(strict_types=1);

use AndroidForensicsLab\Support\Env;

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'AndroidForensicsLab\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Env::load($root . DIRECTORY_SEPARATOR . '.env');

$sessionName = Env::get('SESSION_NAME', 'android_forensics_lab');
session_name($sessionName);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

return [
    'root' => $root,
    'catalog' => require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'catalog.php',
];

