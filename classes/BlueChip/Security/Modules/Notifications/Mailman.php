<?php

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Modules\Notifications\AdminPage;

abstract class Mailman
{
    /**
     * @var string End-of-line character for email body.
     */
    private const EOL = "\r\n";

    /**
     * Add some boilerplate to $subject and $message and send notification via wp_mail().
     *
     * @see wp_mail()
     *
     * @param array|string $to Email address(es) of notification recipient(s).
     * @param string $subject Subject of notification.
     * @param array|string $message Body of notification.
     * @return bool True, if notification has been sent successfully, false otherwise.
     */
    public static function send($to, string $subject, $message): bool
    {
        return \wp_mail(
            $to,
            self::formatSubject($subject),
            self::formatMessage(\is_array($message) ? $message : [$message])
        );
    }


    /**
     * Add plugin boilerplate to $message.
     *
     * @param array $message Message body as list of lines.
     * @return string
     */
    private static function formatMessage(array $message): string
    {
        $boilerplate_intro = [
            \sprintf(
                __('This email was sent from your website "%1$s" by BC Security plugin on %2$s at %3$s.'),
                // Blog name must be decoded, see: https://github.com/chesio/bc-security/issues/86
                wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
                date_i18n('d.m.Y'),
                date_i18n('H:i:s')
            ),
            '',
        ];

        $boilerplate_outro = [
            '',
            \sprintf(
                __('To change your notification settings, visit: %s', 'bc-security'),
                AdminPage::getPageUrl()
            ),
        ];

        return \implode(self::EOL, \array_merge($boilerplate_intro, $message, $boilerplate_outro));
    }


    /**
     * Prepare subject for email (prepend site name and "BC Security Alert").
     *
     * @param string $subject
     * @return string
     */
    private static function formatSubject(string $subject): string
    {
        // Blog name must be decoded, see: https://github.com/chesio/bc-security/issues/86
        return \sprintf('[%s | %s] %s', wp_specialchars_decode(get_option('blogname'), ENT_QUOTES), __('BC Security Alert', 'bc-security'), $subject);
    }
}
