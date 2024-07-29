<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Core\Settings as CoreSettings;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;

class Settings extends CoreSettings
{
    /**
     * @var array<string,int> Default values for all settings.
     */
    protected const DEFAULTS = [
        AmazonWebServices::class => 0, // Unfortunately Scope::ANY->value is not constant expression.
    ];

    /**
     * @var array<string,callable> Custom sanitizers.
     */
    protected const SANITIZERS = [
        AmazonWebServices::class => [self::class, 'sanitizeAccessScope'],
    ];

    /**
     * Sanitize lock scope values. Allow only expected values.
     */
    public static function sanitizeAccessScope(int $value, int $default): int
    {
        return Scope::tryFrom((int) $value) ? ((int) $value) : $default;
    }

    /**
     * @param string $class Source class
     *
     * @return bool True if source with $class is enabled in settings, false otherwise.
     */
    public function isEnabled(string $class): bool
    {
        $access_scope = $this->getAccessScope($class);

        return ($access_scope instanceof Scope) && ($access_scope !== Scope::ANY);
    }

    public function getAccessScope(string $class): ?Scope
    {
        $value = $this[$class];
        return \is_int($value) ? Scope::tryFrom($value) : null;
    }
}
