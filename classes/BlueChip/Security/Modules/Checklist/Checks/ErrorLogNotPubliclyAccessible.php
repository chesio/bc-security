<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class ErrorLogNotPubliclyAccessible extends Checklist\BasicCheck
{
    public function __construct()
    {
        parent::__construct(
            __('Error log not publicly accessible', 'bc-security'),
            sprintf(__('Both <code>WP_DEBUG</code> and <code>WP_DEBUG_LOG</code> constants are set to true, therefore <a href="%s">WordPress saves all errors</a> to a <code>debug.log</code> log file inside the <code>/wp-content/</code> directory. This file can contain sensitive information and therefore should not be publicly accessible.', 'bc-security'), 'https://codex.wordpress.org/Debugging_in_WordPress')
        );
    }


    /**
     * Check makes sense, only when debug logging is active.
     *
     * @return bool
     */
    public function makesSense(): bool
    {
        return WP_DEBUG && WP_DEBUG_LOG;
    }


    protected function runInternal(): Checklist\CheckResult
    {
        // Path and filename is hardcoded in wp-includes/load.php
        $url = WP_CONTENT_URL . '/debug.log';

        // Report status.
        $status = Checklist\Helper::isAccessToUrlForbidden($url);

        if (is_bool($status)) {
            return $status
                ? new Checklist\CheckResult(true, esc_html__('It seems that error log is not publicly accessible.', 'bc-security'))
                : new Checklist\CheckResult(false, esc_html__('It seems that error log is publicly accessible!', 'bc-security'))
            ;
        } else {
            return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether error log is publicly accessible.', 'bc-security'));
        }
    }
}
