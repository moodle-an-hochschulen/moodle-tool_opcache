<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin tool "Opcache management" - CLI Script to reset opcache
 *
 * @package    tool_opcache
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/filelib.php');

global $CFG;


// Get cli options.
[$options, $unrecognized] = cli_get_params(
    ['help' => false,
                                                    'url' => null,
                                                    'reset' => false, ],
    ['h' => 'help',
                                                    'u' => 'url',
    'r' => 'reset',
    ]
);
if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}


// CLI help.
if ($options['help'] || !($options['url']) || !($options['reset'])) {
    $help = "CLI tool to reset PHP opcache

Options:
-u, --url       Full URL to reset_opcache_web.php
-r, --reset     Reset PHP opcache on this server
-h, --help      Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/tool/cli/reset_opcache.php ";
    cli_writeln($help);
    exit(0);
}


// Clean CLI parameters.
$options['url'] = clean_param($options['url'], PARAM_URL);


// Reset opcache on webserver.
$curl = new curl(['ignoresecurity' => true]); // The ignoresecurity option means that $CFG->curlsecurityblockedhosts is
                                                   // ignored by purpose. Otherwise, $CFG->curlsecurityblockedhosts might prevent
                                                   // that the web part of this CLI tool is fetched.
$curloptions = [
        'FRESH_CONNECT' => true,
        'RETURNTRANSFER' => true,
        'FORBID_REUSE' => true,
        'HEADER' => false,
        'CONNECTTIMEOUT' => 5,
];
$params = [];
if (isset($CFG->tool_opcache_reset_secretkey)) {
    $params['secret'] = $CFG->tool_opcache_reset_secretkey;
}
$curlret = $curl->get($options['url'], $params, $curloptions);

// Echo return string and exit with return status.
if ($curlret == '0') {
    cli_writeln('PHP opcache has been reset successfully on this server.');
    exit(0);
} else if ($curlret == '1') {
    cli_error('PHP opcache could not be reset as requested.
$CFG->tool_opcache_reset_allowed is not set in config.php.
See README.md for details.');
} else if ($curlret == '2') {
    cli_error('PHP opcache could not be reset as requested.
The submitted secret key does not match $CFG->tool_opcache_reset_allowed from config.php.
See README.md for details.');
} else {
    cli_error('PHP opcache could not be reset as requested.
The reason for this may simply be that PHP opcache is disabled on this server.');
}
