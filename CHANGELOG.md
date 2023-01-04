# BC Security Changelog

## Upcoming version 0.20.0 (????-??-??)

### Added

* WordPress 6.1 is supported [#129](https://github.com/chesio/bc-security/issues/129).
* Improve detection of plugins hosted in Plugins Directory: also include plugins that have `readme.md` instead of `readme.txt` file [#128](https://github.com/chesio/bc-security/issues/128).

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