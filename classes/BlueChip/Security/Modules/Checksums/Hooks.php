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
     * Action: triggers when checksums retrieval from WordPress.org API failed.
     */
    const CHECKSUMS_RETRIEVAL_FAILED = 'bc-security/action:checksums-retrieval-failed';

    /**
     * Action: triggers when checksums verification found files with non-matching checksum.
     */
    const CHECKSUMS_VERIFICATION_ALERT = 'bc-security/action:checksums-verification-alert';

    /**
     * Filter: filters list of files that should be ignored during check for modified files.
     */
    const IGNORED_MODIFIED_FILES = 'bc-security/filter:modified-files-ignored-in-checksum-verification';

    /**
     * Filter: filters list of files that should be ignored during check for unknown files.
     */
    const IGNORED_UNKNOWN_FILES = 'bc-security/filter:unknown-files-ignored-in-checksum-verification';
}
