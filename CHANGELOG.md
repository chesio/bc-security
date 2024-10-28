# BC Security Changelog

## Upcoming version 0.26.0 (????-??-??)

...

## Version 0.25.0 (2024-10-28)

This release has been tested with PHP 8.4.

### Added

* Plugin has been tested with PHP 8.4 [#163](https://github.com/chesio/bc-security/issues/163).
* Plugin has been tested with WordPress 6.7 [#162](https://github.com/chesio/bc-security/issues/162).

### Changed

* End-of-life dates for supported PHP versions have been updated [#164](https://github.com/chesio/bc-security/issues/164).

## Version 0.24.0 (2024-07-29)

WordPress 6.4 or newer is now required!

### Added

* Disable autoloading of plugin options when plugin is deactivated [#160](https://github.com/chesio/bc-security/issues/160).
* New built-in rule for bad request banner module that triggers when non-existing `.asp` or `.aspx` file is accessed [#161](https://github.com/chesio/bc-security/issues/161).
* Plugin has been tested with WordPress 6.6 [#157](https://github.com/chesio/bc-security/issues/157).

### Changed

* WordPress 6.4 is required [#159](https://github.com/chesio/bc-security/issues/159).

## Version 0.23.0 (2024-04-04)

**Important**: either deactivate and reactivate plugin after update or install new cron job manually via WP-CLI: `wp cron event schedule bc-security/failed-logins-clean-up now daily`.

### Added

* New built-in rule for bad request banner module that triggers when non-existing `.tgz` or `.zip` file is accessed [#155](https://github.com/chesio/bc-security/issues/155).
* Plugin has been tested with WordPress 6.5 [#152](https://github.com/chesio/bc-security/issues/152).

### Changed

* List of supported PHP versions for PHP version check has been updated to include PHP 8.3 [#151](https://github.com/chesio/bc-security/issues/151).

### Fixed

* Fix SQL syntax error when bulk unlocking entries in internal blocklist [#154](https://github.com/chesio/bc-security/pull/154) - thanks to @szepeviktor.
* Table storing failed logins data is now pruned automatically [#156](https://github.com/chesio/bc-security/issues/156).

## Version 0.22.1 (2024-02-07)

### Fixed

* Fix `Uncaught TypeError` when saving external blocklist settings [#153](https://github.com/chesio/bc-security/issues/153).

## Version 0.22.0 (2024-02-01)

This release has been tested with PHP 8.3 and WordPress 6.4. PHP 8.1 or newer and WordPress 6.2 or newer are now required!

### Added

* New built-in rule for bad request banner module that triggers when non-existing `readme.txt` file is accessed [#149](https://github.com/chesio/bc-security/issues/149).
* Plugin has been tested with PHP 8.3 [#145](https://github.com/chesio/bc-security/issues/145).
* Plugin has been tested with WordPress 6.4 [#144](https://github.com/chesio/bc-security/issues/144).

### Changed

* PHP 8.1 is required [#143](https://github.com/chesio/bc-security/issues/143). As part of an effort to use modern PHP features whenever useful, _access scope_ values are now passed as [backed enum](https://stitcher.io/blog/php-enums) instances instead of plain `int`. This is a **breaking change** for actions and filters that have _access scope_ value as their argument:
  1. `bc-security/action:external-blocklist-hit`
  2. `bc-security/action:internal-blocklist-hit`
  3. `bc-security/filter:is-ip-address-blocked`
* WordPress 6.2 is required [#147](https://github.com/chesio/bc-security/issues/147).

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
* On WordPress 5.8 and newer the plugin cannot be accidentally overridden from WordPress.org Plugins Directory [#111](https://github.com/chesio/bc-security/issues/111).

## Older releases

Notes on changes in all releases can be found [here](https://github.com/chesio/bc-security/releases).
