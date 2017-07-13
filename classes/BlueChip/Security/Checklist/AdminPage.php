<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Checklist;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-checklist';

    /** @var string Prefix of default, MD5-based hashes */
    const WP_OLD_HASH_PREFIX = '$P$';


    /** @var \wpdb WordPress database access abstraction object */
    private $wpdb;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    function __construct($wpdb)
    {
        $this->page_title = _x('Security Checklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Checklist', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;

        $this->wpdb = $wpdb;
    }


    /**
     * Render admin page.
     */
    public function render()
    {
        echo '<div class="wrap">';

        echo '<h1>' . $this->page_title . '</h1>';
        echo '<p>The more <span class="dashicons dashicons-yes"></span> you have, the better!</p>';

        echo '<table class="wp-list-table widefat striped">';

        echo '<tr>';
        $this->renderPhpFileEditationStatus();
        echo '</tr>';

        echo '<tr>';
        $this->renderPhpFileBlockedInUploadsDir();
        echo '</tr>';

        echo '<tr>';
        $this->renderNoObviousUsernamesStatus();
        echo '</tr>';

        echo '<tr>';
        $this->renderNoDefaultMd5HashedPasswords();
        echo '</tr>';

        echo '</table>';

        echo '<p>';
        echo sprintf(
            /* translators: %s: link to hardening options */
            esc_html__('You might also want to enable some other %s.', 'bc-security'),
            sprintf(
                '<a href="%s">%s</a>',
                \BlueChip\Security\Core\AdminPage::getPageUrl(\BlueChip\Security\Hardening\AdminPage::SLUG),
                esc_html__('hardening options', 'bc-security')
            )
        );
        echo '</p>';

        echo '</div>';
    }


    /**
     * Render status info about php file editation.
     */
    private function renderPhpFileEditationStatus()
    {
        $disabled = defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;
        echo '<th><span class="dashicons dashicons-' . ($disabled ? 'yes' : 'no') . '"></span></th>';
        echo '<th>' . __('PHP Files Editation Disabled', 'bc-security') . '</th>';
        echo '<td>' . sprintf(__('It is generally recommended to <a href="%s">disable editation of PHP files</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Disable_File_Editing') . '</td>';
        echo '<td></td>';
    }


    /**
     * Render status info about php files being unaccessible from within uploads directory.
     */
    private function renderPhpFileBlockedInUploadsDir()
    {
        $blocked = Helper::isAccessToPhpFilesInUploadsDirForbidden();

        echo '<th>' . (!is_null($blocked) ? ('<span class="dashicons dashicons-' . ($blocked ? 'yes' : 'no') . '"></span>') : '' ) . '</th>';
        echo '<th>' . __('PHP Files Forbidden', 'bc-security') . '</th>';
        echo '<td>' . sprintf(__('Vulnerable plugins may allow upload of arbitrary files into uploads directory. <a href="%s">Disabling access to PHP files</a> within uploads directory may help prevent successful exploitation of such vulnerabilities.', 'bc-security'), 'https://gist.github.com/chesio/8f83224840eccc1e80a17fc29babadf2') . '</td>';
        if (is_null($blocked)) {
            echo '<td>' . esc_html__('Unfortunately, BC Security has failed to determine whether PHP files can be executed from uploads directory.') . '</td>';
        } elseif ($blocked) {
            echo '<td>' . esc_html__('It seems that PHP files cannot be executed from uploads directory.', 'bc-security') . '</td>';
        } else {
            echo '<td>' . esc_html__('It seems that PHP files can be executed from uploads directory!', 'bc-security') . '</td>';
        }
    }


    /**
     * Render status info about no obvious usernames being present on the system.
     *
     * @hook bc_security_status_obvious_usernames Filters list of obvious usernames to check and report.
     */
    private function renderNoObviousUsernamesStatus()
    {
        // Get (filtered) list of obvious usernames to test.
        $obvious = apply_filters(Hooks::OBVIOUS_USERNAMES, ['admin', 'administrator']);
        // Check for existing usernames.
        $existing = array_filter($obvious, function ($username) { return get_user_by('login', $username); });

        echo '<th><span class="dashicons dashicons-' . (empty($existing) ? 'yes' : 'no') . '"></span></th>';
        echo '<th>' . __('No Obvious Usernames', 'bc-security') . '</th>';
        echo '<td>' . sprintf(__('Usernames like "admin" and "administrator" are often used in brute force attacks and <a href="%s">should be avoided</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity') . '</td>';
        if (empty($existing)) {
            echo '<td>' . esc_html__('None of the following usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $obvious) . '</em></td>';
        } else {
            echo '<td>' . esc_html__('The following obvious usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $existing) . '</em></td>';
        }
    }


    /**
     * Render status info about no default MD5-based password hashes being present in database.
     */
    private function renderNoDefaultMd5HashedPasswords()
    {
        // Get all users with old hash prefix
        $result = $this->wpdb->get_results(sprintf(
            "SELECT `user_login` FROM {$this->wpdb->users} WHERE `user_pass` LIKE '%s%%';",
            self::WP_OLD_HASH_PREFIX
        ));

        echo '<th>' . (($result === false) ? '' : ('<span class="dashicons dashicons-' . (empty($result) ? 'yes' : 'no') . '"></span>')) . '</th>';
        echo '<th>' . __('No Default MD5 Password Hashes', 'bc-security') . '</th>';
        echo '<td>' . sprintf(__('WordPress by default uses an MD5 based password hashing scheme that is too cheap and fast to generate cryptographically secure hashes. For modern PHP versions, there are <a href="%s">more secure alternatives</a> available.', 'bc-security'), 'https://github.com/roots/wp-password-bcrypt') . '</td>';
        if ($result === false) {
            echo '<td>' . esc_html__('Unfortunately, BC Security has failed to determine whether there are any users with password hashed with default MD5-based algorithm.', 'bc-security') . '</td>';
        } elseif (empty($result)) {
            echo '<td>' . esc_html__('No users have password hashed with default MD5-based algorithm.', 'bc-security') . '</td>';
        } else {
            echo '<td>' . esc_html__('The following users have their password hashed with default MD5-based algorithm:', 'bc-security') . ' <em>' . implode(', ', wp_list_pluck($result, 'user_login')) . '</em></td>';
        }
    }
}
