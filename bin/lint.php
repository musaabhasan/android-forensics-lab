<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    $root . DIRECTORY_SEPARATOR . 'bin',
    $root . DIRECTORY_SEPARATOR . 'config',
    $root . DIRECTORY_SEPARATOR . 'public',
    $root . DIRECTORY_SEPARATOR . 'src',
];

$files = [];
foreach ($paths as $path) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);
$checked = 0;
foreach ($files as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        echo implode(PHP_EOL, $output) . PHP_EOL;
        exit($exitCode);
    }
    $checked++;
}

echo 'Lint passed: ' . $checked . ' PHP files.' . PHP_EOL;

