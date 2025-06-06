<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers\WpRemote;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Cron\Jobs;

class CoreIntegrity extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    protected const CRON_JOB_HOOK = Jobs::CORE_INTEGRITY_CHECK;

    /**
     * @var string URL of checksum API
     */
    private const CHECKSUMS_API_URL = 'https://api.wordpress.org/core/checksums/1.0/';


    public function getDescription(): string
    {
        return \sprintf(
            /* translators: 1: link to Wikipedia article about md5sum, 2: link to checksums file at WordPress.org */
            esc_html__('By comparing %1$s of local core files with %2$s it is possible to determine whether any of core files have been modified or there are any unknown files in core directories.', 'bc-security'),
            '<a href="' . esc_url(__('https://en.wikipedia.org/wiki/Md5sum', 'bc-security')) . '" rel="noreferrer">' . esc_html__('MD5 checksums', 'bc-security') . '</a>',
            '<a href="' . esc_url(self::getChecksumsUrl()) . '" rel="noreferrer">' . esc_html__('checksums downloaded from WordPress.org', 'bc-security') . '</a>'
        );
    }


    public function getName(): string
    {
        return __('WordPress core files are untouched', 'bc-security');
    }


    protected function runInternal(): Checklist\CheckResult
    {
        $url = self::getChecksumsUrl();

        // Get checksums via WordPress.org API.
        if (empty($checksums = self::getChecksums($url))) {
            $message = \sprintf(
                /* translators: 1: link to checksums file at WordPress.org */
                esc_html__('Failed to get core file checksums from %1$s.', 'bc-security'),
                '<a href="' . esc_url($url) . '" rel="noreferrer">' . esc_html($url) . '</a>'
            );
            return new Checklist\CheckResult(null, $message);
        }

        // Use checksums to find any modified files.
        $modified_files = self::findModifiedFiles($checksums);
        // Scan WordPress directories to find any files unknown to WordPress.
        $unknown_files = self::findUnknownFiles($checksums);

        if (empty($modified_files) && empty($unknown_files)) {
            return new Checklist\CheckResult(true, esc_html__('WordPress core files seem to be genuine.', 'bc-security'));
        } else {
            $message_parts = [];
            if (!empty($modified_files)) {
                $message_parts[] = \sprintf(
                    esc_html__('The following WordPress core files have been modified: %s', 'bc-security'),
                    Checklist\Helper::formatListOfFiles($modified_files)
                );
            }
            if (!empty($unknown_files)) {
                $message_parts[] = \sprintf(
                    esc_html__('There are following unknown files present in WordPress core directory: %s', 'bc-security'),
                    Checklist\Helper::formatListOfFiles($unknown_files)
                );
            }
            return new Checklist\CheckResult(false, $message_parts);
        }
    }


    /**
     * @return string URL to checksums file at api.wordpress.org for current WordPress version.
     */
    public static function getChecksumsUrl(): string
    {
        // Add version number to request URL.
        return add_query_arg('version', get_bloginfo('version'), self::CHECKSUMS_API_URL);
    }


    /**
     * Get md5 checksums of core WordPress files from WordPress.org API.
     *
     * @param string $url
     *
     * @return object|null
     */
    private static function getChecksums(string $url): ?object
    {
        $json = WpRemote::getJson($url);

        // When no locale is specified in API request, checksums are stored under additional version number key.
        $version = get_bloginfo('version');

        return $json && !empty($json->checksums) && !empty($json->checksums->$version) ? $json->checksums->$version : null;
    }


    /**
     * Check md5 hashes of files on local filesystem against $checksums and report any modified files.
     *
     * Files in wp-content directory are automatically excluded, see:
     * https://github.com/pluginkollektiv/checksum-verifier/pull/11
     *
     * Some files are ignored automatically, because they may differ between localized version of WordPress, see:
     * https://meta.trac.wordpress.org/ticket/4008
     *
     * @hook \BlueChip\Security\Modules\Checklist\Hooks::IGNORED_CORE_MODIFIED_FILES
     *
     * @param object $checksums
     *
     * @return string[]
     */
    private static function findModifiedFiles(object $checksums): array
    {
        // Get files that should be ignored.
        $ignored_files = apply_filters(
            Checklist\Hooks::IGNORED_CORE_MODIFIED_FILES,
            [
                'readme.html',
                'wp-config-sample.php',
                'wp-includes/version.php',
            ]
        );

        // Initialize array for files that do not match.
        $modified_files = Checklist\Helper::checkDirectoryForModifiedFiles(ABSPATH, $checksums, $ignored_files);

        // Ignore any modified files in wp-content directory.
        return \array_filter(
            $modified_files,
            fn (string $filename): bool => !\str_starts_with($filename, 'wp-content/')
        );
    }


    /**
     * Report any unknown files in root directory and in wp-admin and wp-includes directories (including subdirectories).
     *
     * @hook \BlueChip\Security\Modules\Checklist\Hooks::IGNORED_CORE_UNKNOWN_FILES
     *
     * @param object $checksums
     *
     * @return string[]
     */
    private static function findUnknownFiles(object $checksums): array
    {
        // Get files that should be ignored.
        $ignored_files = apply_filters(
            Checklist\Hooks::IGNORED_CORE_UNKNOWN_FILES,
            [
                '.htaccess',
                'composer.json', // WordPress might be installed via Composer
                'wp-config.php',
                'liesmich.html', // German readme (de_DE)
                'olvasdel.html', // Hungarian readme (hu_HU)
                'procitajme.html',  // Croatian readme (hr)
            ]
        );

        return \array_filter(
            \array_merge(
                // Scan root WordPress directory.
                Checklist\Helper::scanDirectoryForUnknownFiles(ABSPATH, ABSPATH, $checksums, false),
                // Scan wp-admin directory recursively.
                Checklist\Helper::scanDirectoryForUnknownFiles(ABSPATH . 'wp-admin', ABSPATH, $checksums, true),
                // Scan wp-include directory recursively.
                Checklist\Helper::scanDirectoryForUnknownFiles(ABSPATH . WPINC, ABSPATH, $checksums, true)
            ),
            fn (string $filename): bool => !\in_array($filename, $ignored_files, true)
        );
    }
}
