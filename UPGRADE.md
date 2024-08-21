Upgrading this plugin
=====================

This is an internal documentation for plugin developers with some notes what has to be considered when updating this plugin to a new Moodle major version.

General
-------

* Generally, this is a quite simple plugin with just one purpose.
* It does not rely on any fluctuating library functions and should remain quite stable between Moodle major versions.
* Thus, the upgrading effort is low.


Upstream changes
----------------

* This plugin relies on the thiry-party Opcache-GUI tool which is located within the plugin directory. This tool is under active development and has to be updated within the plugin every now and then.
* Basically, you would just need to take the index.php file from the Opcache-GUI repo on https://github.com/amnuts/opcache-gui and replace the /lib/opcache-gui/index.php.inc file with this new version.
* However, as we want to use a CDN-less version of Opcache-GUI, you will have to rebuild the software according to https://github.com/amnuts/opcache-gui#the-javascript. Generally, it works like this:
```
  git clone git@github.com:amnuts/opcache-gui.git
  cd opcache-gui
  php ./build/build.php --local-js
  cp index.php <path-to-tool_opcache>/lib/opcache-gui/index.php.inc
```


Automated tests
---------------

* The plugin has a good coverage with Behat tests which test all of the plugin's user stories.


Manual tests
------------

* There aren't any manual tests needed to upgrade this plugin.


Visual checks
-------------

* It might be advisable to have a look at the admin page of the plugin in the Moodle GUI as Moodle themes or the Opcache-GUI itself can always change small details in this area.
