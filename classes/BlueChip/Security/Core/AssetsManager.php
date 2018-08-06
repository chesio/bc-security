<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core;

class AssetsManager
{
    /**
     * @var string Relative path to directory with CSS assets.
     */
    const CSS_ASSETS_DIRECTORY_PATH = 'assets/css/';

    /**
     * @var string Relative path to directory with JavaScript assets.
     */
    const JS_ASSETS_DIRECTORY_PATH = 'assets/js/';

    /**
     * @var string Absolute path to main plugin file.
     */
    private $plugin_filename;


    /**
     * @param string $plugin_filename Absolute path to main plugin file.
     */
    public function __construct(string $plugin_filename)
    {
        $this->plugin_filename = $plugin_filename;
    }


    /**
     * @param string $filename Asset filename (ie. asset.js).
     * @return string Absolute path to the asset.
     */
    public function getScriptFilePath(string $filename): string
    {
        return implode('', [plugin_dir_path($this->plugin_filename), self::JS_ASSETS_DIRECTORY_PATH, $filename]);
    }


    /**
     * @param string $filename Asset filename (ie. asset.js).
     * @return string URL of the asset.
     */
    public function getScriptFileUrl(string $filename): string
    {
        return implode('', [plugin_dir_url($this->plugin_filename), self::JS_ASSETS_DIRECTORY_PATH, $filename]);
    }


    /**
     * @param string $filename Asset filename (ie. asset.css).
     * @return string Absolute path to the asset.
     */
    public function getStyleFilePath(string $filename): string
    {
        return implode('', [plugin_dir_path($this->plugin_filename), self::CSS_ASSETS_DIRECTORY_PATH, $filename]);
    }


    /**
     * @param string $filename Asset filename (ie. asset.css).
     * @return string URL of the asset.
     */
    public function getStyleFileUrl(string $filename): string
    {
        return implode('', [plugin_dir_url($this->plugin_filename), self::CSS_ASSETS_DIRECTORY_PATH, $filename]);
    }
}
