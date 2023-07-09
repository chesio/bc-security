<?php

namespace BlueChip\Security\Modules\ScannerBlocker;

use BlueChip\Security\Core\Settings as CoreSettings;

class Settings extends CoreSettings
{
    /**
     * @var string List of bad request patterns that trigger ban [array:empty]
     */
    public const BAD_REQUEST_PATTERNS = 'bad_request_patterns';

    /**
     * @var string Duration of ban in seconds [int:3600]
     */
    public const BAN_DURATION = 'ban_duration';


    /**
     * @var string Character that signals comment line.
     */
    public const BAD_REQUEST_PATTERN_COMMENT_PREFIX = '#';


    /**
     * @var array<string,mixed> Default values for all settings.
     */
    protected const DEFAULTS = [
        self::BAD_REQUEST_PATTERNS => [],
        self::BAN_DURATION => HOUR_IN_SECONDS,
    ];

    /**
     * @var array<string,callable> Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::BAD_REQUEST_PATTERNS => [self::class, 'parseList'],
    ];


    /**
     * Get filtered list of usernames to be immediately locked out during login.
     *
     * @hook \BlueChip\Security\Modules\Login\Hooks::USERNAME_BLACKLIST
     *
     * @return string[]
     */
    public function getBadRequestPatterns(): array
    {
        return apply_filters(Hooks::BAD_REQUEST_PATTERNS, $this->removeComments($this->data[self::BAD_REQUEST_PATTERNS]));
    }


    /**
     * @return int Ban duration in seconds.
     */
    public function getBanDuration(): int
    {
        return $this->data[self::BAN_DURATION] * MINUTE_IN_SECONDS;
    }


    /**
     * @param string[] $bad_request_patterns
     *
     * @return string[]
     */
    private function removeComments(array $bad_request_patterns): array
    {
        return \array_filter(
            $bad_request_patterns,
            fn (string $pattern): bool => str_starts_with($pattern, self::BAD_REQUEST_PATTERN_COMMENT_PREFIX)
        );
    }
}
