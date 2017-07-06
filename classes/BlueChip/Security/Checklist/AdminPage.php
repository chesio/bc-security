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

		echo '<table class="wp-list-table widefat">';

        echo '<tr class="">';
		$this->renderPhpFileEditationStatus();
        echo '</tr>';

        echo '<tr class="alternate">';
		$this->renderTablePrefixStatus();
        echo '</tr>';

        echo '<tr class="">';
		$this->renderNoObviousUsernamesStatus();
        echo '</tr>';

		echo '</table>';

        echo '<p>';

        // TODO: How to escape text with link?
        echo sprintf(
            __('You might also want to enable some other <a href="%s">hardening options</a>.', 'bc-security'),
            \BlueChip\Security\Core\AdminPage::getPageUrl(\BlueChip\Security\Hardening\AdminPage::SLUG)
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
	 * Render status info about non-default table prefix.
	 * @global string $table_prefix
	 */
	private function renderTablePrefixStatus()
    {
		global $table_prefix;
		//
		$hardened = $table_prefix !== 'wp_';

		echo '<th><span class="dashicons dashicons-' . ($hardened ? 'yes' : 'no') . '"></span></th>';
		echo '<th>' . __('Non-default Table Prefix', 'bc-security') . '</th>';
		echo '<td>' . sprintf(__('As an additional measure against blind SQL injections attacks, it is generally recommended to <a href="%1$s">change database tables prefix</a> to a non-default value, although there are <a href="%2$s">disagreeing opinions</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity', 'https://www.wordfence.com/blog/2016/12/wordpress-table-prefix/') . '</td>';
		echo '<td>' . esc_html__('Your table prefix is:', 'bc-security') . ' <em>' . $table_prefix . '</em></td>';
	}


	/**
	 * Render status info about no obvious usernames being present on the system.
     *
     * @filter bc_security_obvious_usernames Filters list of obvious usernames to check and report.
	 */
	private function renderNoObviousUsernamesStatus()
    {
		$obvious_usernames = apply_filters(Hooks::OBVIOUS_USERNAMES, ['admin', 'administrator']);
		//
		$ok = !\BlueChip\Security\Core\Utils::hasUsername($obvious_usernames);

		echo '<th><span class="dashicons dashicons-' . ($ok ? 'yes' : 'no') . '"></span></th>';
		echo '<th>' . __('No Obvious Usernames', 'bc-security') . '</th>';
		echo '<td>' . sprintf(__('Usernames like "admin" and "administrator" are often used in brute force attacks and <a href="%s">should be avoided</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity') . '</td>';
		echo '<td>' . esc_html__('None of the following usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $obvious_usernames) . '</em></td>';
	}
}
