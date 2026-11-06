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
 * Admin tool "Opcache management" - Nagios monitoring check, part "cli"
 *
 * @package    tool_opcache
 * @copyright  2017 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @copyright  inspired by code by Mikanoshi, https://exchange.icinga.com/Mikanoshi/PHP+opcache+monitoring+plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/../locallib.php');


// Get cli options.
[$options, $unrecognized] = cli_get_params(
    ['help' => false,
                                                    'url' => null,
                                                    'warning' => 80,
                                                    'critical' => 90, ],
    ['h' => 'help',
                                                    'u' => 'url',
                                                    'w' => 'warning',
    'c' => 'critical',
    ]
);
if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}


// Initialize return code as UNKNOWN.
$returnstatus = 3;


// CLI help.
if ($options['help'] || !($options['url'])) {
    $help = "Nagios check for PHP opcache

Options:
-u, --url       Full URL to check_opcache_web.php
-w, --warning   Threshold for warning (Default: 80)
-c, --critical  Threshold for critical (Default: 90)
-h, --help      Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/tool/cli/check_opcache.php " .
        "--url=\"https://example.com/admin/tool/opcache/cli/check_opcache_web.php\" --warning=75 --critical=85
";
    cli_writeln($help);
    exit($returnstatus);
}


// Die if the Nagios check is not enabled.
if (!tool_opcache_is_cli_tool_enabled('check')) {
    cli_writeln('UNKNOWN - The Nagios check is not enabled in config.php. See README.md for details.');
    exit($returnstatus);
}


// Clean CLI parameters.
$options['url'] = clean_param($options['url'], PARAM_URL);
$options['warning'] = clean_param($options['warning'], PARAM_INT);
$options['critical'] = clean_param($options['critical'], PARAM_INT);


// Fetch Opcache figures from webserver.
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
$params = ['secret' => tool_opcache_get_secretkey('check', true)];
$curlret = $curl->get($options['url'], $params, $curloptions);

// Evaluate the response of the webserver.
[$returnstatus, $out] = tool_opcache_evaluate_check_response($curlret, $curl->get_errno(), (string) $curl->error,
        $options['warning'], $options['critical']);

// Echo return string.
cli_writeln($out);

// Exit with calculated return status.
exit($returnstatus);
