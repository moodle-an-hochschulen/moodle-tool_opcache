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

// @codingStandardsIgnoreFile
// Let codechecker ignore this file because otherwise it would complain about a missing MOODLE_INTERNAL check or config.php
// inclusion which is really not needed.

// Get Opcache status.
$opcache = opcache_get_status();

// Pick and calculate Opcache figures.
$used = $opcache['memory_usage']['used_memory'];
$free = $opcache['memory_usage']['free_memory'];
$totalreq = $opcache['opcache_statistics']['blacklist_misses'] + $opcache['opcache_statistics']['misses'] +
        $opcache['opcache_statistics']['hits'];
$hitspct = round($opcache['opcache_statistics']['opcache_hit_rate'], 2, PHP_ROUND_HALF_UP);
$misspct = round($opcache['opcache_statistics']['misses'] / $totalreq * 100, 2, PHP_ROUND_HALF_UP);

// Echo Opcache figures.
echo $used."\r\n".$free."\r\n".$hitspct."\r\n".$misspct;
