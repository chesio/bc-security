<?php

namespace BlueChip\Security\Modules\Tools;

use BlueChip\Security\Helpers\AdminNotices;
use BlueChip\Security\Setup;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-tools';

    /**
     * @var string
     */
    const EXPORT_ACTION = 'export-settings';

    /**
     * @var string
     */
    const IMPORT_ACTION = 'import-settings';

    /**
     * @var string
     */
    const RESET_ACTION = 'reset-settings';


    /**
     * @var \BlueChip\Security\Core\Settings[] Plugin settings collection
     */
    private $settings = [];


    /**
     * @param \BlueChip\Security\Core\Settings[] $settings Plugin settings collection
     */
    public function __construct(array $settings)
    {
        $this->page_title = _x('Tools', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Tools', 'Dashboard menu item name', 'bc-security');

        $this->settings = $settings;
    }


    public function loadPage()
    {
        $this->processActions();
    }


    public function printContents()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';

        $this->printExportForm();
        echo '<hr>';
        $this->printImportForm();
        echo '<hr>';
        $this->printResetForm();

        echo '</div>';
    }


    private function printExportForm()
    {
        echo '<h2>' . esc_html__('Export settings', 'bc-security') . '</h2>';
        echo '<p>' . esc_html__('Create JSON file with plugin settings that can be used as backup or to clone the settings to another installation.', 'bc-security') . '</p>';
        echo '<form method="post" class="bc-security">';
        // Form nonce
        wp_nonce_field(self::EXPORT_ACTION, self::NONCE_NAME);
        // Submit button
        submit_button(__('Export settings', 'bc-security'), 'primary', self::EXPORT_ACTION, true);
        echo '</form>';
    }


    private function printImportForm()
    {
        echo '<h2>' . esc_html__('Import settings', 'bc-security') . '</h2>';
        echo '<p>' . esc_html__('Import only JSON files created with the same version of the plugin!', 'bc-security') . '</p>';
        echo '<form method="post" class="bc-security" enctype="multipart/form-data">';
        // Form nonce
        wp_nonce_field(self::IMPORT_ACTION, self::NONCE_NAME);
        // File input
        echo '<label for="tools-import-file">' . esc_html__('Select file to import:', 'bc-security') . '</label><br>';
        echo '<input type="file" id="tools-import-file" name="import-file">';
        // Submit button
        submit_button(__('Import settings', 'bc-security'), 'primary', self::IMPORT_ACTION, true);
        echo '</form>';
    }


    private function printResetForm()
    {
        echo '<h2>' . esc_html__('Reset settings', 'bc-security') . '</h2>';
        echo '<p>';
        echo \sprintf(
            /* translators: %s: link to plugin setup page */
            esc_html__('Set all plugin settings (including %s) back to their default values.', 'bc-security'),
            \sprintf(
                '<a href="%s">%s</a>',
                Setup\AdminPage::getPageUrl(),
                esc_html__('connection type', 'bc-security')
            )
        );
        echo '</p>';
        echo '<form method="post" class="bc-security">';
        // Form nonce
        wp_nonce_field(self::RESET_ACTION, self::NONCE_NAME);
        // Submit button
        submit_button(__('Reset settings', 'bc-security'), 'primary', self::RESET_ACTION, true);
        echo '</form>';
    }


    /**
     * Dispatch any action that is indicated by POST data (form submission).
     */
    private function processActions()
    {
        $nonce = \filter_input(INPUT_POST, self::NONCE_NAME, FILTER_SANITIZE_STRING);
        if (empty($nonce)) {
            // No nonce, no action.
            return;
        }

        if (isset($_POST[self::EXPORT_ACTION]) && wp_verify_nonce($nonce, self::EXPORT_ACTION)) {
            // Export settings to a file.
            $this->processExportAction();
        }

        if (isset($_POST[self::IMPORT_ACTION]) && wp_verify_nonce($nonce, self::IMPORT_ACTION)) {
            // Import settings from provided file.
            $this->processImportAction();
        }

        if (isset($_POST[self::RESET_ACTION]) && wp_verify_nonce($nonce, self::RESET_ACTION)) {
            // Reset all settings to default values.
            $this->processResetAction();
        }
    }


    private function processExportAction()
    {
        $export = [];

        foreach ($this->settings as $settings) {
            $export[$settings->getOptionName()] = $settings->get();
        }

        // Send headers.
        $file_name = 'bc-security-export-' . \date('Y-m-d') . '.json';
        \header("Content-Disposition: attachment; filename={$file_name}");
        \header("Content-Type: application/json; charset=utf-8");

        // Send content.
        echo \json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }


    private function processImportAction()
    {
        $import_file = $_FILES['import-file'];

        // Validate file upload.
        if (empty($import_file['size'])) {
            AdminNotices::add(__('No file selected.', 'bc-security'), AdminNotices::ERROR);
            return;
        }
        if ($import_file['error']) {
            AdminNotices::add(__('File failed to upload. Please try again.', 'bc-security'), AdminNotices::ERROR);
            return;
        }
        if (\pathinfo($import_file['name'], PATHINFO_EXTENSION) !== 'json') {
            AdminNotices::add(__('Incorrect file type!', 'bc-security'), AdminNotices::ERROR);
            return;
        }

        // Read the file.
        if (empty($json = \file_get_contents($import_file['tmp_name']))) {
            AdminNotices::add(__('File could not be read!', 'bc-security'), AdminNotices::ERROR);
            return;
        }

        // Parse JSON.
        if (empty($import = \json_decode($json, true))) { // true -> convert objects into associative arrays
            AdminNotices::add(__('File is either empty or corrupted!', 'bc-security'), AdminNotices::ERROR);
            return;
        }

        $status = true;

        foreach ($this->settings as $settings) {
            $option_name = $settings->getOptionName();
            if (!isset($import[$option_name])) {
                $status = false;
                continue;
            }

            $data = $import[$option_name];
            if (!\is_array($data)) {
                $status = false;
                continue;
            }

            $settings->set($data);
        }

        if ($status) {
            AdminNotices::add(
                __('Plugin settings have been imported successfully.', 'bc-security'),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Some or all plugin settings could not be updated. Make sure you are importing file that has been created by the same version of the plugin.', 'bc-security'),
                AdminNotices::WARNING
            );
        }
    }


    private function processResetAction()
    {
        foreach ($this->settings as $settings) {
            $settings->reset();
        }

        AdminNotices::add(
            __('Plugin settings have been reset to their defaults.', 'bc-security'),
            AdminNotices::SUCCESS
        );
    }
}
