# BC Security

Helps keeping WordPress websites secure.

## Requirements
* [PHP](https://secure.php.net/) 5.6 or newer
* [WordPress](https://wordpress.org/) 4.7 or newer

## Features

### Checklist

BC Security features a checklist of common security practices. In the moment, the list consists of only two checks:
1. Is PHP editation of plugin and theme files disabled?
1. Are there no common usernames like admin or administrator on the system?

### WordPress Hardening

BC Security allows you to:
1. Disable pingbacks
1. Disable XML RPC methods that require authentication
1. Disable access to REST API to anonymous users

### Login Security

1. BC Security allows you to limit number of login attempts from single IP address. Implementation of this feature is heavily inspired by popular [Limit Login Attempts](https://wordpress.org/plugins/limit-login-attempts/) plugin with an extra feature of immediate blocking of specific usernames (like _admin_ or _administrator_).
1. BC Security offers an option to only display generic error message as a result of failed login attempt when wrong username, email or password is provided.

### IP Blacklist

BC Security maintains a list of IP addresses with limited access to the website. Currently, this list is only populated by [Login Security](#login-security) module.
