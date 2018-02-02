<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * array: list of checks that should not be run automatically
     */
    const DISABLED_CHECKS = 'disabled_checks';


    /**
     * @param array $s
     * @return array
     */
    public function sanitize(array $s): array
    {
        return [
            self::DISABLED_CHECKS => $this->sanitizeDisabledChecks(
                isset($s[self::DISABLED_CHECKS]) && is_array($s[self::DISABLED_CHECKS]) ? $s[self::DISABLED_CHECKS] : []
            ),
        ];
    }


    /**
     * @param array $disabled
     * @return array A hashmap with [ (string) check_id => (bool) is_disabled ] values
     */
    private function sanitizeDisabledChecks(array $disabled): array
    {
        $check_ids = Manager::getIds();

        return array_map(
            function (string $check_id) use ($disabled): bool {
                return $disabled[$check_id] ?? false;
            },
            array_combine($check_ids, $check_ids) // Pass check IDs as values as well, so they can be used in callback.
        );
    }
}
