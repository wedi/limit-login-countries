=== Limit Login Countries ===
Contributors: wedi
Tags: login, security
Requires at least: 3.5.0
Tested up to: 3.6.1
Stable tag: 0.5
License: GPLv2 or any later version
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin gives you the ability to limit WordPress admin logins based on the country the visitor's IP address is located in.

== Description ==

This plugin gives you the ability to limit WordPress admin logins based on the country the visitor's IP address is located in. It uses Maxmind's GeoIP database which comes in a free lite version.

You can choose between a white- and blacklist mode. In whitelist mode only visitors with IP addresses from the configured countries are allowed. In blacklist mode visitors with IPs from the configured countries are not allowed to login.

= Contribute =

You are welcome to contribute to this plugin on [GitHub](https://github.com/wedi/limit-login-countries).

= Credits =

Kudos go out to the guys over at [*Maxmind*](https://www.maxmind.com/) for providing a lite version of their GeoIP database for free.
Same to [*Konstantin Kovshenin*](http://kovshenin.com/) who has written a great [tutorial on the WordPress Settings API](http://kovshenin.com/2012/the-wordpress-settings-api/) which helped me a lot while cleaning up the plugin for the public.
Last but not least thank you to [*Gabriel Oliveira*](http://think0.deviantart.com/) on whose work the plugin's banner image is based.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin to your `/wp-content/plugins/` directory
2. [Download Maxmind's current GeoLite database](http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz) ("legacy" version, an update to GeoIP2 is already planned) and extract it to your WordPress directory, e.g. your uploads folder.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to settings and configure the plugin

== Screenshots ==

1.  Whoo! The plugin's Options page.

== Frequently Asked Questions ==

= HELP! I locked myself out! How do I get in again? =
No worries! Just add `define('LIMIT_LOGIN_COUNTRIES_OVERRIDE', TRUE);` to your `wp-config.php` and can login again in whichever country you are.

== Changelog ==

= 0.5 =
* readme.txt added
* and more cleanup before making the plugin public

= 0.4 =
* Fancy JavaScript additions to options page.
* Search for GeoIP database file added.
* Refactoring of GeoIP access code to prepare for future changes.

= 0.3 =
* Options page added. No more hardcoded values.
* Translation added.

= 0.2 =
* Much better design, but still a lot of hardcoded stuff.
* Updated GeoIP API trunk.

= 0.1 =
* Initial version. ~2011. Works as it should, but not very beautiful.