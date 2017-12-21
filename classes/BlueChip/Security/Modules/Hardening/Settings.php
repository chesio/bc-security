<?php
/**
 * Hardening settings
 *
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Hardening;

class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * bool: Disable pingbacks? [No]
     */
    const DISABLE_PINGBACKS = 'disable_pingbacks';

    /**
     * bool: Disable XML RPC methods that require authentication? [No]
     */
    const DISABLE_XML_RPC = 'disable_xml_rpc';

    /**
     * bool: Disable REST API methods to anonymous users? [No]
     */
    const DISABLE_REST_API = 'disable_rest_api';


    /**
     * Sanitize settings array: only return known keys, provide default values for missing keys.
     *
     * @param array $s
     * @return array
     */
    public function sanitize(array $s): array
    {
        return [
            self::DISABLE_PINGBACKS
                => isset($s[self::DISABLE_PINGBACKS]) ? boolval($s[self::DISABLE_PINGBACKS]) : false,
            self::DISABLE_XML_RPC
                => isset($s[self::DISABLE_XML_RPC]) ? boolval($s[self::DISABLE_XML_RPC]) : false,
            self::DISABLE_REST_API
                => isset($s[self::DISABLE_REST_API]) ? boolval($s[self::DISABLE_REST_API]) : false,
        ];
    }
}
