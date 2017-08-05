<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Modules\Notifications\AdminPage;


abstract class Mailman
{
    /**
     * @var string End-of-line character for email body.
     */
    const EOL = "\r\n";

    /**
     * Add some boilerplate to $subject and $message and send notification via wp_mail().
     *
     * @see wp_mail()
     *
     * @param array|string $to Email address(es) of notification recipient(s).
     * @param string $subject Subject of notification.
     * @param array|string $message Body of notification.
     * @return bool True, if notifications was sent successfully, false otherwise.
     */
    public static function send($to, $subject, $message)
    {
        return wp_mail(
            $to,
            self::formatSubject($subject),
            self::formatMessage(is_array($message) ? $message : [$message])
        );
    }


    /**
     * Add plugin boilerplate to $message.
     *
     * @param array $message Message body as list of lines.
     * @return string
     */
    private static function formatMessage(array $message)
    {
        $boilerplate_intro = [
            sprintf(
                __('This email was sent from your website "%1$s" by BC Security plugin on %2$s at %3$s.'),
                get_option('blogname'),
                date_i18n('d.m.Y'),
                date_i18n('H:i:s')
            ),
            '',
        ];

        $boilerplate_outro = [
            '',
            sprintf(
                __('To change your notification settings, visit: %s', 'bc-security'),
                AdminPage::getPageUrl(AdminPage::SLUG)
            ),
        ];

        return implode(self::EOL, array_merge($boilerplate_intro, $message, $boilerplate_outro));
    }


    /**
     * Prepare subject for email (prepend site name and "BC Security Alert").
     *
     * @param string $subject
     * @return string
     */
    private static function formatSubject($subject)
    {
        return sprintf('[%s | %s] %s', get_option('blogname'), __('BC Security Alert', 'bc-security'), $subject);
    }
}
