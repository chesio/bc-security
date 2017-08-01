# BC Security

Helps keeping WordPress websites secure.

## Requirements
* [PHP](https://secure.php.net/) 5.6 or newer
* [WordPress](https://wordpress.org/) 4.7 or newer

## Limitations

* BC Security has not been tested on WordPress multisite installation.
* BC Security is primarily being developed for Apache webserver environment.

## Features

### Checklist

BC Security features a checklist of common security practices. In the moment, the list consists of following checks:
1. Is PHP editation of plugin and theme files disabled?
1. Are directory listings disabled?
1. Is execution of PHP files from uploads directory forbidden?
1. Is display of PHP errors off by default? This check is only run in production environment, ie. when `WP_ENV === 'production'`.
1. Is error log file not publicly available? This check is only run if both `WP_DEBUG` and `WP_DEBUG_LOG` constants are set to true.
1. Are there no common usernames like admin or administrator on the system?
1. Are user passwords hashed with some non-default hashing algorithm?

### WordPress Hardening

BC Security allows you to:
1. Disable pingbacks
1. Disable XML RPC methods that require authentication
1. Disable access to REST API to anonymous users

### Checksums verification

BC Security once a day performs integrity check of WordPress core files by comparing their checksums with MD5 checksums downloaded from WordPress.org. Any file with md5 checksum, that does not match its official checksum, is [logged](#events-logging) and (optionally) reported via [email notification](#notifications).

### Login Security

1. BC Security allows you to limit number of login attempts from single IP address. Implementation of this feature is heavily inspired by popular [Limit Login Attempts](https://wordpress.org/plugins/limit-login-attempts/) plugin with an extra feature of immediate blocking of specific usernames (like _admin_ or _administrator_).
1. BC Security offers an option to only display generic error message as a result of failed login attempt when wrong username, email or password is provided.

### IP Blacklist

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
1. BC Security plugin has been deactivated.

Note: _Known IP address_ is an IP address from which a successful login attempt had been previously made. Information about successful login attempts is fetched from [event logs](#events-logging).

### Events logging

BC Security logs both short and long lockout events (see [Login Security](#login-security) feature) and [checksums verification](#checksums-verification) alerts.

Also, the following events triggered by WordPress core are logged:

1. Attempts to authenticate with bad cookie
1. Failed and successful login attempts
1. Requests that result in 404 page

Logs are stored in database and can be viewed on backend. Logs are automatically deleted based on their age and overall size: by default no more than 20 thousands of records are kept and any log records older than 365 days are removed, but these limits can be configured.

## Credits

1. [Login Security](#login-security) feature has been inspired by [Limit Login Attempts](https://wordpress.org/plugins/limit-login-attempts/) plugin by Johan Eenfeldt.
1. Part of [psr/log](https://packagist.org/packages/psr/log) package codebase is shipped with the plugin.
1. [Checksums verification](#checksums-verification) feature is almost verbatim taken from [Checksum Verifier](https://github.com/pluginkollektiv/checksum-verifier) plugin by Sergej Müller.

## Alternatives (and why I do not use them)

1. [Wordfence Security](https://wordpress.org/plugins/wordfence/) - likely the current number one plugin for WordPress Security. My problem with Wordfence is that _"when you use [Wordfence], statistics about your website visitors are automatically collected"_ (see the full [Terms of Use and Privacy Policy](https://www.wordfence.com/terms-of-use-and-privacy-policy/)). In other words, in order to offer some of its great features, Wordfence is [phoning home](https://en.wikipedia.org/wiki/Phoning_home).
1. [All In One WP Security & Firewall](https://wordpress.org/plugins/all-in-one-wp-security-and-firewall/) - another very popular security plugin for WordPress. I have used AIOWPSF for quite some time; it has a lot of features, but also lot of small bugs (sometimes [not that small](https://sumofpwn.nl/advisory/2016/cross_site_scripting_in_all_in_one_wp_security___firewall_wordpress_plugin.html)). I [used to contribute](https://github.com/Arsenal21/all-in-one-wordpress-security/commits?author=chesio) to the plugin, but the codebase is [rather messy](https://github.com/Arsenal21/all-in-one-wordpress-security/pull/34) and after some time I got tired struggling with it.
