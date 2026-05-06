<?php

declare(strict_types=1);

namespace AndroidForensicsLab\Support;

final class Json
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function respond(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

