<?php

declare(strict_types=1);

namespace BlueChip\Security\Helpers;

abstract class PhpVersion
{
    /**
     * @var array<string,string> List of supported PHP versions and their end-of-life dates
     *
     * @link https://www.php.net/supported-versions.php
     */
    private const SUPPORTED_PHP_VERSIONS = [
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
    ];

    /**
     * @return string Active PHP version as "major.minor" string
     */
    public static function get(): string
    {
        return \sprintf("%s.%s", PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
    }


    /**
     * @return string HTML tag with PHP version as <major>.<minor> string with full version in title attribute.
     */
    public static function getAsHtmlSnippet(): string
    {
        return \sprintf('<em title="%s">%s.%s</em>', PHP_VERSION, PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
    }


    /**
     * @param string $version PHP version in "major.minor" format (eg. "8.4")
     *
     * @return string|null EOL-date for given PHP $version in YYYY-MM-DD format or null if unknown.
     */
    public static function getEndOfLifeDate(?string $version = null): ?string
    {
        $version ??= self::get();

        return self::SUPPORTED_PHP_VERSIONS[$version] ?? null;
    }


    /**
     * @param string $version PHP version in "major.minor" format (eg. "8.4")
     *
     * @return bool|null
     */
    public static function isSupported(?string $version = null): ?bool
    {
        $version ??= self::get();

        $now = \time();

        foreach (self::SUPPORTED_PHP_VERSIONS as $supportedPhpVersion => $eol_date) {
            if (\strtotime($eol_date) >= $now) {
                // Oldest PHP version that is still being supported.
                return version_compare($version, $supportedPhpVersion, '>=');
            }
        }

        // We have out-dated data.
        $newestKnownUnsupportedPhpVersion = \array_key_last(self::SUPPORTED_PHP_VERSIONS);

        // If the latest PHP version for which we have EOL is not supported anymore and
        // PHP version is the same or older than PHP version must be out-dated too.
        // Otherwise we cannot say for sure.
        return version_compare($newestKnownUnsupportedPhpVersion, $version, '>=') ? false : null;
    }
}
