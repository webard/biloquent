<?php

declare(strict_types=1);

namespace Webard\Biloquent\Support;

use Illuminate\Support\Facades\DB;

/**
 * Runtime feature detector for SQLite statistical functions.
 *
 * SQLite must be compiled with SQLITE_ENABLE_PERCENTILE flag
 * to support median() and percentile_cont() functions.
 */
class SqliteFeatureDetector
{
    protected static ?bool $hasMedian = null;

    protected static ?bool $hasPercentile = null;

    /**
     * Check if SQLite has median() function available.
     */
    public static function hasMedian(): bool
    {
        if (self::$hasMedian !== null) {
            return self::$hasMedian;
        }

        return self::$hasMedian = self::testFunction('median');
    }

    /**
     * Check if SQLite has percentile_cont() function available.
     */
    public static function hasPercentile(): bool
    {
        if (self::$hasPercentile !== null) {
            return self::$hasPercentile;
        }

        return self::$hasPercentile = self::testFunction('percentile_cont');
    }

    /**
     * Reset the cached detection results.
     * Useful for testing.
     */
    public static function reset(): void
    {
        self::$hasMedian = null;
        self::$hasPercentile = null;
    }

    /**
     * Test if a function is available by trying to use it.
     */
    protected static function testFunction(string $function): bool
    {
        try {
            // Create a temporary table with test data
            DB::connection('sqlite')->statement('CREATE TEMPORARY TABLE IF NOT EXISTS _biloquent_test (val INTEGER)');
            DB::connection('sqlite')->statement('INSERT INTO _biloquent_test VALUES (1), (2), (3)');

            // Try to use the function
            $query = match ($function) {
                'median' => 'SELECT median(val) FROM _biloquent_test',
                'percentile_cont' => 'SELECT percentile_cont(val, 0.5) FROM _biloquent_test',
                default => throw new \InvalidArgumentException("Unknown function: {$function}"),
            };

            DB::connection('sqlite')->selectOne($query);

            // Clean up
            DB::connection('sqlite')->statement('DROP TABLE IF EXISTS _biloquent_test');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
