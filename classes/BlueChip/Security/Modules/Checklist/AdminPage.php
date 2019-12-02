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
     * @return int Number of meaningful checks that are monitored and failed the last time they have been executed.
     */
    public function getCount(): int
    {
        return \count($this->checklist_manager->getChecks(['meaningful' => true, 'monitored' => true, 'status' => false]));
    }


    /**
     * Output admin page.
     */
    public function printContents()
    {
        echo '<div class="wrap">';

        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>';
        echo \sprintf(
            /* translators: %s: tick icon */
            esc_html__('The more %s you have, the better!'),
            '<span class="dashicons dashicons-yes"></span>'
        );
        echo '</p>';

        echo '<p>';
        echo '<button type="button" class="button button-large bcs-run-checks" data-check-class="bcs-check">' . esc_html__('Run all checks', 'bc-security') . '</button>';
        echo '</p>';

        echo '<form method="post" action="' . admin_url('options.php') . '">';

        $this->printBasicChecksSection($this->checklist_manager->getBasicChecks());

        $this->printAdvancedChecksSection($this->checklist_manager->getAdvancedChecks());

        $this->printChecklistMonitoringSection();

        // Output nonce, action and other hidden fields...
        $this->printSettingsFields();
        // ... and finally the submit button :)
        submit_button(__('Monitor selected checks in background', 'bc-security'));

        echo '</form>';

        echo '<p>';
        echo \sprintf(
            /* translators: %s: link to hardening options */
            esc_html__('You might also want to enable some other %s.', 'bc-security'),
            \sprintf(
                '<a href="%s">%s</a>',
                Hardening\AdminPage::getPageUrl(),
                esc_html__('hardening options', 'bc-security')
            )
        );
        echo '</p>';

        echo '</div>';
    }


    /**
     * @param array $basic_checks
     */
    private function printBasicChecksSection(array $basic_checks)
    {
        echo '<h2>' . esc_html__('Basic checks', 'bc-security') . '</h2>';

        echo '<p>';
        echo esc_html__('Basic checks do not require any information from WordPress.org to proceed.', 'bc-security');
        echo '</p>';

        echo '<p>';
        echo '<button type="button" class="button button-large bcs-run-checks" data-check-class="bcs-check--basic">' . esc_html__('Run basic checks', 'bc-security') . '</button>';
        echo '</p>';

        $this->printChecklistTable($basic_checks, 'bcs-check--basic');
    }


    /**
     * @param array $advanced_checks
     */
    private function printAdvancedChecksSection(array $advanced_checks)
    {
        echo '<h2>' . esc_html__('Advanced checks', 'bc-security') . '</h2>';

        echo '<p>';
        echo esc_html__('In order to run advanced checks, a list of all installed plugins and their versions is shared with WordPress.org.', 'bc-security');
        echo '</p>';

        echo '<p>';
        echo '<button type="button" class="button button-large bcs-run-checks" data-check-class="bcs-check--advanced">' . esc_html__('Run advanced checks', 'bc-security') . '</button>';
        echo '</p>';

        $this->printChecklistTable($advanced_checks, 'bcs-check--advanced');
    }


    private function printChecklistMonitoringSection()
    {
        echo '<h2>' . esc_html__('Checklist monitoring', 'bc-security') . '</h2>';

        echo '<p>';
        echo esc_html__('You can let BC Security monitor the checklist automatically. Just select the checks you want to monitor:', 'bc-security');
        echo ' ';
        echo \implode(' ', [
            '<button type="button" id="bcs-mark-all-checks" disabled="disabled">' . esc_html__('select all', 'bc-security') . '</button>',
            '<button type="button" id="bcs-mark-no-checks" disabled="disabled">' . esc_html__('select none', 'bc-security') . '</button>',
            '<button type="button" id="bcs-mark-passing-checks" disabled="disabled">' . esc_html__('select only passing', 'bc-security') . '</button>',
        ]);
        echo '</p>';
    }


    private function printChecklistTable(array $checks, string $checks_class)
    {
        echo '<table class="wp-list-table widefat striped">';

        echo '<thead>';
        $this->printLabelsRow();
        echo '</thead>';

        echo '<tbody>';

        foreach ($checks as $check) {
            $this->printCheckRow($check, $checks_class);
        }

        echo '</tbody>';

        echo '<tfoot>';
        $this->printLabelsRow();
        echo '</tfoot>';

        echo '</table>';
    }


    /**
     * Output single table row with data labels.
     */
    private function printLabelsRow()
    {
        echo '<tr>';
        echo '<th>' . esc_html__('Monitor', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Name', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Description', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Last run', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Status', 'bc-security') . '</th>';
        echo '<th>' . esc_html__('Result', 'bc-security') . '</th>';
        echo '<tr>';
    }


    /**
     * Output single table row with $check data.
     *
     * @param \BlueChip\Security\Modules\Checklist\Check $check
     */
    private function printCheckRow(Check $check, string $check_class)
    {
        $check_id = $check::getId();
        $result = $check->getResult();
        $status = $result->getStatus();
        $status_class = \is_bool($status) ? ($status ? 'bcs-check--ok' : 'bcs-check--ko') : '';

        echo '<tr class="bcs-check ' . esc_attr($check_class) . ' ' . $status_class . '" data-check-id="' . esc_attr($check_id) . '">';

        // Background monitoring toggle.
        echo '<th>';
        if (isset($this->settings[$check_id])) {
            FormHelper::printCheckbox($this->getFieldBaseProperties($check_id, \intval($this->settings[$check_id])));
        }
        echo '</th>';
        // Name should be short and descriptive and without HTML tags.
        echo '<th>' . esc_html($check->getName()) . '</th>';
        // Allow for HTML tags in $description.
        echo '<td>' . $check->getDescription() . '</td>';
        // Last time the check has been run.
        echo '<td class="bcs-check__last-run">' . Helper::formatLastRunTimestamp($check) . '</td>';
        // Status icon.
        echo '<td class="bcs-check__status"><span class="dashicons"></span></td>';
        // Check result message.
        echo '<td class="bcs-check__message">' . $result->getMessageAsHtml() . '</td>';

        echo '</tr>';
    }
}
