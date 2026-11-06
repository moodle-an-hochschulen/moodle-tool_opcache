moodle-tool_opcache
===================

Changes
-------

### Unreleased

* 2026-11-06 - Bugfix: Let the web parts of the Nagios check and the CLI tool to reset PHP opcache return a proper response instead of throwing PHP warnings and errors if Opcache is not available on the webserver (extension not loaded, Opcache disabled or API access restricted) or if Opcache has not counted any requests yet. The Nagios check reports an unavailable Opcache as UNKNOWN, the CLI tool to reset PHP opcache reports a missing Opcache extension with a dedicated error message. The evaluation of the response of the web part has been moved to a helper function, including PHPUnit tests.
* 2026-11-06 - Bugfix: Let the Nagios check report a proper status instead of crashing with a division by zero if the webserver returned an unexpected response. A wrong secret key is now reported as CRITICAL, an unreachable webserver or a response without valid Opcache figures (for example an error page or a webserver with disabled Opcache) is reported as UNKNOWN.
* 2026-11-06 - Documentation: Remove the privacy note in README.md which claimed that the OPcache GUI loads JavaScript libraries from the unpkg.com CDN. The bundled OPcache GUI is a CDN-less build and does not fetch any resources from third-party servers.
* 2026-11-04 - Improvement: Disable the Nagios check and the CLI tool to reset PHP opcache by default. They have to be enabled with a setting in config.php now and their web parts are always protected by a secret key which is generated automatically if it is not set in config.php. For backwards compatibility, setting a secret key in config.php still enables the particular tool as well.
               Please note: Up to now, the Nagios check was enabled by default and the secret key was optional. If you have used the Nagios check without a secret key until now, you have to enable it now by adding $CFG->tool_opcache_check_enabled = true; to config.php after upgrading the plugin. Otherwise, the Nagios check will report an UNKNOWN status. If you have already set a secret key in config.php for the Nagios check or the CLI tool to reset PHP opcache, you do not have to change anything as the secret key still enables the particular tool. Please see README.md for details.
* 2026-11-04 - Bugfix: Compare the secret keys of the web parts of the Nagios check and the CLI tool to reset PHP opcache with hash_equals(). Until now, a numeric secret key could be matched by a different numeric string (like '1e3' for '1000').
* 2026-11-04 - Bugfix: Fix the file name of the web part of the Nagios check in README.md.
* 2026-11-03 - Improvement: Protect the write operations of the Opcache GUI (resetting the cache and invalidating files) against cross-site request forgery with a sesskey check, including PHPUnit and Behat tests.

### v4.5-r5

* 2026-07-05 - Upgrade OPcache GUI to version 3.6.1

### v4.5-r4

* 2025-10-31 - Upgrade OPcache GUI to version 3.6.0

### v4.5-r3

* 2025-10-31 - Withdrawn (just because of a flaw in CHANGES.md)

### v4.5-r2

* 2025-10-15 - Make codechecker happy again
* 2025-10-15 - Tests: Switch Github actions workflows to reusable workflows by Moodle an Hochschulen e.V.

### v4.5-r1

* 2024-10-14 - Upgrade: Adopt changes from MDL-81960 and use new \core\url class
* 2024-10-14 - Upgrade: Adopt changes from MDL-81920 and use new \core\lang_string class.
* 2024-10-07 - Prepare compatibility for Moodle 4.5.

### v4.4-r1

* 2024-08-24 - Development: Rename master branch to main, please update your clones.
* 2024-08-20 - Prepare compatibility for Moodle 4.4.

### v4.3-r3

* 2024-08-21 - Upgrade OPcache GUI to version 3.5.5

### v4.3-r2

* 2024-08-11 - Add section for scheduled tasks to README
* 2024-08-11 - Updated Moodle Plugin CI to latest upstream recommendations

### v4.3-r1

* 2023-10-20 - Prepare compatibility for Moodle 4.3.

### v4.2-r1

* 2023-10-16 - Upgrade OPcache GUI to a CDN-less edition of version 3.5.2
* 2023-09-01 - Prepare compatibility for Moodle 4.2.

### v4.1-r2

* 2023-10-14 - Add automated release to moodle.org/plugins
* 2023-10-14 - Make codechecker happy again
* 2023-10-10 - Updated Moodle Plugin CI to latest upstream recommendations
* 2023-04-30 - Tests: Updated Moodle Plugin CI to use PHP 8.1 and Postgres 13 from Moodle 4.1 on.

### v4.1-r1

* 2023-04-25 - Upgrade OPcache GUI to a CDN-less edition of version 3.4.0
* 2023-01-21 - Prepare compatibility for Moodle 4.1.
* 2023-03-11 - Make codechecker happy again
* 2022-11-28 - Updated Moodle Plugin CI to latest upstream recommendations

### v4.0-r2

* 2022-08-08 - Improvement: Get rid of second vertical scrollbar around OPcache GUI

### v4.0-r1

* 2022-07-12 - Prepare compatibility for Moodle 4.0.

### v3.11-r4

* 2022-08-07 - Upgrade OPcache GUI to version 3.4.0

### v3.11-r3

* 2022-07-10 - Add Visual checks section to UPGRADE.md
* 2022-07-10 - Add Capabilities section to README.md

### v3.11-r2

* 2022-06-26 - Make codechecker happy again
* 2022-06-26 - Updated Moodle Plugin CI to latest upstream recommendations
* 2022-06-26 - Add UPGRADE.md as internal upgrade documentation
* 2022-06-26 - Update maintainers and copyrights in README.md.

### v3.11-r1

* 2021-07-15 - Upgrade OPcache GUI to version 3.3.0 (codebase as of 15 july 2021).
* 2021-07-15 - Prepare compatibility for Moodle 3.11.
* 2021-02-05 - Move Moodle Plugin CI from Travis CI to Github actions

### v3.10-r1

* 2020-12-09 - Prepare compatibility for Moodle 3.10.
* 2020-12-08 - Change in Moodle release support:
               For the time being, this plugin is maintained for the most recent LTS release of Moodle as well as the most recent major release of Moodle.
               Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.
* 2020-12-08 - Improvement: Declare which major stable version of Moodle this plugin supports (see MDL-59562 for details).
* 2020-12-08 - Update OPCache GUI to version 3.2.0 (codebase as of 08 december 2020) from upstream.

### v3.9-r3

* 2020-12-07 - Add a note about privacy and CDNs to README.md
* 2020-12-07 - Add a note about browser support to README.md
* 2020-12-07 - Update OPCache GUI to version 3.1.0 (codebase as of 28 november 2020) from upstream.
* 2020-11-26 - Update OPCache GUI to version 3.0.1 (codebase as of 30 september 2020) from upstream.

### v3.9-r2

* 2020-09-28 - Remove hardcoded admin directory in paths.

### v3.9-r1

* 2020-09-25 - Update OPCache GUI to version 3.0.0 from upstream.
* 2020-09-25 - Prepare compatibility for Moodle 3.9.

### v3.8-r2

* 2020-09-25 - Add MIT license text to OPCache GUI library directory
* 2020-09-25 - Update OPCache GUI to version 2.5.4 from upstream - Thanks to Andrew Collington

### v3.8-r1

* 2020-02-14 - Prepare compatibility for Moodle 3.8.

### v3.7-r1

* 2019-08-12 - Update OPCache GUI to version 2.4.1 from upstream.
* 2019-08-05 - Added behat tests.
* 2019-07-19 - Prepare compatibility for Moodle 3.7.


### v3.6-r2

* 2019-07-05 - Feature: Add optional security mechanism to Nagios check. Please see README.md for details.
* 2019-07-05 - Feature: Add CLI tool to reset PHP Opcache. Please see README.md for details.

### v3.6-r1

* 2019-02-01 - Check compatibility for Moodle 3.6, no functionality change.
* 2018-12-05 - Changed travis.yml due to upstream changes.

### v3.5-r1

* 2018-05-24 - Check compatibility for Moodle 3.5, no functionality change.

### v3.4-r3

* 2018-05-16 - Implement Privacy API.

### v3.4-r2

* 2018-04-21 - Update OPCache GUI to latest version from upstream

### v3.4-r1

* 2017-12-12 - Check compatibility for Moodle 3.4, no functionality change.
* 2017-12-05 - Added Workaround to travis.yml for fixing Behat tests with TravisCI.

### v3.3-r1

* 2017-11-23 - Check compatibility for Moodle 3.3, no functionality change.
* 2017-11-08 - Updated travis.yml to use newer node version for fixing TravisCI error.

### Release v3.2-r3

* 2017-09-11 - Disable MySQL in Travis CI, credits to David Mudrák
* 2017-09-11 - Add security note to README

### Release v3.2-r2

* 2017-08-14 - Add nagios check as CLI script

### Release v3.2-r1

* 2017-08-08 - Initial version
