<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checksums;

class Verifier
{
    /**
     * @var string URL of checksum API
     */
    const CHECKSUMS_API_URL = 'https://api.wordpress.org/core/checksums/1.0/';


    /**
     * Perform checksums check.
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::CHECKSUMS_RETRIEVAL_FAILED
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::CHECKSUMS_VERIFICATION_MATCHES
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
        $checksums = $this->getChecksums($url);
        if (empty($checksums)) {
            do_action(Hooks::CHECKSUMS_RETRIEVAL_FAILED, $url);
            return;
        }

        // Use checksums to find any modified files.
        $modified_files = $this->findModifiedFiles($checksums);
        // Scan WordPress directories to find any files unknown to WordPress.
        $unknown_files = $this->findUnknownFiles($checksums);

        // Trigger alert, if any suspicious files have been found.
        if (!empty($modified_files) || !empty($unknown_files)) {
            do_action(Hooks::CHECKSUMS_VERIFICATION_ALERT, $modified_files, $unknown_files);
        }
    }


    /**
     * Get md5 checksums of default WordPress files from WordPress.org.
     *
     * @param string $url
     * @return \stdClass|null
     */
    private function getChecksums($url)
    {
        // Make request to Checksums API.
        $response = wp_remote_get($url);

        // Check response code.
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        // Read JSON.
        $json = json_decode(wp_remote_retrieve_body($response));

        if (json_last_error() === JSON_ERROR_NONE) {
            // Return checksums, if they exists.
            return empty($json->checksums) ? null : $json->checksums;
        } else {
            // Return nothing.
            return null;
        }
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
    private function findModifiedFiles($checksums)
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
        $modified_files = [];

        // Loop through all files in list.
        foreach ($checksums as $filename => $checksum) {
            // Skip any files in wp-content directory or any ignored files.
            if ((strpos($filename, 'wp-content/') === 0) || in_array($filename, $ignored_files, true)) {
                continue;
            }

            // Get absolute file path.
            $pathname = ABSPATH . $filename;

            // Check, if file exists.
            if (!file_exists($pathname)) {
                continue;
            }

            // Compare MD5 hashes.
            if (md5_file($pathname) !== $checksum) {
                $modified_files[] = $filename;
            }
        }

        return $modified_files;
    }


    /**
     * Report any unknown files in root directory and in wp-admin and wp-includes directories (including subdirectories).
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::IGNORED_UNKNOWN_FILES
     *
     * @param \stdClass $checksums
     * @return array
     */
    private function findUnknownFiles($checksums)
    {
        // Get files that should be ignored.
        $ignored_files = apply_filters(
            Hooks::IGNORED_UNKNOWN_FILES,
            [
                'wp-config.php',
                'liesmich.html', // German readme (de_DE)
                'olvasdel.html', // Hungarian readme (hu_HU)
                'procitajme.html',  // Croatian readme (hr)
            ]
        );

        return array_filter(
            array_merge(
                // Scan root WordPress directory.
                $this->scanDirForUnknownFiles($checksums, ABSPATH, false),
                // Scan wp-admin directory recursively.
                $this->scanDirForUnknownFiles($checksums, ABSPATH . 'wp-admin', true),
                // Scan wp-include directory recursively.
                $this->scanDirForUnknownFiles($checksums, ABSPATH . WPINC, true)
            ),
            function ($filename) use ($ignored_files) {
                return !in_array($filename, $ignored_files, true);
            }
        );
    }


    /**
     * Scan given $directory ($recursive-ly) and report any files not present in $checksums.
     *
     * @param \stdClass $checksums
     * @param string $directory Directory to scan, must be ABSPATH or a subdirectory thereof.
     * @param bool $recursive Scan subdirectories too [optional].
     * @return array
     */
    private function scanDirForUnknownFiles($checksums, $directory = ABSPATH, $recursive = false)
    {
        $unknown_files = [];

        // Only allow to scan ABSPATH and subdirectories.
        if (strpos($directory, ABSPATH) !== 0) {
            _doing_it_wrong(__METHOD__, sprintf('Directory to scan (%s) is neither ABSPATH (%s) nor subdirectory thereof!', $directory, ABSPATH), '0.5.0');
            return $unknown_files;
        }

        // Get either recursive or normal directory iterator.
        $it = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory))
            : new \DirectoryIterator($directory)
        ;

        $abspath_length = strlen(ABSPATH);

        foreach ($it as $fileinfo) {
            // Skip directories.
            if ($fileinfo->isDir()) {
                continue;
            }

            // Drop ABSPATH from file's pathname.
            $filename = substr($fileinfo->getPathname(), $abspath_length);

            // Check, whether it is a known file.
            if (!isset($checksums->$filename)) {
                $unknown_files[] = $filename;
            }
        }

        return $unknown_files;
    }
}
