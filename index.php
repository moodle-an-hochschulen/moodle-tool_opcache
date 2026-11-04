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
 * Admin tool "Opcache management" - Main page
 *
 * @package    tool_opcache
 * @copyright  2017 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/opcache/locallib.php');

// Set up the plugin's main page as external admin page.
admin_externalpage_setup('tool_opcache');

// If a write operation of the Opcache GUI is requested, make sure that the request is not forged.
// The Opcache GUI does not have any CSRF protection itself and it reads the write operation parameters from $_GET only.
// This check has to be done before the Opcache GUI is included as the Opcache GUI performs the write operations directly.
if (tool_opcache_is_write_request($_GET)) {
    require_sesskey();
}

// Now: Distinguish the two possible request modes of opcache-gui.
// Request mode: Real-time update AJAX request.
// This request mode is identified in exactly the same way as opcache-gui does it itself.
if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    // Include Opcache just for returning the AJAX request like it would work when Opcache GUI is called standalone.
    require_once(__DIR__ . '/lib/opcache-gui/index.php.inc');

    // Request mode: Display Opcache GUI.
} else {
    // Page setup.
    $title = get_string('pluginname', 'tool_opcache');
    $PAGE->set_url('/' . $CFG->admin . '/tool/opcache/index.php');
    $PAGE->set_title($title);
    $PAGE->set_heading($title);

    // Output has to be buffered because Opcache GUI might add headers which wouldn't be possible if output has already started.
    ob_start();

    // Page setup.
    echo $OUTPUT->header();

    // Include Opcache GUI.
    // Its output is buffered separately because we want to modify it.
    // If the Opcache GUI performs a write operation, it redirects itself to its main view and does not return here.
    ob_start();
    require_once(__DIR__ . '/lib/opcache-gui/index.php.inc');
    $opcacheguioutput = ob_get_clean();

    // Protect the write operations of the Opcache GUI with the sesskey.
    $opcacheguioutput = tool_opcache_add_sesskey_to_write_links($opcacheguioutput);
    $opcacheguioutput = tool_opcache_add_sesskey_to_write_requests($opcacheguioutput);

    // Output the Opcache GUI.
    echo $opcacheguioutput;

    // Page setup.
    echo $OUTPUT->footer();

    // Output buffered content.
    ob_end_flush();
}
