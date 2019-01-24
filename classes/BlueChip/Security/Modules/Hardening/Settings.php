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
     * bool: Validate passwords against Pwned Passwords database? [No]
     */
    const VALIDATE_PASSWORDS = 'validate_passwords';

    /**
     * @var array Default values for all settings.
     */
    const DEFAULTS = [
        self::DISABLE_PINGBACKS => false,
        self::DISABLE_XML_RPC => false,
        self::DISABLE_REST_API => false,
        self::VALIDATE_PASSWORDS => false,
    ];
}
