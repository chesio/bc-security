<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Checklist;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-checklist';


	function __construct()
    {
		$this->page_title = _x('Security Checklist', 'Dashboard page title', 'bc-security');
		$this->menu_title = _x('Checklist', 'Dashboard menu item name', 'bc-security');
		$this->slug = self::SLUG;
	}


	/**
	 * Render admin page
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
		$this->renderNoObviousUsernamesStatus();
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
	 * Render status info about no obvious usernames being present on the system.
     *
     * @hook bc_security_status_obvious_usernames Filters list of obvious usernames to check and report.
	 */
	private function renderNoObviousUsernamesStatus()
    {
		$obvious_usernames = apply_filters(Hooks::OBVIOUS_USERNAMES, ['admin', 'administrator']);

		$ok = !\BlueChip\Security\Core\Utils::hasUsername($obvious_usernames);

		echo '<th><span class="dashicons dashicons-' . ($ok ? 'yes' : 'no') . '"></span></th>';
		echo '<th>' . __('No Obvious Usernames', 'bc-security') . '</th>';
		echo '<td>' . sprintf(__('Usernames like "admin" and "administrator" are often used in brute force attacks and <a href="%s">should be avoided</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity') . '</td>';
		echo '<td>' . esc_html__('None of the following usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $obvious_usernames) . '</em></td>';
	}
}
