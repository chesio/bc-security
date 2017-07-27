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
        // Get checksums via API.
        if (empty($checksums = $this->getChecksums())) {
            do_action(Hooks::CHECKSUMS_RETRIEVAL_FAILED);
            return;
        }

        // Check checksums.
        if (!empty($matches = $this->matchChecksums($checksums))) {
            do_action(Hooks::CHECKSUMS_VERIFICATION_MATCHES, $matches);
        }
    }


    /**
     * @return array|null
     */
    private function getChecksums()
    {
        // Make request to Checksums API.
        $response = wp_remote_get(
            add_query_arg(
                [
                    'version' => get_bloginfo('version'),
                    'locale'  => get_locale(), // TODO: What about multilanguage sites?,
                ],
                self::CHECKSUMS_API_URL
            )
        );

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
     * Check MD5 hashes of files on local filesystem against $checksums.
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::IGNORED_FILES
     *
     * @param array $checksums
     * @return array
     */
    private function matchChecksums($checksums)
    {
        // Get files that should be ignored.
        $ignore_files = apply_filters(
            Hooks::IGNORED_FILES,
            [
                'wp-config-sample.php',
                'wp-includes/version.php',
            ]
        );

        // Init array for files that has no match.
        $matches = [];

        // Loop files.
        foreach ($checksums as $file => $checksum) {
            // Skip ignored files.
            if (in_array($file, $ignore_files, true)) {
                continue;
            }

            // Get absolute file path.
            $file_path = ABSPATH . $file;

            // Check, if file exists.
            if (!file_exists($file_path)) {
                continue;
            }

            // Compare MD5 hashes.
            if (md5_file($file_path) !== $checksum) {
                $matches[] = $file;
            }
        }

        return $matches;
    }
}
