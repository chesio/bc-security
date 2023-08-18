<?php

namespace BlueChip\Security\Modules\Checklist;

abstract class Helper
{
    public static function formatLastRunTimestamp(Check $check): string
    {
        if (empty($timestamp = $check->getTimeOfLastRun())) {
            return '--';
        } else {
            $format = \sprintf('%s %s', get_option('date_format'), get_option('time_format'));
            return wp_date($format, $timestamp) ?: '';
        }
    }


    /**
     * @param string[] $list
     *
     * @return string
     */
    public static function formatListOfFiles(array $list): string
    {
        return \implode(
            ', ',
            \array_map(
                fn (string $file): string => '<em>' . esc_html($file) . '</em>',
                $list
            )
        );
    }


    /**
     * Check if HTTP request to $url results in 403 forbidden response.
     *
     * Method returns:
     * - true if HTTP request to $url returns HTTP status 403.
     * - false if HTTP request to $url returns HTTP status 200 and response body is equal to $body (if given) or 404
     *   is returned (meaning file does not exist, but access is not forbidden).
     * - null, in all other cases: especially if HTTP request to $url fails or other HTTP status than 200, 403 or 404
     *   is returned. Null is also returned for HTTP status 200 if response body is different than $body (if given).
     *
     * @param string $url URL to check.
     * @param string|null $body Response body to check [optional].
     *
     * @return bool|null
     */
    public static function isAccessToUrlForbidden(string $url, ?string $body = null): ?bool
    {
        // Try to get provided URL. Use HEAD request for simplicity if response body is of no interest.
        $response = \is_string($body) ? wp_remote_get($url) : wp_remote_head($url);

        switch (wp_remote_retrieve_response_code($response)) {
            case 200:
                // Status suggests that URL can be accessed, but check response body too if given.
                return \is_string($body) ? ((wp_remote_retrieve_body($response) === $body) ? false : null) : false;
            case 403:
                // Status suggests that access to URL is forbidden.
                return true;
            case 404:
                // Status suggests that no resource has been found, but access to URL is not forbidden.
                return false;
            default:
                // Otherwise assume nothing.
                return null;
        }
    }


    /**
     * Check md5 hashes of files under $path on local filesystem against $checksums and report any modified files.
     *
     * @param string $path Absolute path to checksums root directory, must end with slash!
     * @param object $checksums Dictionary with { filename: checksum } items. All filenames must be relative to $path.
     * @param string[] $ignored_files List of filenames to ignore [optional].
     *
     * @return string[]
     */
    public static function checkDirectoryForModifiedFiles(string $path, object $checksums, array $ignored_files = []): array
    {
        // Initialize array for files that do not match.
        $modified_files = [];

        // Loop through all files in list.
        foreach ((array) $checksums as $filename => $checksum) {
            // Skip any ignored files.
            if (\in_array($filename, $ignored_files, true)) {
                continue;
            }

            // Get absolute file path.
            $pathname = $path . $filename;

            // Check whether file exists (skip non-existing files).
            if (!\file_exists($pathname)) {
                continue;
            }

            // Compare MD5 hashes.
            // Note that there can be multiple checksums provided for a single file (at least in plugin checksums).
            $md5 = \md5_file($pathname);
            if (\is_array($checksum) ? !\in_array($md5, $checksum, true) : ($md5 !== $checksum)) {
                $modified_files[] = $filename;
            }
        }

        return $modified_files;
    }


    /**
     * Scan given $directory ($recursive-ly) and report any files not present in $checksums.
     *
     * @param string $directory Directory to scan.
     * @param string $path Absolute path to checksums root directory, must end with slash!
     * @param object $checksums Dictionary with { filename: checksum } items. All filenames must be relative to $path.
     * @param bool $recursive Scan subdirectories too [optional].
     *
     * @return string[]
     */
    public static function scanDirectoryForUnknownFiles(string $directory, string $path, object $checksums, bool $recursive = false): array
    {
        $unknown_files = [];

        // Get either recursive or normal directory iterator.
        $it = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory))
            : new \DirectoryIterator($directory)
        ;

        $directory_path_length = \strlen($path);

        foreach ($it as $fileinfo) {
            // Skip directories as they don't have checksums.
            if ($fileinfo->isDir()) {
                continue;
            }

            // Strip directory path from file's pathname.
            $filename = \substr($fileinfo->getPathname(), $directory_path_length);

            // Check, whether it is a known file.
            if (!isset($checksums->$filename)) {
                $unknown_files[] = $filename;
            }
        }

        return $unknown_files;
    }
}
