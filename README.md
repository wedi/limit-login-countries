# Limit Login Countries #
**Contributors:** wedi   
**Tags:** login, security   
**Requires at least:** 3.5.0   
**Tested up to:** 4.0   
**Stable tag:** 0.6.4   
**License:** GPLv2 or any later version   
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html   
**Text Domain:** limit-login-countries
**Domain Path:** /languages

This plugin gives you the ability to limit WordPress admin logins based on the country the visitor's IP address is located in.


## Description ##
This plugin gives you the ability to limit WordPress admin logins based on the country the visitor's IP address is located in. It uses Maxmind's GeoIP database which comes in a free lite version.

You can choose between a white- and blacklist mode. In whitelist mode only visitors with IP addresses from the configured countries are allowed. In blacklist mode visitors with IPs from the configured countries are not allowed to login.


### Translations ###
Big thanks to all the WP-Translations Team Members!

**Included**

* English (English)
* Deutsch (German)
* Türkçe (Turkish)

**Upcoming**

* Nederlands (Dutch) – *93% completed*
* Français (French (France)) – *91% completed*
* Română (Romanian) – *80% completed*

If you don't see your language or it's not completed yet, join the Community and make it happen.

Thanks to [WP-Translations.org](http://wp-translations.org) it's very easy to translate this plugin:

  1. Go to the [project page on Transifex](https://www.transifex.com/projects/p/limit-login-countries/).
  2. Register.
  3. Start translating.


## Development ##
This plugin is developed by [Dirk Weise](http://www.dirk-weise.de) as a pet project on [GitHub](https://github.com/wedi/limit-login-countries). You are welcome to [file an issue](https://github.com/wedi/limit-login-countries/issues) or send a pull request.


## Credits ##
* Kudos go out to the guys over at [*Maxmind*](https://www.maxmind.com/) for providing a lite version of their GeoIP database for free.
* Same to [*Konstantin Kovshenin*](http://kovshenin.com/) who has written a [great tutorial on the WordPress Settings API](http://kovshenin.com/2012/the-wordpress-settings-api/) which helped me a lot while cleaning up the plugin for the public.
* Give big hands to [sudar](http://sudarmuthu.com) for his [toolset for deploying WordPress plugins from GitHub](https://github.com/sudar/wp-plugin-in-github). A must have, when developing WordPress plugins on GitHub.
* Last but not least I send a thank you note to [*Gabriel Oliveira*](http://think0.deviantart.com/) on whose work this plugin's banner image is based.


## Installation ##
This section describes how to install the plugin and get it working.

1. Upload the plugin to your WordPress plugin directory, which is probably `/wp-content/plugins/`.
2. [Download Maxmind's current GeoLite database](http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz) and extract it, e.g. to your WordPress uploads directory, which is probably `/wp-content/uploads/`.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to settings and configure the plugin

### System Requirements ###
This plugin requires at least PHP 5.3. The minimum *tested* WordPress version is 3.5.0. Please report if it works in even older versions.


## Screenshots ##
![ScreenShot](https://raw.github.com/wedi/limit-login-countries/master/assets/screenshot-1.png)
1.  Whoo! The plugin's options page.


## Frequently Asked Questions ##

### HELP! I locked myself out! How do I get in again? ###
No worries! Just add `define('LIMIT_LOGIN_COUNTRIES_OVERRIDE', TRUE);` to your `wp-config.php` and you can login again in whichever country you are.


## Changelog ##

### Version 0.7 ###
* Joined the translators community on [WP-Translations.org](http://wp-translations.org).

### Version 0.6.4 ###
* Hotfix for settings link showing up for all plugins in the Plugins list table.

### Version 0.6.3 ###
* Raise tested WordPress version to 4.0.
* Enforcing PHP 5.3 requirement now.
* Updated bundled geoip-api-php.
* Added settings link in the Plugins list table.
* Refactored code to match WordPress coding style (thank you PhpStorm).

### Version 0.6.2 ###
* Add missing changelog for 0.6.1

### Version 0.6.1 ###
* Raise tested WordPress version.

### Version 0.6 ###
* Public release in WordPress plugin directory.
* Even more cleanup.

### Version 0.5 ###
* readme.txt added.
* and more cleanup before making the plugin public.

### Version 0.4 ###
* Fancy JavaScript additions to options page.
* Search for GeoIP database file added.
* Refactoring of GeoIP access code to prepare for future changes.

### Version 0.3 ###
* Options page added. No more hardcoded values.
* Translation added.

### Version 0.2 ###
* Much better design, but still a lot of hardcoded stuff.
* Updated GeoIP API trunk.

### Version 0.1 ###
* Initial version. ~2011. Works as intended, but it's not very beautiful.


## Upgrade Notice ##

### 0.6.3 ###
This version upgrades, among other things, the bundled geo location API which hopefully fixes problems reading the GeoIP database in some cases.

### 0.6.4 ###
Hotfix for settings link in Plugin list table. The previous release upgraded, among other things, the bundled geo location API.
