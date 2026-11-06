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
 * Admin tool "Opcache management" - Nagios monitoring check, part "web"
 *
 * @package    tool_opcache
 * @copyright  2017 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @copyright  inspired by code by Mikanoshi, https://exchange.icinga.com/Mikanoshi/PHP+opcache+monitoring+plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Do not start a Moodle session as the web part is called without any cookies by its CLI part.
// Otherwise, a new session would be created with each call.
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../locallib.php');

// Die if the Nagios check is not enabled.
if (!tool_opcache_is_cli_tool_enabled('check')) {
    echo 'DISABLED';
    exit;
}

// Die if the submitted secret key is not correct.
if (!tool_opcache_check_secretkey('check', optional_param('secret', '', PARAM_ALPHANUM))) {
    echo 'FORBIDDEN';
    exit;
}

// Die if the Opcache extension is not loaded on the webserver.
if (!function_exists('opcache_get_status')) {
    echo 'UNAVAILABLE';
    exit;
}

// Get Opcache status.
// The list of cached scripts is not fetched as only the memory and statistics figures are needed.
// Die if Opcache is disabled or if the API access is restricted on the webserver.
$opcache = @opcache_get_status(false);
if (!is_array($opcache)) {
    echo 'UNAVAILABLE';
    exit;
}

// Pick and calculate Opcache figures.
$used = $opcache['memory_usage']['used_memory'];
$free = $opcache['memory_usage']['free_memory'];
$totalreq = $opcache['opcache_statistics']['blacklist_misses'] + $opcache['opcache_statistics']['misses'] +
        $opcache['opcache_statistics']['hits'];
$hitspct = round($opcache['opcache_statistics']['opcache_hit_rate'], 2, PHP_ROUND_HALF_UP);
$misspct = $totalreq > 0 ?
        round($opcache['opcache_statistics']['misses'] / $totalreq * 100, 2, PHP_ROUND_HALF_UP) : 0;

// Echo Opcache figures.
echo $used . "\r\n" . $free . "\r\n" . $hitspct . "\r\n" . $misspct;
