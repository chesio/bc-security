<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class PhpVersionSupported extends Checklist\BasicCheck
{
    /**
     * @var array List of supported PHP versions and their end-of-life dates
     */
    const SUPPORTED_VERSIONS = [
        '7.1' => '2019-12-01',
        '7.2' => '2020-10-30',
        '7.3' => '2021-12-06',
    ];


    public function __construct()
    {
        parent::__construct(
            __('PHP version is supported', 'bc-security'),
            sprintf(__('Running an <a href="%s">unsupported PHP version</a> may pose a security risk.', 'bc-security'), 'https://secure.php.net/supported-versions.php')
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        $oldest_supported_version = $this->getOldestSupportedVersion();

        if (is_null($oldest_supported_version)) {
            $message = sprintf(
                esc_html__('List of supported PHP versions is out-dated. Consider updating the plugin. Btw. you are running PHP %s.', 'bc-security'),
                PHP_VERSION
            );
            return new Checklist\CheckResult(null, $message);
        }

        if (version_compare(PHP_VERSION, $oldest_supported_version, '>=')) {
            // PHP version is supported, but do we have end-of-life date?
            $eol_date = self::SUPPORTED_VERSIONS[PHP_VERSION] ?? null;
            // Format message accordingly.
            $message = $eol_date
                ? sprintf(
                    esc_html__('You are running PHP %1$s, which is supported until %2$s.', 'bc-security'),
                    PHP_VERSION,
                    date_i18n(get_option('date_format'), strtotime($eol_date))
                )
                : sprintf(
                    esc_html__('You are running PHP %1$s, which is still supported.', 'bc-security'),
                    PHP_VERSION
                )
            ;
            return new Checklist\CheckResult(true, $message);
        } else {
            $message = sprintf(
                esc_html__('You are running PHP %s, which is no longer supported! Consider upgrading your PHP version.', 'bc-security'),
                PHP_VERSION
            );
            return new Checklist\CheckResult(false, $message);
        }
    }


    /**
     * @return string Oldest supported version of PHP as of today or null, if it can not be determined from data available.
     */
    private function getOldestSupportedVersion(): ?string
    {
        $now = current_time('timestamp');

        foreach (self::SUPPORTED_VERSIONS as $version => $eol_date) {
            if (strtotime($eol_date) >= $now) {
                return $version;
            }
        }

        return null;
    }
}
