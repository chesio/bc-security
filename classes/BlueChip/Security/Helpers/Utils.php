<?php

declare(strict_types=1);

namespace BlueChip\Security\Helpers;

abstract class Utils
{
    /**
     * Terminate script execution via wp_die(), pass 503 as return code.
     *
     * @link https://httpstatusdogs.com/503-service-unavailable
     *
     * @param string $ip_address Remote IP address to include in error message [optional].
     */
    public static function blockAccessTemporarily(string $ip_address = ''): void
    {
        $error_msg = empty($ip_address)
            ? esc_html__('Access from your device has been temporarily disabled for security reasons.', 'bc-security')
            : \sprintf(esc_html__('Access from your IP address %1$s has been temporarily disabled for security reasons.', 'bc-security'), \sprintf('<em>%s</em>', $ip_address))
        ;
        //
        wp_die($error_msg, __('Service Temporarily Unavailable', 'bc-security'), 503);
    }
}
