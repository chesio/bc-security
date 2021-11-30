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
     * @var string Regular expression to match link elements.
     *
     * @internal Does not have to be bullet-proof - it is only used to parse HTML generated by the plugin itself.
     */
    private const LINK_REGEX = '#<a\s+[^>]*href="([^"]+)"[^>]*>(.+)<\/a>#iU';


    /**
     * Add some boilerplate to $subject and $message and send notification via wp_mail().
     *
     * @see wp_mail()
     *
     * @param array|string $to Email address(es) of notification recipient(s).
     * @param string $subject Subject of notification.
     * @param array|string $message Body of notification.
     * @return bool True if notification has been sent successfully, false otherwise.
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
     * Strip any HTML tags from $message and add plugin boilerplate to it.
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
                wp_date(get_option('date_format')),
                wp_date(get_option('time_format'))
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

        return \implode(self::EOL, \array_merge($boilerplate_intro, self::stripTags($message), $boilerplate_outro));
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


    /**
     * Convert HTML message into plain text message.
     *
     * For any matched link element keep its URL and print list of all matched URLs after the message.
     *
     * Example input:
     * This is <strong>HTML text</strong> with a <a href="https://www.example.com">dummy link</a>.
     * And <a href="https://www.one-more-example.com/">one more <em>dummy link</em></a>.
     *
     * Example output:
     * This is HTML text with a dummy link [1].
     * And one more dummy link [2].
     *
     * [1] https://www.example.com
     * [2] https://www.one-more-example.com/
     *
     * @param array $message Message as list of strings with HTML tags.
     * @return array Message as list of strings without HTML tags with optional URL index appended.
     */
    private static function stripTags(array $message): array
    {
        // List of URLs extracted from $message.
        $urls = [];

        // Strip all HTML elements from message:
        // - match any link elements: push their href attributes into $urls list, replace element with text and index number
        // - strip any remaining tags
        $message_without_html_elements = \array_map(
            function (string $line) use (&$urls): string {
                return $line === '' ? '' : \strip_tags(
                    \preg_replace_callback(
                        self::LINK_REGEX,
                        function (array $matches) use (&$urls): string {
                            \array_push($urls, $matches[1]);
                            // Link text followed by link index.
                            return \sprintf('%s [%d]', $matches[2], \count($urls));
                        },
                        $line
                    )
                );
            },
            $message
        );

        if ($urls === []) {
            return $message_without_html_elements;
        }

        // Build links index...
        $links_index = \array_map(
            function (int $index, string $url): string {
                return \sprintf('[%d] %s', $index + 1, $url);
            },
            \array_keys($urls),
            \array_values($urls)
        );

        // ...and add it to the message.
        return \array_merge($message_without_html_elements, [''], $links_index);
    }
}
