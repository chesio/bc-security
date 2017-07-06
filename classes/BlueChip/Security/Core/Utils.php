<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core;

/**
 * Useful helper functions
 */
class Utils
{
	/**
     * @param array $usernames
	 * @return bool True, if any from given $usernames exists on the system.
	 */
	public static function hasUsername($usernames)
    {
		foreach ($usernames as $username) {
			$user = get_user_by('login', $username);
			if ($user) {
				return true;
			}
		}

		return false;
	}


	/**
	 * What type of request is this?
     *
	 * @param string $type One of: admin, ajax, cron, frontend or wp-cli.
	 * @return bool True, if current request is of given $type, false otherwise.
	 */
	public static function isRequest($type)
    {
		switch ($type) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined('DOING_AJAX') && DOING_AJAX;
			case 'cron':
				return defined('DOING_CRON') && DOING_CRON;
			case 'frontend':
				return (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) && !(defined('DOING_CRON') && DOING_CRON);
            case 'wp-cli':
                return defined('WP_CLI') && WP_CLI;
			default:
				_doing_it_wrong(__METHOD__, sprintf('Unknown request type: %s', $type), '0.1.0');
				return false;
		}
	}
}
