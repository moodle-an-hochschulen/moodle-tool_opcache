moodle-tool_opcache
===================

[![Moodle Plugin CI](https://github.com/moodle-an-hochschulen/moodle-tool_opcache/workflows/Moodle%20Plugin%20CI/badge.svg?branch=MOODLE_400_STABLE)](https://github.com/moodle-an-hochschulen/moodle-tool_opcache/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3AMOODLE_400_STABLE)

Moodle plugin which adds a PHP Opcache management GUI to Moodle site administration, a CLI tool to reset PHP Opcache and a Nagios check for PHP Opcache.


Requirements
------------

This plugin requires Moodle 4.0+


Motivation for this plugin
--------------------------

For performance reasons, Moodle should always be run with the Opcache PHP extension enabled. Unfortunately, PHP Opcache is kind of a black box and doesn't provide a management interface by default.

Luckily, there are some free Opcache management GUIs out there with Opcache-GUI by Andrew Collington (https://github.com/amnuts/opcache-gui) being the best-looking one. As a Moodle server administrator, you can just throw Opcache-GUI's single index.php file somewhere onto your Moodle server and get a Opcache management GUI instantly. However, this approach requires that you protect the Opcache-GUI from unauthorized access manually in your webserver and comes with the downside that Opcache-GUI is located outside Moodle.

For these reasons, we have packaged Opcache-GUI as a very simple Moodle admin tool providing it within Moodle site administration for Moodle administrators only.

As a companion feature to the Opcache-GUI which is used by Moodle administrators in the browser, we added a simple CLI tool to reset PHP Opcache from the command line.

Additionally as a companion feature to the Opcache-GUI which is used manually by Moodle administrators, we added a simple Nagios check for PHP Opcache which can be leveraged to monitor PHP Opcache usage automatically.


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


Capabilities
------------

This plugin does not add any additional capabilities.


How this plugin works
---------------------

This plugin works in a really simple way. It adds an admin tool page to Moodle's site administration tree and restricts access to this admin tool page to Moodle administrators (and other users having the moodle/site:config capability). Opcache-GUI is shipped as a library file with this plugin and is just included on the admin tool page.


Browser support
---------------

The Opcache-GUI which is used within this plugin is using ES6 Javascript code. In contrast to Moodle core, this JavaScript code is not transpiled to ES5 in any way. As a result, this plugin does not necessarily run in all legacy browsers which Moodle core still supports. However, this requirement just applies to the admin tool page on Site administration -> Server -> Opcache management, nothing more and nothing less.


Security note
-------------

The Opcache-GUI was added as a library to this Moodle plugin and was renamed to /lib/opcache-gui/index.php.inc.

There is a potential for sensitive data leak, not personal data but data about the webserver's PHP configuration, if your webserver is configured to interpret *.inc files as PHP. An anonymous user could then visit the library's index page directly via https://yourmoodle.com/admin/tool/opcache/lib/opcache-gui/index.php.inc and would see the Opcache-GUI circumventing Moodle's access control.

Please make sure that your webserver does not interpret *.inc files as PHP (which should be the default) or take any other measure that this file can not be accessed directly by a browser.


Privacy note
------------

The Opcache-GUI which is used within this plugin is using several JavaScript libraries from the unpkg.com CDN. This is important to note as Moodle core strives to be as CDN-free as possible normally. As a result, you will have to accept that your browser will fetch the JavaScript libraries from the CDN and during this request at least your IP address will be exposed to the CDN. However, this just applies to the admin tool page on Site administration -> Server -> Opcache management, nothing more and nothing less.


CLI tool
--------

The CLI tool for PHP Opcache is found in the cli subdirectory of the plugin directory. It consists of two parts:

### cli/reset_opcache_web.php

This file has to be run within the PHP webserver environment and is thus shipped within this plugin. Its only purpose is to reset PHP Opcache within the PHP webserver environment instead of the PHP CLI environment.

### cli/reset_opcache.php

This file is a Moodle CLI script which can only be run on the command line. To be able to reset PHP Opcache with reset_opcache_web.php, you have to provide the full URL to reset_opcache_web.php as parameter.


Security mechanism:
As cli/reset_opcache_web.php will reset PHP opcache without any Moodle user authentication and as this could be abused for denial-of-service attacks, a secret key has to be set in config.php. This secret key is used internally by cli/reset_opcache.php to call cli/reset_opcache_web.php. Without submitting the correct secret key to cli/reset_opcache_web.php, cli/reset_opcache_web.php will not reset PHP opcache.

To enable this CLI tool, please add this setting to your config.php file and set this setting to an alphanumeric string of your choice:
```
$CFG->tool_opcache_reset_secretkey = 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa';
```


Example usage:
```
sudo -u www-data /usr/bin/php admin/tool/cli/reset_opcache.php --url=\"https://example.com/admin/tool/opcache/cli/reset_opcache_web.php\" --reset
```

For more information about the usage run:
```
sudo -u www-data /usr/bin/php admin/tool/cli/reset_opcache.php --help
```

If run successfully, the CLI script will return a clear message:
```
PHP opcache has been reset successfully on this server.
```

If run unsuccessfully, the CLI script will also return an error message.


Nagios check
------------

The Nagios check for PHP Opcache is found in the cli subdirectory of the plugin directory. It consists of two parts:

### cli/check_opcache_web.php

This file has to be run within the PHP webserver environment and is thus shipped within this plugin. Its only purpose is to collect some basic figures of the current PHP Opcache usage and to return them one figure per line.

### cli/check_opcache.php

This file is a Moodle CLI script which can only be run on the command line. It is intended to be used by Nagios / NRPE or compatible monitoring solutions. To be able to fetch the basic figures provided by check_opcache_web.php, you have to provide the full URL to check_opcache_web.php as parameter.


Security mechanism:
As cli/clear_opcache_web.php will return some data about PHP opcache usage without any Moodle user authentication and as this might not be desired in all installations, a secret key can be set in config.php. If set, this secret key is used internally by cli/check_opcache.php to call cli/check_opcache_web.php. Without submitting the correct secret key to cli/check_opcache_web.php, cli/check_opcache_web.php will then not return any values.

To enable the security mechanism, please add this setting to your config.php file and set this setting to an alphanumeric string of your choice:
```
$CFG->tool_opcache_check_secretkey = 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa';
```


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

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/tool_opcache

The latest development version can be found on Github:
https://github.com/moodle-an-hochschulen/moodle-tool_opcache


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/moodle-an-hochschulen/moodle-tool_opcache/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily implemented for our own local needs and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/moodle-an-hochschulen/moodle-tool_opcache/issues

Please create pull requests on Github:
https://github.com/moodle-an-hochschulen/moodle-tool_opcache/pulls

We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle as well as the most recent LTS release of Moodle. Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major release - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


Maintainers
-----------

The plugin is maintained by\
Moodle an Hochschulen e.V.


Copyright
---------

The copyright of this plugin is held by\
Moodle an Hochschulen e.V.

Individual copyrights of individual developers are tracked in PHPDoc comments and Git commits.


Initial copyright
-----------------

This plugin was initially built, maintained and published by\
Ulm University\
Communication and Information Centre (kiz)\
Alexander Bias

It was contributed to the Moodle an Hochschulen e.V. plugin catalogue in 2022.


Credits
-------

This Moodle plugin is only a simple wrapper for Opcache-GUI by Andrew Collington.
Andrew owns all copyrights for Opcache-GUI and maintains this tool on https://github.com/amnuts/opcache-gui.

The Nagios check in this plugin was inspired by code by Mikanoshi which is published on https://exchange.icinga.com/Mikanoshi/PHP+opcache+monitoring+plugin.
