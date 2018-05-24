moodle-tool_opcache
===================

[![Build Status](https://travis-ci.org/moodleuulm/moodle-tool_opcache.svg?branch=master)](https://travis-ci.org/moodleuulm/moodle-tool_opcache)

Moodle plugin which adds a PHP Opcache management GUI to Moodle site administration and a Nagios check for PHP Opcache.


Requirements
------------

This plugin requires Moodle 3.5+


Motivation for this plugin
--------------------------

For performance reasons, Moodle should always be run with the Opcache PHP extension enabled. Unfortunately, PHP Opcache is kind of a black box and doesn't provide a management interface by default.

Luckily, there are some free Opcache management GUIs out there with Opcache-GUI by Andrew Collington (https://github.com/amnuts/opcache-gui) being the best-looking one. As a Moodle server administrator, can just throw Opcache-GUI's single index.php file somewhere onto your Moodle server and get a Opcache management GUI instantly. However, this approach requires that you protect the Opcache-GUI from unauthorized access manually in your webserver and comes with the downside that Opcache-GUI is located outside Moodle.

For these reasons, we have packaged Opcache-GUI as a very simple Moodle admin tool providing it within Moodle site adminstration for Moodle administrators only.

As a companion feature to the Opcache-GUI which is used manually by Moodle administrators, we added a simple Nagios check for PHP Opcache which can be leveraged to monitor PHP Opcache usage automatically.


Installation
------------

Install the plugin like any other plugin to folder
/admin/tool/opcache

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage
-----

After installing the plugin, it is active and fully working.

To use the plugin, please visit:
Site administration -> Server -> Opcache management.

Opcache-GUI should be self-explanatory for experienced PHP administrators.
For additional documentation about the usage of Opcache-GUI, please refer to the Opcache-GUI documentation on https://github.com/amnuts/opcache-gui/blob/master/README.md.


How this plugin works
---------------------

This plugin works in a really simple way. It adds an admin tool page to Moodle's site administration tree and restricts access to this admin tool page to Moodle administrators (and other users having the moodle/site:config capability). Opcache-GUI is shipped as a library file with this plugin and is just included on the admin tool page.


Security note
-------------

The Opcache-GUI was added as a library to this Moodle plugin and was renamed to /lib/opcache-gui/index.php.inc.

There is a potential for sensitive data leak, not personal data but data about the webserver's PHP configuration, if your webserver is configured to interpret *.inc files as PHP. An anonymous user could then visit the library's index page directly via https://yourmoodle.com/admin/tool/opcache/lib/opcache-gui/index.php.inc and would see the Opcache-GUI circumventing Moodle's access control.

Please make sure that your webserver does not interpret *.inc files as PHP (which should be the default) or take any other measure that this file can not be accessed directly by a browser.


Nagios check
------------

The Nagios check for PHP Opcache is found in the cli subdirectory of the plugin directory. It consists of two parts:

### cli/check_opcache_web.php

This file has to be run within the webserver environment and is thus shipped within this plugin. Its only purpose is to collect some basic figures of the current PHP Opcache usage and to return them one figure per line.

### cli/check_opcache.php

This file is a Moodle CLI script which can only be run on the command line. It is intended to be used by Nagios / NRPE or compatible monitoring solutions. To be able to fetch the basic figures provided by check_opcache_web.php, you have to provide the full URL to check_opcache_web.php as parameter.

Example usage:
```
sudo -u www-data /usr/bin/php admin/tool/cli/check_opcache.php --url=\"https://example.com/admin/tool/opcache/cli/check_opcache_web.php\" --warning=80 --critical=90
```

For more information about the usage run:
```
sudo -u www-data /usr/bin/php admin/tool/cli/check_opcache.php --help
```

The CLI script will return a string similar to:
```
OK - 28.5% cache used | used_pct=28.5%;80;90 hit_pct=72.95%; miss_pct=27.05%;
```


Theme support
-------------

This plugin should work with all Bootstrap based Moodle themes.
It has been developed on and tested only with Moodle Core's Boost theme.
While this plugin should also work with Moodle Core's legacy Clean theme or third party themes, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/tool_opcache

The latest development version can be found on Github:
https://github.com/moodleuulm/moodle-tool_opcache


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/moodleuulm/moodle-tool_opcache/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily implemented for our own local needs and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/moodleuulm/moodle-tool_opcache/issues

Please create pull requests on Github:
https://github.com/moodleuulm/moodle-tool_opcache/pulls

We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major relase - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is a undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


PHP7 Support
------------

Since Moodle 3.4 core, PHP7 is mandatory. We are developing and testing this plugin for PHP7 only.


Copyright
---------

Ulm University
kiz - Media Department
Team Web & Teaching Support
Alexander Bias


Credits
-------

This Moodle plugin is only a simple wrapper for Opcache-GUI by Andrew Collington.
Andrew owns all copyrights for Opcache-GUI and maintains this tool on https://github.com/amnuts/opcache-gui.

The Nagios check in this plugin was inspired by code by Mikanoshi which is published on https://exchange.icinga.com/Mikanoshi/PHP+opcache+monitoring+plugin.
