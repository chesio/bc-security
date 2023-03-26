<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Modules\Access\Scope;

class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var string Block requests from Amazon web services? [int:0]
     */
    public const AMAZON_WEB_SERVICES = 'amazon_web_services';

    /**
     * @var array<string,int> Default values for all settings.
     */
    protected const DEFAULTS = [
        self::AMAZON_WEB_SERVICES => Scope::ANY,
    ];

    /**
     * @var array<string,callable> Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::AMAZON_WEB_SERVICES => [self::class, 'sanitizeAccessScope'],
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
