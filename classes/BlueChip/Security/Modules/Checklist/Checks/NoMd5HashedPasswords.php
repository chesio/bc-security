<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;
use wpdb;

class NoMd5HashedPasswords extends Checklist\BasicCheck
{
    /**
     * @var string Prefix of default, MD5-based hashes
     */
    private const WP_OLD_HASH_PREFIX = '$P$';


    public function getDescription(): string
    {
        return \sprintf(
            /* translators: 1: link to plugin with alternative implementation of password hashing scheme */
            esc_html__('WordPress by default uses an MD5 based password hashing scheme that is too cheap and fast to generate cryptographically secure hashes. For modern PHP versions, there are %1$s available.', 'bc-security'),
            '<a href="https://github.com/roots/wp-password-bcrypt" rel="noreferrer">' . esc_html__('more secure alternatives', 'bc-security') . '</a>'
        );
    }


    public function getName(): string
    {
        return __('No default MD5 password hashes', 'bc-security');
    }


    public function __construct(private wpdb $wpdb)
    {
    }


    /**
     * Check does not make sense anymore starting with WordPress 6.8
     *
     * @link https://make.wordpress.org/core/2025/02/17/wordpress-6-8-will-use-bcrypt-for-password-hashing/
     *
     * @return bool
     */
    public function isMeaningful(): bool
    {
        return !is_wp_version_compatible('6.8');
    }


    protected function runInternal(): Checklist\CheckResult
    {
        // Get all users with old hash prefix
        /** @var array<int,array<string,string>>|null $result */
        $result = $this->wpdb->get_results(
            \sprintf("SELECT `user_login` FROM {$this->wpdb->users} WHERE `user_pass` LIKE '%s%%';", self::WP_OLD_HASH_PREFIX),
            ARRAY_A
        );

        if ($result === null) {
            return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether there are any users with password hashed with default MD5-based algorithm.', 'bc-security'));
        } else {
            return ($result === [])
                ? new Checklist\CheckResult(true, esc_html__('No users have password hashed with default MD5-based algorithm.', 'bc-security'))
                : new Checklist\CheckResult(false, esc_html__('The following users have their password hashed with default MD5-based algorithm:', 'bc-security') . ' <em>' . \implode(', ', \array_column($result, 'user_login')) . '</em>')
            ;
        }
    }
}
