moodle-tool_opcache
===================

Changes
-------

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
