# BC Security Changelog

## Upcoming version 0.22.0 (????-??-??)

...

## Version 0.21.0 (2023-08-17)

PHP 8.0 or newer and WordPress 6.0 or newer are now required!

### Added

* WordPress 6.3 is supported [#141](https://github.com/chesio/bc-security/issues/141).
* Block rules with "website" access scope in internal blocklist can now be synced with `.htaccess` file [#142](https://github.com/chesio/bc-security/issues/142).
* Remote IP addresses that are scanning your website for weaknesses can be automatically for configured amount of time [#132](https://github.com/chesio/bc-security/issues/132).

### Changed

* PHP 8.0 is required and the policy to run on supported PHP versions only has been restored [#117](https://github.com/chesio/bc-security/issues/117).

## Version 0.20.1 (2023-04-11)

### Fixed

* Validate IP addresses to avoid potential security issues [#138](https://github.com/chesio/bc-security/issues/138).
* List of supported PHP versions for PHP version check has been updated to include PHP 8.2 and exclude PHP 7.4 [#137](https://github.com/chesio/bc-security/issues/137).

## Version 0.20.0 (2023-03-31)

This release brings a new feature: __external blocklist__. This feature has its own module named _External Blocklist_. To keep the naming consistent, _IP Blacklist_ module has been renamed to _Internal Blocklist_.

These adjustments led to some breaking changes, therefore during update it is recommended to:
1. Deactivate the plugin first.
2. Rename the database table `bc_security_ip_blacklist` to `bc_security_internal_blocklist`.
3. Update and reactivate the plugin.

### Added

* PHP 8.2 is supported [#130](https://github.com/chesio/bc-security/issues/130).
* WordPress 6.1 and 6.2 is supported ([#129](https://github.com/chesio/bc-security/issues/129) and [#136](https://github.com/chesio/bc-security/issues/136)).
* Improve detection of plugins hosted in Plugins Directory: also include plugins that have `readme.md` instead of `readme.txt` file [#128](https://github.com/chesio/bc-security/issues/128).
* An option to block request coming from Amazon AWS network: either all requests or login requests only coming from AWS network can be blocked [#120](https://github.com/chesio/bc-security/issues/120).
* Requests blocked by external or internal blocklist are now logged.

### Changed

* WordPress 5.9 or newer is now required [#131](https://github.com/chesio/bc-security/issues/131).

## Version 0.19.0 (2022-06-02)

### Added

* PHP 8.1 is supported [#116](https://github.com/chesio/bc-security/issues/116).
* WordPress versions 5.9 and 6.0 are supported [#121](https://github.com/chesio/bc-security/issues/121) and [#127](https://github.com/chesio/bc-security/issues/127).
* An option to restrict login options has been implemented: login via email or login via username can be disabled [#123](https://github.com/chesio/bc-security/issues/123).
* [Changelog.md](CHANGELOG.md) has been added [#125](https://github.com/chesio/bc-security/issues/125).

### Removed

* "Check auth cookies" setting has been removed - the check is now always applied [#124](https://github.com/chesio/bc-security/issues/124).

## Version 0.18.1 (2021-12-29)

### Fixed

* EOL dates for PHP versions in PHP version check has been updated: EOL date for PHP 7.3 has been removed, EOL date for PHP 8.1 has been added [#115](https://github.com/chesio/bc-security/issues/115).

## Version 0.18.0 (2021-11-30)

### Added

* PHP 8.0 is supported [#104](https://github.com/chesio/bc-security/issues/104).
* Alert about "No removed plugins installed" has more information [#107](https://github.com/chesio/bc-security/issues/107).
* Detection of plugins installed from WordPress Directory has been improved [#112](https://github.com/chesio/bc-security/issues/112).
* On WordPress 5.8 and newer the plugin cannot be accidentally overriden from WordPress.org Plugins Directory [#111](https://github.com/chesio/bc-security/issues/111).

## Older releases

Notes on changes in all releases can be found [here](https://github.com/chesio/bc-security/releases).
