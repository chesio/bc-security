<?php

namespace BlueChip\Security\Modules\Hardening;

use BlueChip\Security\Modules\Access\Scope;

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
     * @var string Remove the option to log in with email and password? [bool:no]
     */
    public const DISABLE_LOGIN_WITH_EMAIL = 'disable_login_with_email';

    /**
     * @var string Remove the option to log in with username and password? [bool:no]
     */
    public const DISABLE_LOGIN_WITH_USERNAME = 'disable_login_with_username';

    /**
     * @var string Check existing passwords against Pwned Passwords database? [bool:no]
     */
    public const CHECK_PASSWORDS = 'check_passwords';

    /**
     * @var string Validate new/updated passwords against Pwned Passwords database? [bool:no]
     */
    public const VALIDATE_PASSWORDS = 'validate_passwords';

    /**
     * @var string Block requests from Amazon web services? [int:0]
     */
    public const BLOCK_REQUESTS_FROM_AMAZON_WEB_SERVICES = 'block_requests_from_aws';

    /**
     * @var array<string,bool> Default values for all settings.
     */
    protected const DEFAULTS = [
        self::DISABLE_PINGBACKS => false,
        self::DISABLE_XML_RPC => false,
        self::DISABLE_APPLICATION_PASSWORDS => false,
        self::DISABLE_USERNAMES_DISCOVERY => false,
        self::DISABLE_LOGIN_WITH_EMAIL => false,
        self::DISABLE_LOGIN_WITH_USERNAME => false,
        self::CHECK_PASSWORDS => false,
        self::VALIDATE_PASSWORDS => false,
        self::BLOCK_REQUESTS_FROM_AMAZON_WEB_SERVICES => Scope::ANY,
    ];

    /**
     * @var array Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::BLOCK_REQUESTS_FROM_AMAZON_WEB_SERVICES => [self::class, 'sanitizeAccessScope'],
    ];

    /**
     * Sanitize lock scope values. Allow only expected values.
     *
     * @param int $value
     * @param int $default
     *
     * @return int
     */
    public static function sanitizeAccessScope(int $value, int $default): int
    {
        return \in_array($value, Scope::enlist(), true) ? $value : $default;
    }
}
