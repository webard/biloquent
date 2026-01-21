<?php

declare(strict_types=1);

namespace Webard\Biloquent\Exceptions;

use RuntimeException;

class UnsupportedAggregatorException extends RuntimeException
{
    /**
     * Create exception for unsupported driver.
     */
    public static function forDriver(string $aggregator, string $driver, ?string $hint = null): self
    {
        $message = "The '{$aggregator}' aggregator is not supported on {$driver}.";

        if ($hint !== null) {
            $message .= " {$hint}";
        }

        return new self($message);
    }

    /**
     * Create exception for MySQL requiring UDF Infusion.
     */
    public static function requiresUdfInfusion(string $aggregator): self
    {
        return new self(
            "The '{$aggregator}' aggregator requires MySQL/MariaDB with UDF Infusion extension. "
            .'See: https://github.com/infusion/udf_infusion'
        );
    }

    /**
     * Create exception for SQLite requiring compile flag.
     */
    public static function requiresSqlitePercentile(string $aggregator): self
    {
        return new self(
            "The '{$aggregator}' aggregator requires SQLite compiled with SQLITE_ENABLE_PERCENTILE flag."
        );
    }
}
