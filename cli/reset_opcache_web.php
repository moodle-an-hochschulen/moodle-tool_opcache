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
 * Admin tool "Opcache management" - CLI Script to reset opcache, part "web"
 *
 * @package    tool_opcache
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable
// Let codechecker ignore this file because otherwise it would complain about a missing MOODLE_INTERNAL check or config.php
// inclusion which is really not needed.

require(__DIR__.'/../../../../config.php');

global $CFG, $PAGE;

// Die if resetting opcache is not actively enabled.
if (!isset($CFG->tool_opcache_reset_secretkey)) {
    echo 1;
    exit;

    // And die if the submitted secret key is not correct.
} else if ($CFG->tool_opcache_reset_secretkey != clean_param($PAGE->url->get_param('secret'), PARAM_ALPHANUM)) {
    echo 2;
    exit;
}

// Reset opcache.
$retval = opcache_reset();

// Echo exit status.
if ($retval == true) {
    echo 0;
} else {
    echo 255;
}
