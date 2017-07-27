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
    const CHECKSUMS_VERIFICATION_MATCHES = 'bc-security/action:checksums-retrieval-failed';

    /**
     * Filter: filters list of files that should be ignored during checksums verification.
     */
    const IGNORED_FILES = 'bc-security/filter:files-ignored-in-checksum-verification';
}
