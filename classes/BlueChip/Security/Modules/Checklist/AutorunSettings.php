<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Every setting has a boolean value: true = perform check monitoring, false = do not perform check monitoring
 */
class AutorunSettings extends \BlueChip\Security\Core\Settings
{
    /**
     * @param array $s
     * @return array A hashmap with [ (string) check_id => (bool) is_monitoring_active ] values
     */
    public function sanitize(array $s): array
    {
        $check_ids = Manager::getIds();

        return array_map(
            function (string $check_id) use ($s): bool {
                return $s[$check_id] ?? false;
            },
            array_combine($check_ids, $check_ids) // Pass check IDs as values as well, so they can be used in callback.
        );
    }
}
