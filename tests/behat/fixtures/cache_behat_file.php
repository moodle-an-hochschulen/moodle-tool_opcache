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
 * Admin tool "Opcache management" - Behat fixture page which is cached in the opcache of the webserver
 *
 * The opcache of the webserver can't be filled from the Behat CLI process, as the CLI process has its own opcache memory.
 * Thus, this page is visited by the Behat tests to get this file into the opcache of the webserver.
 * As this file is not used anywhere else, it can then be found in the Opcache GUI, be invalidated and stays invalidated.
 *
 * @package    tool_opcache
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../config.php');

defined('BEHAT_SITE_RUNNING') || die();

global $CFG, $PAGE, $OUTPUT;

require_admin();

$PAGE->set_url('/' . $CFG->admin . '/tool/opcache/tests/behat/fixtures/cache_behat_file.php');
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->notification('This file has been cached in the opcache', 'success');
echo $OUTPUT->footer();
