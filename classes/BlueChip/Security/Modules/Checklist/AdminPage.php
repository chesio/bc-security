<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Helpers\AjaxHelper;
use BlueChip\Security\Helpers\FormHelper;
use BlueChip\Security\Modules\Hardening;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /** Page has assets */
    use \BlueChip\Security\Core\Admin\PageWithAssets;

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
     * @param \BlueChip\Security\Core\AssetsManager $assets_manager
     */
    public function __construct(Manager $checklist_manager, AutorunSettings $settings, \BlueChip\Security\Core\AssetsManager $assets_manager)
    {
        $this->page_title = _x('Security Checklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Checklist', 'Dashboard menu item name', 'bc-security');

        $this->checklist_manager = $checklist_manager;

        $this->useAssetsManager($assets_manager);
        $this->useSettings($settings);
    }


    public function loadPage()
    {
        $this->enqueueCssAssets(['checklist' => 'checklist.css',]);
        $this->enqueueJsAssets(['checklist' => 'checklist.js',]);
        AjaxHelper::injectSetup(
            'checklist',
            'bc_security_checklist',
            Manager::ASYNC_CHECK_ACTION,
            [
                'messages' => [
                    'check_is_running' => '<em>' . esc_html__('Check is running ...', 'bc-security') . '</em>',
                    'check_failed' => '<em>' . esc_html__('Check failed! Please try again later.', 'bc-security') . '</em>',
                ],
            ]
        );

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

        echo '<p>';
        echo '<button type="button" id="bcs-run-checks" class="button button-large">' . esc_html__('Run all checks', 'bc-security') . '</button>';
        echo '</p>';

        echo '<form method="post" action="' . admin_url('options.php') .'">';

        echo '<table class="wp-list-table widefat striped bcs-checklist--initial" id="bcs-checklist">';

        echo '<thead>';
        $this->printLabelsRow();
        echo '<thead>';

        echo '<tbody>';

        $checks = $this->checklist_manager->getChecks(true);

        foreach ($checks as $check) {
            $this->printCheckRow($check);
        }

        echo '</tbody>';

        echo '<tfoot>';
        $this->printLabelsRow();
        echo '<tfoot>';

        echo '</table>';

        echo '<p>';
        echo sprintf(
            /* translators: %s: inline button with "select all passing checks" label */
            esc_html__('You can let BC Security monitor the checklist automatically. Just select the checks you want to monitor or simply %s and click the button below.', 'bc-security'),
            '<button type="button" id="bcs-mark-passing-checks" disabled="disabled">' . esc_html__('select all passing checks', 'bc-security') . '</button>'
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
     * Output single table row with data labels.
     */
    private function printLabelsRow()
    {
        echo '<tr>';
        echo '<th class="bcs-check__status">' . esc_html__('Status', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Name', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Monitor', 'bc-security') . '</th>';
        echo '<td>' . esc_html__('Description', 'bc-security') . '</td>';
        echo '<td class="bcs-check__message">' . esc_html__('Result', 'bc-security') . '</td>';
        echo '<tr>';
    }


    /**
     * Output single table row with $check data.
     *
     * @param \BlueChip\Security\Modules\Checklist\Check $check
     */
    private function printCheckRow(Check $check)
    {
        $check_id = $check->getId();

        echo '<tr class="bcs-check" data-check-id="' . $check_id . '">';

        // Status icon.
        echo '<th class="bcs-check__status"><span class="dashicons"></span></th>';
        // Name should be short and descriptive and without HTML tags.
        echo '<th>' . esc_html($check->getName()) . '</th>';
        // Background monitoring toggle.
        echo '<th>';
        if (isset($this->settings[$check_id])) {
            FormHelper::printCheckbox($this->getFieldBaseProperties($check_id, intval($this->settings[$check_id])));
        }
        echo '</th>';
        // Allow for HTML tags in $description.
        echo '<td>' . $check->getDescription() . '</td>';
        // Check result message.
        echo '<td class="bcs-check__message">---</td>';

        echo '</tr>';
    }
}
