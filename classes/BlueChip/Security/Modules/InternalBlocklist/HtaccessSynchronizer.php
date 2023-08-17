<?php

namespace BlueChip\Security\Modules\InternalBlocklist;

class HtaccessSynchronizer
{
    /**
     * @var string Marker for insertion to .htaccess file.
     */
    private const MARKER = 'BC Security';

    /**
     * @var string Header line for insertion to .htaccess file.
     */
    public const HEADER_LINE = '# BEGIN ' . self::MARKER;

    /**
     * @var string Footer line for insertion to .htaccess file.
     */
    public const FOOTER_LINE = '# END ' . self::MARKER;


    private string $htaccess_file;


    public function __construct()
    {
        $this->htaccess_file = $this->getPathToRootHtaccessFile();
    }


    public function isAvailable(): bool
    {
        return $this->htaccess_file !== '';
    }


    /**
     * @return string[] List of IP addresses blocked via .htaccess file.
     */
    public function extract(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $lines = extract_from_markers($this->htaccess_file, self::MARKER);

        $ip_addresses = [];
        foreach ($lines as $line) {
            if (\str_starts_with($line, '#')) {
                continue;
            }
            $matches = [];
            if (\preg_match('/^Require not ip (\S+)$/', $line, $matches)) {
                $ip_addresses[] = $matches[1];
            }
        }

        return \array_unique($ip_addresses);
    }


    /**
     * @param string[] $blocked_ip_addresses List of IP addresses to block via .htaccess file.
     *
     * @return bool True if $blocked_ip_addresses has been written successfully, false otherwise.
     */
    public function insert(array $blocked_ip_addresses): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        // Prepare rules for given IP addresses.
        $rules = $this->prepareHtaccessRules($blocked_ip_addresses);

        // Write the rules to .htaccess file.
        return insert_with_markers($this->htaccess_file, self::MARKER, $rules);
    }


    private function getPathToRootHtaccessFile(): string
    {
        // Check ABSPATH first - this should work for any regular installation.
        $htaccess_file = ABSPATH . '.htaccess';
        if ($this->isRootHtaccessFile($htaccess_file)) {
            return $htaccess_file;
        }

        // Check one folder above ABSPATH second - this should work for any subdirectory installations.
        $htaccess_file = \dirname(ABSPATH) . DIRECTORY_SEPARATOR . '.htaccess';
        if ($this->isRootHtaccessFile($htaccess_file)) {
            return $htaccess_file;
        }

        return '';
    }


    /**
     * @param string $filename Path to .htaccess file to test.
     *
     * @return bool True if $filename seems to be root .htaccess file, false otherwise.
     */
    private function isRootHtaccessFile(string $filename): bool
    {
        if (!\file_exists($filename) || !\is_readable($filename) || !\is_writable($filename)) {
            return false;
        }

        $contents = \file_get_contents($filename) ?: '';

        return \str_contains($contents, self::HEADER_LINE) && \str_contains($contents, self::FOOTER_LINE);
    }


    /**
     * @link https://help.ovhcloud.com/csm/en-web-hosting-htaccess-ip-restriction?id=kb_article_view&sysparm_article=KB0052844
     *
     * @param string[] $blocked_ip_addresses
     *
     * @return string[]
     */
    private function prepareHtaccessRules(array $blocked_ip_addresses): array
    {
        $rules = [];

        $rules[] = '<IfModule mod_authz_core.c>';
        $rules[] = '<RequireAll>';
        $rules[] = 'Require all granted';
        foreach ($blocked_ip_addresses as $blocked_ip_address) {
            $rules[] = sprintf("Require not ip %s", $blocked_ip_address);
        }
        $rules[] = '</RequireAll>';
        $rules[] = '</IfModule>';

        return $rules;
    }
}
