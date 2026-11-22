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
                                                    'critical' => 90,
                                                    'insecure' => false,
                                                    'domain' => null,
                                                    'verbose' => false, ],
    ['h' => 'help',
                                                    'u' => 'url',
                                                    'w' => 'warning',
    'c' => 'critical',
    'k' => 'insecure',
    'd' => 'domain',
    'v' => 'verbose',
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
-k, --insecure  Do not verify the TLS certificate of the webserver
                (for example if it uses a self-signed certificate
                or if its certificate does not match the hostname in the URL)
-d, --domain    Hostname which is sent as Host header to the webserver
                (for example if the URL contains an IP address
                instead of the hostname of the Moodle site),
                optionally with a port (example.com:8443)
-v, --verbose   Print verbose information about the request to STDERR
-h, --help      Print out this help

Example (run from the Moodle root directory):
\$ sudo -u www-data /usr/bin/php admin/tool/opcache/cli/check_opcache.php " .
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

// Die if the thresholds do not make sense.
if ($options['warning'] > $options['critical']) {
    cli_writeln('UNKNOWN - The threshold for warning must not be greater than the threshold for critical.');
    exit($returnstatus);
}

// Die if the domain is not a valid hostname.
if (tool_opcache_get_host_header($options['domain']) === false) {
    cli_writeln('UNKNOWN - The domain must be a plain hostname or IP address (optionally with a port) ' .
            'without protocol and path.');
    exit($returnstatus);
}


// Fetch Opcache figures from webserver.
[$curlret, $curlerrno, $curlerror, $redirecterror] = tool_opcache_call_web_part(
    'check',
    $options['url'],
    (bool) $options['insecure'],
    trim((string) $options['domain']),
    (bool) $options['verbose']
);

// Die if the webserver redirected the request (for example because the given URL does not match $CFG->wwwroot).
if ($redirecterror !== '') {
    cli_writeln('UNKNOWN - ' . $redirecterror);
    exit(3);
}

// Evaluate the response of the webserver.
[$returnstatus, $out] = tool_opcache_evaluate_check_response(
    $curlret,
    $curlerrno,
    $curlerror,
    $options['warning'],
    $options['critical']
);

// Echo return string.
cli_writeln($out);

// Exit with calculated return status.
exit($returnstatus);
