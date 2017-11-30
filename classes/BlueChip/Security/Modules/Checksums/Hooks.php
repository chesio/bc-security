<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checksums;

/**
 * Hooks available in checksum verifier module
 */
interface Hooks
{
    /**
     * Action: triggers when WP core checksums retrieval from WordPress.org API failed.
     */
    const CORE_CHECKSUMS_RETRIEVAL_FAILED = 'bc-security/action:core-checksums-retrieval-failed';

    /**
     * Action: triggers when WP core checksums verification found files with non-matching checksum.
     */
    const CORE_CHECKSUMS_VERIFICATION_ALERT = 'bc-security/action:core-checksums-verification-alert';

    /**
     * Action: triggers when plugin checksums retrieval from downloads.wordpress.org failed.
     */
    const PLUGIN_CHECKSUMS_RETRIEVAL_FAILED = 'bc-security/action:plugin-checksums-retrieval-failed';

    /**
     * Action: triggers when plugin checksums verification found files with non-matching checksum.
     */
    const PLUGIN_CHECKSUMS_VERIFICATION_ALERT = 'bc-security/action:plugin-checksums-verification-alert';

    /**
     * Filter: filters list of files that should be ignored during check for modified core files.
     */
    const IGNORED_MODIFIED_FILES = 'bc-security/filter:modified-files-ignored-in-core-checksum-verification';

    /**
     * Filter: filters list of files that should be ignored during check for unknown core files.
     */
    const IGNORED_UNKNOWN_FILES = 'bc-security/filter:unknown-files-ignored-in-core-checksum-verification';

    /**
     * Filter: filters list of plugins to check in checksum verification.
     */
    const PLUGINS_TO_VERIFY = 'bc-security/filter:plugins-to-check-in-checksum-verification';
}
