<?php
/** Crea conexiones PDO a partir de la configuración única del proyecto. */
final class Database
{
    public static function connect(array $settings, bool $withDatabase = true): PDO
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('La extensión pdo_mysql no está habilitada en PHP.');
        }

        $dsn = 'mysql:host=' . $settings['host'] . ';port=' . (int) $settings['port'];
        if ($withDatabase) {
            $dsn .= ';dbname=' . $settings['name'];
        }
        $dsn .= ';charset=utf8mb4';

        return new PDO($dsn, $settings['user'], $settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
