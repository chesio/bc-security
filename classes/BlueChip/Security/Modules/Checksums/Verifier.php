<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checksums;

abstract class Verifier
{
    /**
     * Perform checksums check.
     */
    abstract public function runCheck();


    /**
     * Check md5 hashes of files under $path on local filesystem against $checksums and report any modified files.
     *
     * @param string $path Absolute path to checksums root directory, must end with slash!
     * @param \stdClass $checksums Dictionary with { filename: checksum } items. All filenames must be relative to $path.
     * @param array $ignored_files List of filenames to ignore [optional].
     * @return array
     */
    protected static function checkDirectoryForModifiedFiles($path, $checksums, array $ignored_files = [])
    {
        // Initialize array for files that do not match.
        $modified_files = [];

        // Loop through all files in list.
        foreach ($checksums as $filename => $checksum) {
            // Skip any ignored files.
            if (in_array($filename, $ignored_files, true)) {
                continue;
            }

            // Get absolute file path.
            $pathname = $path . $filename;

            // Check, if file exists (skip non-existing files).
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
     * Scan given $directory ($recursive-ly) and report any files not present in $checksums.
     *
     * @param string $directory Directory to scan, must be ABSPATH or a subdirectory thereof.
     * @param string $path Absolute path to checksums root directory, must end with slash!
     * @param \stdClass $checksums Dictionary with { filename: checksum } items. All filenames must be relative to $path.
     * @param bool $recursive Scan subdirectories too [optional].
     * @return array
     */
    protected static function scanDirectoryForUnknownFiles($directory, $path, $checksums, $recursive = false)
    {
        // Only allow to scan ABSPATH and subdirectories.
        if (strpos($directory, ABSPATH) !== 0) {
            _doing_it_wrong(__METHOD__, sprintf('Directory to scan (%s) is neither ABSPATH (%s) nor subdirectory thereof!', $directory, ABSPATH), '0.5.0');
            return [];
        }

        $unknown_files = [];

        // Get either recursive or normal directory iterator.
        $it = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory))
            : new \DirectoryIterator($directory)
        ;

        $directory_path_length = strlen($path);

        foreach ($it as $fileinfo) {
            // Skip directories as they don't have checksums.
            if ($fileinfo->isDir()) {
                continue;
            }

            // Strip directory path from file's pathname.
            $filename = substr($fileinfo->getPathname(), $directory_path_length);

            // Check, whether it is a known file.
            if (!isset($checksums->$filename)) {
                $unknown_files[] = $filename;
            }
        }

        return $unknown_files;
    }


    /**
     * Fetch JSON data from remote $url.
     *
     * @param string $url
     * @return mixed
     */
    protected static function getJson($url)
    {
        // Make request to URL.
        $response = wp_remote_get($url);

        // Check response code.
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        // Read JSON.
        $json = json_decode(wp_remote_retrieve_body($response));

        // If decoding went fine, return JSON data.
        return (json_last_error() === JSON_ERROR_NONE) ? $json : null;
    }
}
