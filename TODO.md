TODO: Limit Login Countries
===========================

Version 0.7
-----------
- save last login country for all users. Warn if anyone will be locked out.
- check if current user will be locked out on save.
- tabify settings
- Statistics/Log
  - Save all logins with geoip results
  - Add data about login (country, IP, browscap? etc.)

Version 1.0
-----------
- switch from TextExt to select2
- Define GeoIP database in wp-config.php which disables the
  corresponsing setting on settings page
- Enable GeoIP2 databases
- Use GeoIP C extension if installed
- login country settings per user
- support tab in settings
  - debug info (_SERVER, settings, versions etc.)
  - howto report issues
  - list of issues reported
- akismet like message on dashboard (with setting to disable)

Version x.y
-----------
- add country settings to posts
- make continents selectable in black-/whitelists
- dashboard meta-box
