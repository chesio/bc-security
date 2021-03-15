<?php

namespace BlueChip\Security\Modules\Hardening;

class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * bool: Disable pingbacks? [No]
     */
    public const DISABLE_PINGBACKS = 'disable_pingbacks';

    /**
     * bool: Disable XML RPC methods that require authentication? [No]
     */
    public const DISABLE_XML_RPC = 'disable_xml_rpc';

    /**
     * bool: Disable application passwords feature? [No]
     */
    public const DISABLE_APPLICATION_PASSWORDS = 'disable_application_passwords';

    /**
     * bool: Disable users listings via REST API `/wp/v2/users` endpoint and author scan via author=N query? [No]
     */
    public const DISABLE_USERNAMES_DISCOVERY = 'disable_usernames_discovery';

    /**
     * bool: Check existing passwords against Pwned Passwords database? [No]
     */
    public const CHECK_PASSWORDS = 'check_passwords';

    /**
     * bool: Validate new/updated passwords against Pwned Passwords database? [No]
     */
    public const VALIDATE_PASSWORDS = 'validate_passwords';

    /**
     * @var array Default values for all settings.
     */
    protected const DEFAULTS = [
        self::DISABLE_PINGBACKS => false,
        self::DISABLE_XML_RPC => false,
        self::DISABLE_APPLICATION_PASSWORDS => false,
        self::DISABLE_USERNAMES_DISCOVERY => false,
        self::CHECK_PASSWORDS => false,
        self::VALIDATE_PASSWORDS => false,
    ];
}
