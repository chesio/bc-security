# BC Security

Helps keeping WordPress websites secure.

## Requirements

* [PHP](https://secure.php.net/) 7.0 or newer
* [WordPress](https://wordpress.org/) 4.9 or newer

## Limitations

* BC Security has not been tested on WordPress multisite installation.
* BC Security is primarily being developed for Apache webserver and Unix-like environments.

## Features

### Checklist

BC Security features a checklist of common security practices. The list is split in two parts: basic and advanced checks.

#### Basic checks

Basic checks do not require any information from third party sources to run and thus do not leak any data about your website:

1. Is editation of plugin and theme PHP files disabled?
1. Are directory listings disabled?
1. Is execution of PHP files from uploads directory forbidden?
1. Is display of PHP errors off by default? This check is only run in production environment, ie. when `WP_ENV === 'production'`.
1. Is error log file not publicly available? This check is only run if both `WP_DEBUG` and `WP_DEBUG_LOG` constants are set to true.
1. Are there no common usernames like admin or administrator on the system?
1. Are user passwords hashed with some non-default hashing algorithm?

#### Advanced checks

Advanced checks depend on data that has to be fetched from external servers (in the moment from WordPress.org only). Because of this, advanced checks may leak some data about your website (in the moment list of installed plugins that have `readme.txt` file) and are slower to perform.

1. Are there no plugins installed that have been removed from [Plugin Directory](https://wordpress.org/plugins/)?

#### Checklist monitoring

Both basic and advanced checks can be run from a dedicated page in backend, but can be also configured to run periodically in the background. Basic checks are run in via single cron job, while each of advanced checks is run via separate cron job.

### WordPress hardening

BC Security allows you to:
1. Disable pingbacks
1. Disable XML RPC methods that require authentication
1. Disable access to REST API to anonymous users

### Checksums verification

BC Security once a day performs integrity check of WordPress core and plugin files. Any file that is evaluated as modified or unknown is [logged](#events-logging) and (optionally) reported via [email notification](#notifications).

WordPress core files verification is done in two phases:
1. Official md5 checksums from WordPress.org are used to determine if any of core files have been modified.
1. All files in root directory, `wp-admin` directory (including subdirectories) and `wp-includes` directory (including subdirectories) are checked against official checksums list to determine if the file is official (known) file.

Plugin files verification works only for plugins hosted at [WordPress Plugins](https://wordpress.org/plugins/) directory. The verification process is akin to the core files verification, although the API is slightly different (see [related Trac ticket](https://meta.trac.wordpress.org/ticket/3192) and [specification](https://docs.google.com/document/d/14-SMpaPtDGEBm8hE9ZwnA-vik5OvECDig32KqX8uFlg/edit)).

### Login security

1. BC Security allows you to limit number of login attempts from single IP address. Implementation of this feature is heavily inspired by popular [Limit Login Attempts](https://wordpress.org/plugins/limit-login-attempts/) plugin with an extra feature of immediate blocking of specific usernames (like _admin_ or _administrator_).
1. BC Security offers an option to only display generic error message as a result of failed login attempt when wrong username, email or password is provided.

### IP blacklist

BC Security maintains a list of IP addresses with limited access to the website. This list is automatically populated by [Login Security](#login-security) module, but manual addition of IP addresses is also possible.

Out-dated records are automatically removed from the list by WP-Cron job scheduled to run every night. The job can be deactivated in backend, if desired.

### Notifications

BC Security allows to send automatic email notification to configured recipients on following occasions:

1. WordPress update is available.
1. Plugin update is available.
1. Theme update is available.
1. User with administrator privileges has logged in.
1. Known IP address has been locked out (see note below).
1. [Checksums verification](#checksums-verification) fails or there are files with non-matching checksum.
1. [Checklist monitoring](#checklist-monitoring) triggers an alert. Note: there is one notification sent if any of basic checks fails, but separate notification is sent if any of advanced checks fails.
1. BC Security plugin has been deactivated.

Note: _Known IP address_ is an IP address from which a successful login attempt had been previously made. Information about successful login attempts is fetched from [event logs](#events-logging).

You can mute all email notifications by setting constant `BC_SECURITY_MUTE_NOTIFICATIONS` to `true` via `define('BC_SECURITY_MUTE_NOTIFICATIONS', true);`. If you run a website in multiple environments (development, staging, production etc.), you may find it disturbing to receive email notifications from development or any environment other than production. Declaring the constant for particular environment only is very easy, if you use a [multi-environment setup](https://github.com/chesio/wp-multi-env-config).

### Events logging

BC Security logs both short and long lockout events (see [Login Security](#login-security) feature). Also, the following events triggered by WordPress core are logged:

1. Attempts to authenticate with bad cookie
1. Failed and successful login attempts
1. Requests that result in 404 page

Logs are stored in database and can be viewed on backend. Logs are automatically deleted based on their age and overall size: by default no more than 20 thousands of records are kept and any log records older than 365 days are removed, but these limits can be configured.

## Customization

Some of the modules listed above come with settings panel. Further customization can be done with filters provided by plugin:

* `bc-security/filter:is-admin` - filters boolean value that determines whether current user is considered an admin user. This check determines whether admin login notification should be sent for particular user. By default, any user with `manage_options` capability is considered an admin (or `manage_network` on multisite).
* `bc-security/filter:obvious-usernames` - filters array of common usernames that are being checked via [checklist check](#checklist). By default, the array consists of _admin_ and _administrator_ values.
* `bc-security/filter:plugins-to-check-for-removal` - filters array of plugins to check for their presence in WordPress.org Plugins Directory. By default, the array consists of all installed plugins that have `readme.txt` file.
* `bc-security/filter:modified-files-ignored-in-core-integrity-check` - filters array of files that should not be reported as __modified__ in checksum verification of core WordPress files. By default, the array consist of _wp-config-sample.php_ and _wp-includes/version.php_ values.
* `bc-security/filter:unknown-files-ignored-in-core-integrity-check` - filters array of files that should not be reported as __unknown__ in checksum verification of core WordPress files. By default, the array consist of _.htaccess_, _wp-config.php_, _liesmich.html_, _olvasdel.html_ and _procitajme.html_ values.
* `bc-security/filter:plugins-to-check-for-integrity` - filters array of plugins to check in checksum verification. By default, the array consists of all installed plugins that have `readme.txt` file.
* `bc-security/filter:ip-blacklist-default-manual-lock-duration` - filters number of seconds that is used as default value in lock duration field of manual IP blacklisting form. By default, the value is equal to one month in seconds.
* `bc-security/filter:is-ip-address-locked` - filters boolean value that determines whether given IP address is currently locked within given scope. By default, the value is based on plugin bookkeeping data.
* `bc-security/filter:log-404-event` - filters boolean value that determines whether current HTTP request that resulted in [404 response](https://en.wikipedia.org/wiki/HTTP_404) should be logged or not. To completely disable logging of 404 events, you can attach [__return_false](https://developer.wordpress.org/reference/functions/__return_false/) function to the filter.
* `bc-security/filter:events-with-hostname-resolution` - filters array of IDs of events for which hostname of involved IP address should be resolved via reverse DNS lookup. By default the following events are registered: attempts to authenticate with bad cookie, failed and successful login attempts and lockout events. Note that this functionality only relates to event logs report in backend - in case email notification is sent, hostname of reported IP address (if any) is always resolved separately.
* `bc-security/filter:login-username-blacklist` - filters array of blacklisted usernames that are being immediately locked on login. There are no default values, but the filter operates on usernames set via module settings, so it can be used to enforce blacklisting of particular usernames.

## Credits

1. [Login Security](#login-security) feature is inspired by [Limit Login Attempts](https://wordpress.org/plugins/limit-login-attempts/) plugin by Johan Eenfeldt.
1. Part of [psr/log](https://packagist.org/packages/psr/log) package codebase is shipped with the plugin.
1. [Checksums verification](#checksums-verification) feature is heavily inspired by [Checksum Verifier](https://github.com/pluginkollektiv/checksum-verifier) plugin by Sergej Müller.
1. Some features (like "plugins removed from Plugins Directory" check) are inspired by [Wordfence Security](https://wordpress.org/plugins/wordfence/) from [Defiant](https://www.defiant.com/).

## Alternatives (and why I do not use them)

1. [Wordfence Security](https://wordpress.org/plugins/wordfence/) - likely the current number one plugin for WordPress Security. My problem with Wordfence is that _"when you use [Wordfence], statistics about your website visitors are automatically collected"_ (see the full [Terms of Use and Privacy Policy](https://www.wordfence.com/terms-of-use-and-privacy-policy/)). In other words, in order to offer some of its great features, Wordfence is [phoning home](https://en.wikipedia.org/wiki/Phoning_home).
1. [All In One WP Security & Firewall](https://wordpress.org/plugins/all-in-one-wp-security-and-firewall/) - another very popular security plugin for WordPress. I have used AIOWPSF for quite some time; it has a lot of features, but also lot of small bugs (sometimes [not that small](https://sumofpwn.nl/advisory/2016/cross_site_scripting_in_all_in_one_wp_security___firewall_wordpress_plugin.html)). I [used to contribute](https://github.com/Arsenal21/all-in-one-wordpress-security/commits?author=chesio) to the plugin, but the codebase is [rather messy](https://github.com/Arsenal21/all-in-one-wordpress-security/pull/34) and after some time I got tired struggling with it.
