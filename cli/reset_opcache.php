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
require_once(__DIR__ . '/../locallib.php');


// Get cli options.
[$options, $unrecognized] = cli_get_params(
    ['help' => false,
                                                    'url' => null,
                                                    'reset' => false,
                                                    'insecure' => false,
                                                    'domain' => null,
                                                    'verbose' => false, ],
    ['h' => 'help',
                                                    'u' => 'url',
    'r' => 'reset',
    'k' => 'insecure',
    'd' => 'domain',
    'v' => 'verbose',
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
\$ sudo -u www-data /usr/bin/php public/admin/tool/opcache/cli/reset_opcache.php " .
        "--url=\"https://example.com/admin/tool/opcache/cli/reset_opcache_web.php\" --reset
";
    cli_writeln($help);
    exit(0);
}


// Die if resetting opcache is not enabled.
if (!tool_opcache_is_cli_tool_enabled('reset')) {
    cli_error('The Opcache Reset is not enabled in config.php. See README.md for details.');
}


// Clean CLI parameters.
$options['url'] = clean_param($options['url'], PARAM_URL);

// Die if the domain is not a valid hostname.
if (tool_opcache_get_host_header($options['domain']) === false) {
    cli_error('The domain must be a plain hostname or IP address (optionally with a port) without protocol and path.');
}


// Reset opcache on webserver.
[$curlret, $curlerrno, $curlerror, $redirecterror] = tool_opcache_call_web_part(
    'reset',
    $options['url'],
    (bool) $options['insecure'],
    trim((string) $options['domain']),
    (bool) $options['verbose']
);

// Die if the webserver could not be reached.
if ($curlerrno) {
    cli_error('PHP opcache could not be reset as requested. The webserver could not be reached: ' . $curlerror);
}

// Die if the webserver redirected the request (for example because the given URL does not match $CFG->wwwroot).
if ($redirecterror !== '') {
    cli_error('PHP opcache could not be reset as requested. ' . $redirecterror);
}

// Echo return string and exit with return status.
if ($curlret == '0') {
    cli_writeln('PHP opcache has been reset successfully on this server.');
    exit(0);
} else if ($curlret == '1') {
    cli_error('The Opcache Reset is not enabled in config.php. See README.md for details.');
} else if ($curlret == '2') {
    cli_error('Secret key is required but is not correct. See README.md for details.');
} else if ($curlret == '3') {
    cli_error('PHP opcache could not be reset as requested. The Opcache extension is not loaded on this server.');
} else {
    cli_error('PHP opcache could not be reset as requested. PHP opcache may simply be disabled on this server.');
}
