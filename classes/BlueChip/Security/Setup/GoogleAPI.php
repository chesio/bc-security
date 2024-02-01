<?php

declare(strict_types=1);

namespace BlueChip\Security\Setup;

class GoogleAPI
{
    /**
     * @var string Google API key
     */
    private string $key = '';


    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->key = $settings[Settings::GOOGLE_API_KEY];
    }


    /**
     * @return string Google API key as set by `BC_SECURITY_GOOGLE_API_KEY` constant or empty string if constant is not set.
     */
    public static function getStaticKey(): string
    {
        return \defined('BC_SECURITY_GOOGLE_API_KEY') ? BC_SECURITY_GOOGLE_API_KEY : '';
    }


    /**
     * Get API key configured either by constant or backend setting.
     *
     * @return string
     */
    public function getKey(): string
    {
        return self::getStaticKey() ?: $this->key;
    }
}
