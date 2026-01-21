<?php

declare(strict_types=1);

namespace Webard\Biloquent\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;

class DriverDetector
{
    public const DRIVER_MYSQL = 'mysql';

    public const DRIVER_MARIADB = 'mariadb';

    public const DRIVER_PGSQL = 'pgsql';

    public const DRIVER_SQLITE = 'sqlite';

    /**
     * Detect the database driver from a connection.
     */
    public static function detect(Connection $connection): string
    {
        return match (true) {
            $connection instanceof MySqlConnection => self::detectMySqlVariant($connection),
            $connection instanceof PostgresConnection => self::DRIVER_PGSQL,
            $connection instanceof SQLiteConnection => self::DRIVER_SQLITE,
            default => $connection->getDriverName(),
        };
    }

    /**
     * Check if the connection is MySQL (not MariaDB).
     */
    public static function isMySql(Connection $connection): bool
    {
        return self::detect($connection) === self::DRIVER_MYSQL;
    }

    /**
     * Check if the connection is MariaDB.
     */
    public static function isMariaDb(Connection $connection): bool
    {
        return self::detect($connection) === self::DRIVER_MARIADB;
    }

    /**
     * Check if the connection is PostgreSQL.
     */
    public static function isPostgres(Connection $connection): bool
    {
        return self::detect($connection) === self::DRIVER_PGSQL;
    }

    /**
     * Check if the connection is SQLite.
     */
    public static function isSqlite(Connection $connection): bool
    {
        return self::detect($connection) === self::DRIVER_SQLITE;
    }

    /**
     * Detect if MySQL connection is actually MariaDB.
     */
    protected static function detectMySqlVariant(MySqlConnection $connection): string
    {
        // Laravel 12+ has isMaria() method
        if (method_exists($connection, 'isMaria') && $connection->isMaria()) {
            return self::DRIVER_MARIADB;
        }

        // Fallback: check version string
        try {
            $version = $connection->selectOne('SELECT VERSION() as version')?->version ?? '';

            if (stripos($version, 'mariadb') !== false) {
                return self::DRIVER_MARIADB;
            }
        } catch (\Throwable) {
            // Ignore errors and assume MySQL
        }

        return self::DRIVER_MYSQL;
    }
}
