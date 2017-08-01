<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-checklist';

    /**
     * @var string Prefix of default, MD5-based hashes
     */
    const WP_OLD_HASH_PREFIX = '$P$';


    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    function __construct(\wpdb $wpdb)
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

        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>';
        /* translators: %s: tick icon */
        echo sprintf(esc_html__('The more %s you have, the better!'), '<span class="dashicons dashicons-yes"></span>');
        echo '</p>';

        echo '<table class="wp-list-table widefat striped">';

        $this->renderPhpFileEditationStatus();

        $this->renderDirectoryListingDisabled();

        $this->renderPhpFileBlockedInUploadsDir();

        if (defined('WP_ENV') && (WP_ENV === 'production')) {
            // Only check in production environment, as in other environments error display might be active on purpose.
            $this->renderDisplayOfErrorsIsOff();
        }

        if (WP_DEBUG && WP_DEBUG_LOG) {
            // Only check, if there is a chance that debug.log is present.
            $this->renderNoPublicAccessToErrorLog();
        }

        $this->renderNoObviousUsernamesStatus();

        $this->renderNoDefaultMd5HashedPasswords();

        echo '</table>';

        echo '<p>';
        echo sprintf(
            /* translators: %s: link to hardening options */
            esc_html__('You might also want to enable some other %s.', 'bc-security'),
            sprintf(
                '<a href="%s">%s</a>',
                \BlueChip\Security\Core\AdminPage::getPageUrl(\BlueChip\Security\Modules\Hardening\AdminPage::SLUG),
                esc_html__('hardening options', 'bc-security')
            )
        );
        echo '</p>';

        echo '</div>';
    }


    /**
     * Render single table row.
     *
     * @param string $name Check name.
     * @param string $description Check description.
     * @param bool|null $status Check status.
     * @param array $detail Explanation for any particular status [optional].
     */
    private function renderCheckRow($name, $description, $status, array $detail = [])
    {
        echo '<tr>';

        // Status may be undetermined, in such case render no icon.
        echo '<th>' . (is_bool($status) ? ('<span class="dashicons dashicons-' . ($status ? 'yes' : 'no') . '"></span>') : '' ) . '</th>';
        // Name should be short and descriptive and without HTML tags.
        echo '<th>' . esc_html($name) . '</th>';
        // Allow for HTML tags in $description.
        echo '<td>' . $description . '</td>';
        // Detail depends on $status. Any field may be empty or a string or a callable that returns a string.
        echo '<td>' . (isset($detail[$status]) ? (is_callable($detail[$status]) ? call_user_func($detail[$status]) : $detail[$status]) : '') . '</td>';

        echo '</tr>';
    }


    /**
     * Render status info about php file editation.
     */
    private function renderPhpFileEditationStatus()
    {
        $this->renderCheckRow(
            __('PHP Files Editation Disabled', 'bc-security'),
            sprintf(__('It is generally recommended to <a href="%s">disable editation of PHP files</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Disable_File_Editing'),
            defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT
        );
    }


    /**
     * Render status info about directory listings being disabled.
     */
    private function renderDirectoryListingDisabled()
    {
        $this->renderCheckRow(
            __('Directory Listing Disabled', 'bc-security'),
            sprintf(__('A common security practice is to disable <a href="%s">directory listings</a>.', 'bc-security'), 'https://wiki.apache.org/httpd/DirectoryListings'),
            Helper::isDirectoryListingDisabled(),
            [
                null => esc_html__('BC Security has failed to determine whether directory listing is disabled.', 'bc-security'),
                true => esc_html__('It seems that directory listing is disabled.', 'bc-security'),
                false => esc_html__('It seems that directory listing is not disabled!', 'bc-security'),
            ]
        );
    }


    /**
     * Render status info about php files being unaccessible from within uploads directory.
     */
    private function renderPhpFileBlockedInUploadsDir()
    {
        $this->renderCheckRow(
            __('PHP Files Forbidden', 'bc-security'),
            sprintf(__('Vulnerable plugins may allow upload of arbitrary files into uploads directory. <a href="%s">Disabling access to PHP files</a> within uploads directory may help prevent successful exploitation of such vulnerabilities.', 'bc-security'), 'https://gist.github.com/chesio/8f83224840eccc1e80a17fc29babadf2'),
            Helper::isAccessToPhpFilesInUploadsDirForbidden(),
            [
                null => esc_html__('BC Security has failed to determine whether PHP files can be executed from uploads directory.', 'bc-security'),
                true => esc_html__('It seems that PHP files cannot be executed from uploads directory.', 'bc-security'),
                false => esc_html__('It seems that PHP files can be executed from uploads directory!', 'bc-security'),
            ]
        );
    }


    /**
     * Render status info about whether display_errors PHP config is off by default.
     */
    public function renderDisplayOfErrorsIsOff()
    {
        $this->renderCheckRow(
            __('Display of PHP errors is off', 'bc-security'),
            sprintf(
                __('<a href="%1$s">Errors should never be printed</a> to the screen as part of the output on production systems. In WordPress environment, <a href="%2$s">display of errors can lead to path disclosures</a> when directly loading certain files.', 'bc-security'),
                'http://php.net/manual/en/errorfunc.configuration.php#ini.display-errors',
                'https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-there-path-disclosures-when-directly-loading-certain-files'
            ),
            Helper::isErrorsDisplayOff(),
            [
                null => esc_html__('BC Security has failed to determine whether display of errors is turned off by default.', 'bc-security'),
                true => esc_html__('It seems that display of errors is turned off by default.', 'bc-security'),
                false => esc_html__('It seems that display of errors is turned on by default!', 'bc-security'),
            ]
        );
    }


    /**
     * Render status info about error log being publicly unaccessible.
     */
    private function renderNoPublicAccessToErrorLog()
    {
        $this->renderCheckRow(
            __('Error log not publicly accessible', 'bc-security'),
            sprintf(__('Both <code>WP_DEBUG</code> and <code>WP_DEBUG_LOG</code> constants are set to true, therefore <a href="%s">WordPress saves all errors</a> to a <code>debug.log</code> log file inside the <code>/wp-content/</code> directory. This file can contain sensitive information and therefore should not be publicly accessible.', 'bc-security'), 'https://codex.wordpress.org/Debugging_in_WordPress'),
            Helper::isAccessToErrorLogForbidden(),
            [
                null => esc_html__('BC Security has failed to determine whether error log is publicly accessible.', 'bc-security'),
                true => esc_html__('It seems that error log is not publicly accessible.', 'bc-security'),
                false => esc_html__('It seems that error log is publicly accessible!', 'bc-security'),
            ]
        );
    }


    /**
     * Render status info about no obvious usernames being present on the system.
     *
     * @hook \BlueChip\Security\Modules\Checklist\Hooks::OBVIOUS_USERNAMES Filters list of obvious usernames to check and report.
     */
    private function renderNoObviousUsernamesStatus()
    {
        // Get (filtered) list of obvious usernames to test.
        $obvious = apply_filters(Hooks::OBVIOUS_USERNAMES, ['admin', 'administrator']);
        // Check for existing usernames.
        $existing = array_filter($obvious, function ($username) { return get_user_by('login', $username); });

        $this->renderCheckRow(
            __('No Obvious Usernames', 'bc-security'),
            sprintf(__('Usernames like "admin" and "administrator" are often used in brute force attacks and <a href="%s">should be avoided</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity'),
            empty($existing),
            [
                true => function () use ($obvious) {
                    return esc_html__('None of the following usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $obvious) . '</em>';
                },
                false => function () use ($existing) {
                    return esc_html__('The following obvious usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $existing) . '</em>';
                },
            ]
        );
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

        $this->renderCheckRow(
            __('No Default MD5 Password Hashes', 'bc-security'),
            sprintf(__('WordPress by default uses an MD5 based password hashing scheme that is too cheap and fast to generate cryptographically secure hashes. For modern PHP versions, there are <a href="%s">more secure alternatives</a> available.', 'bc-security'), 'https://github.com/roots/wp-password-bcrypt'),
            ($result === false) ? null : empty($result),
            [
                null => esc_html__('BC Security has failed to determine whether there are any users with password hashed with default MD5-based algorithm.', 'bc-security'),
                true => esc_html__('No users have password hashed with default MD5-based algorithm.', 'bc-security'),
                false => function () use ($result) {
                    // If this function gets called, than result is non-empty array.
                    return esc_html__('The following users have their password hashed with default MD5-based algorithm:', 'bc-security') . ' <em>' . implode(', ', wp_list_pluck($result, 'user_login')) . '</em>';
                },
            ]
        );
    }
}
