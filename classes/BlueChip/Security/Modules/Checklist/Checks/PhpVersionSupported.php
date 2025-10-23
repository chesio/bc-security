<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers\PhpVersion;
use BlueChip\Security\Modules\Checklist;

class PhpVersionSupported extends Checklist\BasicCheck
{
    public function getDescription(): string
    {
        return \sprintf(
            /* translators: 1: link to official page on supported PHP versions */
            esc_html__('Running an %1$s may pose a security risk.', 'bc-security'),
            '<a href="' . esc_url(__('https://www.php.net/supported-versions.php', 'bc-security')) . '" rel="noreferrer">' . esc_html__('unsupported PHP version', 'bc-security') . '</a>'
        );
    }


    public function getName(): string
    {
        return __('PHP version is supported', 'bc-security');
    }


    protected function runInternal(): Checklist\CheckResult
    {
        $phpVersionAsHtml = PhpVersion::getAsHtmlSnippet();

        $isSupported = PhpVersion::isSupported();

        if ($isSupported === null) {
            $message = \sprintf(
                esc_html__('List of supported PHP versions is out-dated. Consider updating the plugin. Btw. you are running PHP %1$s.', 'bc-security'),
                $phpVersionAsHtml
            );
            return new Checklist\CheckResult(null, $message);
        }

        if ($isSupported) {
            // PHP version is supported, but do we have end-of-life date?
            $eol_date = PhpVersion::getEndOfLifeDate();
            // Format message accordingly.
            $message = $eol_date
                ? \sprintf(
                    esc_html__('You are running PHP %1$s, which is supported until %2$s.', 'bc-security'),
                    $phpVersionAsHtml,
                    wp_date(get_option('date_format'), \strtotime($eol_date))
                )
                : \sprintf(
                    esc_html__('You are running PHP %1$s, which is still supported.', 'bc-security'),
                    $phpVersionAsHtml
                )
            ;
            return new Checklist\CheckResult(true, $message);
        } else {
            $message = \sprintf(
                esc_html__('You are running PHP %1$s, which is no longer supported! Consider upgrading your PHP version.', 'bc-security'),
                $phpVersionAsHtml
            );
            return new Checklist\CheckResult(false, $message);
        }
    }
}
