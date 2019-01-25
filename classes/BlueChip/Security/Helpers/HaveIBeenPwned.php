<?php
/**
 * @package BC_Security
 */
namespace BlueChip\Security\Helpers;

/**
 * @link https://haveibeenpwned.com/
 */
abstract class HaveIBeenPwned
{
    /**
     * @var string URL of Pwned Passwords home page
     */
    const PWNEDPASSWORDS_HOME_URL = 'https://haveibeenpwned.com/Passwords';

    /**
     * @link https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange
     * @var string URL of Pwned Passwords API range search end-point
     */
    const PWNEDPASSWORDS_API_RANGE_SEARCH_URL = 'https://api.pwnedpasswords.com/range/';


    /**
     * @link https://haveibeenpwned.com/API/v2#PwnedPasswords
     * @param string $password Password to check.
     * @return bool True, if $password has been previously exposed in a data breach, false if not, null if check failed.
     */
    public static function hasPasswordBeenPwned(string $password): ?bool
    {
        $sha1 = sha1($password);

        // Only first 5 characters of the hash are required.
        $sha1_prefix = substr($sha1, 0, 5);

        $response = wp_remote_get(esc_url(self::PWNEDPASSWORDS_API_RANGE_SEARCH_URL . $sha1_prefix));

        if (wp_remote_retrieve_response_code($response) !== 200) {
            // Note: "there is no circumstance in which the API should return HTTP 404",
            // but of course remote request can always fail due network issues.
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            // Note: Should never happen, as there is a non-empty response for every prefix,
            // therefore return null (check failed) rather than false (check negative).
            return null;
        }

        // Every record has "hash_suffix:count" format.
        $records = explode(PHP_EOL, $body);
        foreach ($records as $record) {
            [$sha1_suffix, $count] = explode(':', $record);

            if ($sha1 === ($sha1_prefix . strtolower($sha1_suffix))) {
                return true; // Your password been pwned, my friend!
            }
        }

        return false; // Ok, you're fine.
    }
}
