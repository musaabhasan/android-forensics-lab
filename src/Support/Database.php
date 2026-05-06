<?php

declare(strict_types=1);

namespace AndroidForensicsLab\Support;

use PDO;
use Throwable;

final class Database
{
    public static function connection(): ?PDO
    {
        $dsn = Env::get('DB_DSN');
        if ($dsn === null || $dsn === '') {
            return null;
        }

        try {
            $pdo = new PDO($dsn, Env::get('DB_USER', ''), Env::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;
        } catch (Throwable) {
            return null;
        }
    }
}

