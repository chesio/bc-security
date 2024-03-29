<?php

declare(strict_types=1);

namespace BlueChip\Security\Core\Admin;

use BlueChip\Security\Core\AssetsManager;

trait PageWithAssets
{
    private AssetsManager $assets_manager;


    protected function useAssetsManager(AssetsManager $assets_manager): void
    {
        $this->assets_manager = $assets_manager;
    }


    /**
     * @param array<string,string> $assets JS assets to enqueue in [ handle => filename ] format.
     */
    protected function enqueueJsAssets(array $assets): void
    {
        add_action('admin_enqueue_scripts', function () use ($assets) {
            foreach ($assets as $handle => $filename) {
                wp_enqueue_script(
                    $handle,
                    $this->assets_manager->getScriptFileUrl($filename),
                    ['jquery'],
                    (string) \filemtime($this->assets_manager->getScriptFilePath($filename)),
                    true
                );
            }
        }, 10, 0);
    }


    /**
     * @param array<string,string> $assets CSS assets to enqueue in [ handle => filename ] format.
     */
    protected function enqueueCssAssets(array $assets): void
    {
        add_action('admin_enqueue_scripts', function () use ($assets) {
            foreach ($assets as $handle => $filename) {
                wp_enqueue_style(
                    $handle,
                    $this->assets_manager->getStyleFileUrl($filename),
                    [],
                    (string) \filemtime($this->assets_manager->getStyleFilePath($filename))
                );
            }
        }, 10, 0);
    }
}
