<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\BadRequestsBanner;

use BlueChip\Security\Core\Settings as CoreSettings;

class Settings extends CoreSettings
{
    /**
     * @var string Is built-in rule "Archive files" active? [bool:no]
     */
    public const BUILT_IN_RULE_ARCHIVE_FILES = BuiltInRules::ARCHIVE_FILES;

    /**
     * @var string Is built-in rule "Backup files" active? [bool:no]
     */
    public const BUILT_IN_RULE_BACKUP_FILES = BuiltInRules::BACKUP_FILES;

    /**
     * @var string Is built-in rule "PHP files" active? [bool:no]
     */
    public const BUILT_IN_RULE_PHP_FILES = BuiltInRules::PHP_FILES;

    /**
     * @var string Is built-in rule "Readme files" active? [bool:no]
     */
    public const BUILT_IN_RULE_README_FILES = BuiltInRules::README_FILES;

    /**
     * @var string List of custom rules (bad request patterns) that trigger ban [array:empty]
     */
    public const BAD_REQUEST_PATTERNS = 'bad_request_patterns';

    /**
     * @var string Duration of ban in minutes [int:60]
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
        self::BUILT_IN_RULE_ARCHIVE_FILES => false,
        self::BUILT_IN_RULE_BACKUP_FILES => false,
        self::BUILT_IN_RULE_PHP_FILES => false,
        self::BUILT_IN_RULE_README_FILES => false,
        self::BAD_REQUEST_PATTERNS => [],
        self::BAN_DURATION => 60,
    ];


    /**
     * @return BanRule[] List of active ban rules.
     */
    public function getActiveBanRules(): array
    {
        $ban_rules = [];

        // Fill built in rules first.
        foreach (BuiltInRules::enlist() as $identifier => $ban_rule) {
            if ($this->data[$identifier]) {
                $ban_rules[] = $ban_rule;
            }
        }

        // Fill custom rules second.
        foreach ($this->getBadRequestPatterns() as $pattern) {
            $ban_rules[] = new BanRule(sprintf(__('Custom rule: %s', 'bc-security'), $pattern), $pattern);
        }

        return $ban_rules;
    }


    /**
     * @return int Ban duration in seconds.
     */
    public function getBanDuration(): int
    {
        return $this->data[self::BAN_DURATION] * MINUTE_IN_SECONDS;
    }


    /**
     * @return string[]
     */
    private function getBadRequestPatterns(): array
    {
        return apply_filters(
            Hooks::BAD_REQUEST_CUSTOM_PATTERNS,
            $this->removeComments($this->data[self::BAD_REQUEST_PATTERNS])
        );
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
            fn (string $pattern): bool => !\str_starts_with($pattern, self::BAD_REQUEST_PATTERN_COMMENT_PREFIX)
        );
    }
}
