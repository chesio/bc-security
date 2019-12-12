<?php

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class PhpVersionSupported extends Checklist\BasicCheck
{
    /**
     * @var array List of supported PHP versions and their end-of-life dates
     */
    private const SUPPORTED_VERSIONS = [
        '7.2' => '2020-11-30',
        '7.3' => '2021-12-06',
        '7.4' => '2022-11-28',
    ];


    public function __construct()
    {
        parent::__construct(
            __('PHP version is supported', 'bc-security'),
            \sprintf(
                /* translators: 1: link to official page on supported PHP versions */
                esc_html__('Running an %1$s may pose a security risk.', 'bc-security'),
                '<a href="' . esc_url(__('https://www.php.net/supported-versions.php', 'bc-security')) . '" rel="noreferrer">' . esc_html__('unsupported PHP version', 'bc-security') . '</a>'
            )
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        // Get oldest supported version (as <major>.<minor>):
        $oldest_supported_version = self::getOldestSupportedPhpVersion();

        if (null === $oldest_supported_version) {
            $message = \sprintf(
                esc_html__('List of supported PHP versions is out-dated. Consider updating the plugin. Btw. you are running PHP %1$s.', 'bc-security'),
                self::formatPhpVersion()
            );
            return new Checklist\CheckResult(null, $message);
        }

        // Get active PHP version as <major>.<minor> string:
        $php_version = \sprintf("%s.%s", PHP_MAJOR_VERSION, PHP_MINOR_VERSION);

        if (\version_compare($php_version, $oldest_supported_version, '>=')) {
            // PHP version is supported, but do we have end-of-life date?
            $eol_date = self::SUPPORTED_VERSIONS[$php_version] ?? null;
            // Format message accordingly.
            $message = $eol_date
                ? \sprintf(
                    esc_html__('You are running PHP %1$s, which is supported until %2$s.', 'bc-security'),
                    self::formatPhpVersion(),
                    wp_date(get_option('date_format'), \strtotime($eol_date))
                )
                : \sprintf(
                    esc_html__('You are running PHP %1$s, which is still supported.', 'bc-security'),
                    self::formatPhpVersion()
                )
            ;
            return new Checklist\CheckResult(true, $message);
        } else {
            $message = \sprintf(
                esc_html__('You are running PHP %1$s, which is no longer supported! Consider upgrading your PHP version.', 'bc-security'),
                self::formatPhpVersion()
            );
            return new Checklist\CheckResult(false, $message);
        }
    }


    /**
     * @return string HTML tag with PHP version as <major>.<minor> string with full version in title attribute.
     */
    private static function formatPhpVersion(): string
    {
        return \sprintf('<em title="%s">%s.%s</em>', PHP_VERSION, PHP_MAJOR_VERSION, PHP_MINOR_VERSION);
    }


    /**
     * @return string Oldest supported version of PHP as of today or null if it can not be determined from data available.
     */
    private static function getOldestSupportedPhpVersion(): ?string
    {
        $now = \time();

        foreach (self::SUPPORTED_VERSIONS as $version => $eol_date) {
            if (\strtotime($eol_date) >= $now) {
                return $version;
            }
        }

        return null;
    }
}
