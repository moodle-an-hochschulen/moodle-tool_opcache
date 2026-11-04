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
 * Admin tool "Opcache management" - Local library
 *
 * @package    tool_opcache
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper function to get the request parameters which trigger write operations in the Opcache GUI.
 *
 * These write operations are protected with a sesskey check by this plugin.
 * With each update of the Opcache GUI library, check if there are new write operations which have to be added here.
 *
 * @return array
 */
function tool_opcache_get_write_params() {
    // The request parameters of the Opcache GUI which trigger write operations.
    return [
        'reset', // Reset the whole cache.
        'invalidate', // Invalidate a particular file.
        'invalidate_searched', // Invalidate all files which match a search term.
    ];
}

/**
 * Helper function to check if the given request parameters request a write operation of the Opcache GUI.
 *
 * @param array $request The request parameters, e.g. $_GET.
 *
 * @return bool
 */
function tool_opcache_is_write_request($request) {
    // Iterate over all request parameters which trigger write operations.
    foreach (tool_opcache_get_write_params() as $param) {
        // If the parameter is given at all, a write operation is requested.
        // The Opcache GUI checks the parameters with isset() and does not care about their values (like reset=0),
        // so we must not care about the values either.
        if (array_key_exists($param, $request)) {
            return true;
        }
    }

    // If we have reached this point, no write operation is requested.
    return false;
}

/**
 * Helper function to add the sesskey to all URLs in the given Opcache GUI output which trigger write operations.
 *
 * The Opcache GUI builds these URLs in its JavaScript code as relative URLs which start with the write operation
 * parameter (like '?reset=1' or '?invalidate=' + path). The sesskey is inserted in front of the write operation parameter,
 * so that the rest of the JavaScript expression which builds the URL remains untouched.
 *
 * @param string $output The buffered Opcache GUI output.
 *
 * @return string
 */
function tool_opcache_add_sesskey_to_write_links($output) {
    // Iterate over all request parameters which trigger write operations.
    foreach (tool_opcache_get_write_params() as $param) {
        // Insert the sesskey in front of the write operation parameter.
        // The trailing '=' makes sure that 'invalidate' does not match 'invalidate_searched'.
        $output = str_replace('?' . $param . '=', '?sesskey=' . sesskey() . '&' . $param . '=', $output);
    }

    return $output;
}

/**
 * Helper function to add the sesskey to all AJAX requests of the given Opcache GUI output which trigger write operations.
 *
 * In real-time mode, the Opcache GUI sends its write operations as AJAX requests with axios.
 * For these requests, an axios request interceptor is added to the head of the Opcache GUI output.
 * The interceptor adds the sesskey to all requests which contain a write operation parameter.
 *
 * @param string $output The buffered Opcache GUI output.
 *
 * @return string
 */
function tool_opcache_add_sesskey_to_write_requests($output) {
    // Compose the interceptor script.
    $script = '<script>' .
            'axios.interceptors.request.use(function(config) {' .
                'var writeparams = ' . json_encode(tool_opcache_get_write_params()) . ';' .
                'if (config.params && writeparams.some(function(param) {' .
                    'return Object.prototype.hasOwnProperty.call(config.params, param);' .
                '})) {' .
                    'config.params = Object.assign({}, config.params, {sesskey: ' . json_encode(sesskey()) . '});' .
                '}' .
                'return config;' .
            '});' .
            '</script>';

    // Add the interceptor script at the end of the head, i.e. after the axios library has been loaded.
    // If there isn't any head end tag, the output is not the Opcache GUI which we expect and we leave it as it is.
    // In this case, the write operations will just fail the sesskey check.
    $pos = strpos($output, '</head>');
    if ($pos === false) {
        return $output;
    }

    return substr_replace($output, $script, $pos, 0);
}
