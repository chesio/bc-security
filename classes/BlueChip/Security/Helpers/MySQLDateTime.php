<?php

namespace BlueChip\Security\Helpers;

/**
 * Helper to convert Unix timestamps into date format accepted by MySQL and vice versa.
 */
abstract class MySQLDateTime
{
    /**
     * @var string Date format accepted by MySQL
     *
     * @see https://core.trac.wordpress.org/ticket/48740
     */
    public const FORMAT = 'Y-m-d H:i:s';

    /**
     * @link https://www.php.net/manual/en/date.php
     *
     * @param int $timestamp
     *
     * @return string
     */
    public static function formatDateTime(int $timestamp): string
    {
        return \date(self::FORMAT, $timestamp);
    }

    /**
     * @link https://www.php.net/manual/en/datetime.createfromformat.php
     *
     * @param string $datetime
     *
     * @return int|null Timestamp for given $datetime string or null if $datetime cannot be parsed.
     */
    public static function parseTimestamp(string $datetime): ?int
    {
        $datetime_from_format = \date_create_from_format(self::FORMAT, $datetime);
        return $datetime_from_format ? $datetime_from_format->getTimestamp() : null;
    }
}
