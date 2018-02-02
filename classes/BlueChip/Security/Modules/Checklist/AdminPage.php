<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Modules\Hardening;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-checklist';


    /**
     * @var \BlueChip\Security\Modules\Checklist\Manager
     */
    private $checklist_manager;


    /**
     * @param \BlueChip\Security\Modules\Checklist\Manager $checklist_manager
     */
    public function __construct(Manager $checklist_manager)
    {
        $this->page_title = _x('Security Checklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Checklist', 'Dashboard menu item name', 'bc-security');

        $this->checklist_manager = $checklist_manager;
    }


    /**
     * Output admin page.
     */
    public function printContents()
    {
        echo '<div class="wrap">';

        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>';
        /* translators: %s: tick icon */
        echo sprintf(esc_html__('The more %s you have, the better!'), '<span class="dashicons dashicons-yes"></span>');
        echo '</p>';

        echo '<table class="wp-list-table widefat striped">';

        $checks = $this->checklist_manager->getChecks();

        foreach ($checks as $check) {
            if ($check->makesSense()) {
                $this->printCheckRow($check);
            }
        }

        echo '</table>';

        echo '<p>';
        echo sprintf(
            /* translators: %s: link to hardening options */
            esc_html__('You might also want to enable some other %s.', 'bc-security'),
            sprintf(
                '<a href="%s">%s</a>',
                Hardening\AdminPage::getPageUrl(),
                esc_html__('hardening options', 'bc-security')
            )
        );
        echo '</p>';

        echo '</div>';
    }


    /**
     * Output single table row with status information for given $check.
     *
     * @param \BlueChip\Security\Modules\Checklist\Check $check Check to evaluate and display results of.
     */
    private function printCheckRow(Check $check)
    {
        // Run check and get result;
        $result = $check->run();

        // Get result status and message.
        $status = $result->getStatus();
        $message = $result->getMessage();

        echo '<tr>';

        // Status may be undetermined, in such case render no icon.
        echo '<th>' . (is_bool($status) ? ('<span class="dashicons dashicons-' . ($status ? 'yes' : 'no') . '"></span>') : '' ) . '</th>';
        // Name should be short and descriptive and without HTML tags.
        echo '<th>' . esc_html($check->getName()) . '</th>';
        // Allow for HTML tags in $description.
        echo '<td>' . $check->getDescription() . '</td>';
        // Allow for HTML tags in result $message.
        echo '<td>' . $message . '</td>';

        echo '</tr>';
    }
}
