<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class ErrorLogNotPubliclyAccessible extends Checklist\BasicCheck
{
    public function getDescription(): string
    {
        return \sprintf(
            /* translators: 1: link to article on debugging, 2: WP_DEBUG constant, 3: WP_DEBUG_LOG constant, 4: debug.log file, 5: /wp-content path */
            esc_html__('Both %2$s and %3$s constants are set to true, therefore %1$s to a %4$s log file inside the %5$s directory. This file can contain sensitive information and therefore should not be publicly accessible.', 'bc-security'),
            '<a href="' . esc_url(__('https://wordpress.org/support/article/debugging-in-wordpress/', 'bc-security')) . '" rel="noreferrer">' . esc_html__('WordPress saves all errors', 'bc-security') . '</a>',
            '<code>WP_DEBUG</code>',
            '<code>WP_DEBUG_LOG</code>',
            '<code>debug.log</code>',
            '<code>/wp-content/</code>'
        );
    }


    public function getName(): string
    {
        return __('Error log not publicly accessible', 'bc-security');
    }


    /**
     * Check makes sense, only when debug logging is active.
     *
     * @return bool
     */
    public function isMeaningful(): bool
    {
        return WP_DEBUG && WP_DEBUG_LOG;
    }


    protected function runInternal(): Checklist\CheckResult
    {
        if (\in_array(\strtolower((string) \constant('WP_DEBUG_LOG')), ['true', '1'], true)) {
            // `WP_DEBUG_LOG` is set truthy value.
            // Path to debug.log and filename is hardcoded in `wp-includes/load.php`.
            $url = WP_CONTENT_URL . '/debug.log';

            // Report status.
            $status = Checklist\Helper::isAccessToUrlForbidden($url);

            if (\is_bool($status)) {
                return $status
                    ? new Checklist\CheckResult(true, esc_html__('It seems that error log is not publicly accessible.', 'bc-security'))
                    : new Checklist\CheckResult(false, esc_html__('It seems that error log is publicly accessible!', 'bc-security'))
                ;
            } else {
                return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether error log is publicly accessible.', 'bc-security'));
            }
        } else {
            // `WP_DEBUG_LOG` has been set to custom path (= assume it is outside document root).
            return new Checklist\CheckResult(true, esc_html__('Error log is saved in custom location, presumably outside of document root.', 'bc-security'));
        }
    }
}
