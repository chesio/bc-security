<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;

class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var array<string,int> Default values for all settings.
     */
    protected const DEFAULTS = [
        AmazonWebServices::class => Scope::ANY,
    ];

    /**
     * @var array<string,callable> Custom sanitizers.
     */
    protected const SANITIZERS = [
        AmazonWebServices::class => [self::class, 'sanitizeAccessScope'],
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

    /**
     * @param string $class Source class
     *
     * @return bool True if source with $class is enabled in settings, false otherwise.
     */
    public function isEnabled(string $class): bool
    {
        return $this->data[$class] !== Scope::ANY;
    }
}
