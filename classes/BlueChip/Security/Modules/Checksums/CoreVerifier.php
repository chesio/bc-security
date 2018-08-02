<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checksums;

/**
 * Core verifier gets (official) checksums from WordPress.org.
 *
 * @link https://codex.wordpress.org/WordPress.org_API#Checksum
 */
class CoreVerifier extends Verifier
{
    /**
     * @var string URL of checksum API
     */
    const CHECKSUMS_API_URL = 'https://api.wordpress.org/core/checksums/1.0/';


    /**
     * Perform checksums check.
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::CORE_CHECKSUMS_RETRIEVAL_FAILED
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::CORE_CHECKSUMS_VERIFICATION_ALERT
     */
    public function runCheck()
    {
        // Add necessary arguments to request URL.
        $url = add_query_arg(
            [
                'version' => get_bloginfo('version'),
                'locale'  => get_locale(), // TODO: What about multilanguage sites?
            ],
            self::CHECKSUMS_API_URL
        );

        // Get checksums via WordPress.org API.
        if (empty($checksums = $this->getChecksums($url))) {
            do_action(Hooks::CORE_CHECKSUMS_RETRIEVAL_FAILED, $url);
            return;
        }

        // Use checksums to find any modified files.
        $modified_files = $this->findModifiedFiles($checksums);
        // Scan WordPress directories to find any files unknown to WordPress.
        $unknown_files = $this->findUnknownFiles($checksums);

        // Trigger alert, if any suspicious files have been found.
        if (!empty($modified_files) || !empty($unknown_files)) {
            do_action(Hooks::CORE_CHECKSUMS_VERIFICATION_ALERT, $modified_files, $unknown_files);
        }
    }


    /**
     * Get md5 checksums of core WordPress files from WordPress.org API.
     *
     * @param string $url
     * @return \stdClass|null
     */
    private function getChecksums(string $url)
    {
        $json = self::getJson($url);

        return $json && !empty($json->checksums) ? $json->checksums : null;
    }


    /**
     * Check md5 hashes of files on local filesystem against $checksums and report any modified files.
     *
     * Files in wp-content directory are automatically excluded, see:
     * https://github.com/pluginkollektiv/checksum-verifier/pull/11
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::IGNORED_MODIFIED_FILES
     *
     * @param \stdClass $checksums
     * @return array
     */
    private function findModifiedFiles($checksums): array
    {
        // Get files that should be ignored.
        $ignored_files = apply_filters(
            Hooks::IGNORED_MODIFIED_FILES,
            [
                'wp-config-sample.php',
                'wp-includes/version.php',
            ]
        );

        // Initialize array for files that do not match.
        $modified_files = self::checkDirectoryForModifiedFiles(ABSPATH, $checksums, $ignored_files);

        // Ignore any modified files in wp-content directory.
        return array_filter(
            $modified_files,
            function ($filename) {
                return strpos($filename, 'wp-content/') !== 0;
            }
        );
    }


    /**
     * Report any unknown files in root directory and in wp-admin and wp-includes directories (including subdirectories).
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::IGNORED_UNKNOWN_FILES
     *
     * @param \stdClass $checksums
     * @return array
     */
    private function findUnknownFiles($checksums): array
    {
        // Get files that should be ignored.
        $ignored_files = apply_filters(
            Hooks::IGNORED_UNKNOWN_FILES,
            [
                '.htaccess',
                'wp-config.php',
                'liesmich.html', // German readme (de_DE)
                'olvasdel.html', // Hungarian readme (hu_HU)
                'procitajme.html',  // Croatian readme (hr)
            ]
        );

        return array_filter(
            array_merge(
                // Scan root WordPress directory.
                self::scanDirectoryForUnknownFiles(ABSPATH, ABSPATH, $checksums, false),
                // Scan wp-admin directory recursively.
                self::scanDirectoryForUnknownFiles(ABSPATH . 'wp-admin', ABSPATH, $checksums, true),
                // Scan wp-include directory recursively.
                self::scanDirectoryForUnknownFiles(ABSPATH . WPINC, ABSPATH, $checksums, true)
            ),
            function ($filename) use ($ignored_files) {
                return !in_array($filename, $ignored_files, true);
            }
        );
    }
}
