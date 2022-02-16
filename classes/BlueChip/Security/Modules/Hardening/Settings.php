<?php

namespace BlueChip\Security\Modules\Hardening;

class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var string Disable pingbacks? [bool:no]
     */
    public const DISABLE_PINGBACKS = 'disable_pingbacks';

    /**
     * @var string Disable XML RPC methods that require authentication? [bool:no]
     */
    public const DISABLE_XML_RPC = 'disable_xml_rpc';

    /**
     * @var string Disable application passwords feature? [bool:no]
     */
    public const DISABLE_APPLICATION_PASSWORDS = 'disable_application_passwords';

    /**
     * @var string Disable users listings via REST API `/wp/v2/users` endpoint and author scan via author=N query? [bool:no]
     */
    public const DISABLE_USERNAMES_DISCOVERY = 'disable_usernames_discovery';

    /**
     * @var string Check existing passwords against Pwned Passwords database? [bool:no]
     */
    public const CHECK_PASSWORDS = 'check_passwords';

    /**
     * @var string Validate new/updated passwords against Pwned Passwords database? [bool:no]
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
