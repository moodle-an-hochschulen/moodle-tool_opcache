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

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir . '/filelib.php');


// Get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false,
                                                    'url' => null,
                                                    'warning' => 80,
                                                    'critical' => 90),
                                               array('h' => 'help',
                                                    'u' => 'url',
                                                    'w' => 'warning',
                                                    'c' => 'critical'));
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
\$ sudo -u www-data /usr/bin/php admin/tool/cli/check_opcache.php ".
        "--url=\"https://example.com/admin/tool/opcache/cli/check_opcache_web.php\" --warning=75 --critical=85
";
    echo $help;
    exit($returnstatus);
}


// Clean CLI parameters.
$options['url'] = clean_param($options['url'], PARAM_URL);
$options['warning'] = clean_param($options['warning'], PARAM_INT);
$options['critical'] = clean_param($options['critical'], PARAM_INT);


// Fetch Opcache figures from webserver.
$curl = new curl();
$curloptions = array(
    'FRESH_CONNECT'  => true,
    'RETURNTRANSFER' => true,
    'FORBID_REUSE'   => true,
    'HEADER'         => false,
    'CONNECTTIMEOUT' => 5,
);
$curlret = $curl->get($options['url'], array(), $curloptions);

// Pick Opcache figures.
$figures = explode(PHP_EOL, $curlret);
$used = clean_param($figures[0], PARAM_INT);
$free = clean_param($figures[1], PARAM_INT);
$hitspct = clean_param($figures[2], PARAM_FLOAT);
$misspct = clean_param($figures[3], PARAM_FLOAT);


// Calculate used percentage value.
$usedpct = round($used / ($used + $free) * 100, 1);
// Calculate return status.
if ($usedpct >= $options['critical']) {
    $returnstatus = 2;
} else if ($usedpct >= $options['warning']) {
    $returnstatus = 1;
} else {
    $returnstatus = 0;
}

// Concatenate return string including performance data.
$out = $usedpct."% cache used | ".
        "used_pct=".$usedpct."%;".$options['warning'].";".$options['critical']." hit_pct=".$hitspct."%; miss_pct=".$misspct."%;";

// Echo return string.
if ($returnstatus == 0) {
    cli_writeln('OK - '.$out);
} else if ($returnstatus == 1) {
    cli_writeln('WARNING - '.$out);
} else {
    cli_writeln('CRITICAL - '.$out);
}

// Exit with calculated return status.
exit($returnstatus);
