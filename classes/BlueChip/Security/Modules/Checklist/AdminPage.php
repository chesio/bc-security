<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Helpers\FormHelper;
use BlueChip\Security\Modules\Hardening;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;


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
     * @param \BlueChip\Security\Modules\Checklist\AutorunSettings $settings
     */
    public function __construct(Manager $checklist_manager, AutorunSettings $settings)
    {
        $this->page_title = _x('Security Checklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Checklist', 'Dashboard menu item name', 'bc-security');

        $this->checklist_manager = $checklist_manager;

        $this->useSettings($settings);
    }


    public function loadPage()
    {
        $this->displaySettingsErrors();
    }


    /**
     * Output admin page.
     */
    public function printContents()
    {
        echo '<div class="wrap">';

        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>';
        echo sprintf(
            /* translators: %s: tick icon */
            esc_html__('The more %s you have, the better!'),
            '<span class="dashicons dashicons-yes"></span>'
        );
        echo '</p>';

        echo '<form method="post" action="' . admin_url('options.php') .'">';

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
            /* translators: %s: inline button with "select all passing checks" label */
            esc_html__('You can let BC Security monitor the checklist automatically. Just select the checks you want to monitor or simply %s and click the button below.', 'bc-security'),
            '<button type="button" id="bc-security-mark-passing-checks">' . esc_html__('select all passing checks', 'bc-security') . '</button>'
        );

        // Output nonce, action and other hidden fields...
        $this->printSettingsFields();
        // ... and finally the submit button :)
        submit_button(__('Monitor selected checks in background', 'bc-security'));

        echo '</form>';

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

        $this->printInlineScript();
    }


    /**
     * Initialize settings page: register settings etc.
     */
    public function initPage()
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current.
        $this->setSettingsPage(self::SLUG);
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
        // Background monitoring state.
        echo '<th>';
        FormHelper::printCheckbox(array_merge(
            $this->getFieldBaseProperties($check->getId(), intval($this->settings[$check->getId()])),
            ['class' => $status === true ? 'status-passing' : '']
        ));
        echo '</th>';
        // Allow for HTML tags in $description.
        echo '<td>' . $check->getDescription() . '</td>';
        // Allow for HTML tags in result $message.
        echo '<td>' . $message . '</td>';

        echo '</tr>';
    }


    /**
     * Print inline script that powers "select all passing checks" button.
     */
    private function printInlineScript()
    {
        echo <<<INLINE
<script>
document.getElementById('bc-security-mark-passing-checks').addEventListener('click', function() {
   for (var i = 0; i < this.form.length; ++i) {
        if (this.form[i].type === 'checkbox' && this.form[i].classList.contains('status-passing')) {
            this.form[i].checked = true;
        }
   }
});;
</script>
INLINE;
    }
}
