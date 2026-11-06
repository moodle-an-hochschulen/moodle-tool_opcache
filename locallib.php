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

/**
 * Helper function to get the secret key which is set for the given CLI tool in config.php.
 *
 * @param string $tool The CLI tool, i.e. 'check' (Nagios check) or 'reset' (CLI tool to reset PHP opcache).
 *
 * @return string The secret key or an empty string if no secret key is set in config.php.
 */
function tool_opcache_get_cfg_secretkey($tool) {
    global $CFG;

    // Get the secret key from config.php.
    $setting = 'tool_opcache_' . $tool . '_secretkey';
    if (!isset($CFG->$setting) || !is_scalar($CFG->$setting)) {
        return '';
    }

    return (string) $CFG->$setting;
}

/**
 * Helper function to check if the given CLI tool is enabled in config.php.
 *
 * The CLI tools are disabled by default. They are enabled with a setting in config.php.
 * For backwards compatibility, they are also enabled if a secret key is set in config.php.
 *
 * @param string $tool The CLI tool, i.e. 'check' (Nagios check) or 'reset' (CLI tool to reset PHP opcache).
 *
 * @return bool
 */
function tool_opcache_is_cli_tool_enabled($tool) {
    global $CFG;

    // Check if the CLI tool is enabled explicitly in config.php.
    // Any truthy value like true or 1 enables the CLI tool.
    // The setting is read from $CFG->config_php_settings and not from $CFG directly to make sure that the CLI tool can only
    // be enabled in config.php and not by a setting with the same name in the Moodle database.
    $setting = 'tool_opcache_' . $tool . '_enabled';
    if (!empty($CFG->config_php_settings[$setting])) {
        return true;
    }

    // Check if the CLI tool is enabled implicitly by a secret key in config.php.
    return tool_opcache_get_cfg_secretkey($tool) !== '';
}

/**
 * Helper function to get the secret key which is used by the given CLI tool to call its web part.
 *
 * If a secret key is set in config.php, this secret key is used.
 * Otherwise, the secret key which has been generated automatically and which is stored in the plugin config is used.
 *
 * @param string $tool The CLI tool, i.e. 'check' (Nagios check) or 'reset' (CLI tool to reset PHP opcache).
 * @param bool $generate Generate and store a secret key if there isn't any secret key yet.
 *                       This should only be done by the CLI part of the CLI tool and not by the web part which can be called
 *                       by anybody.
 *
 * @return string The secret key or an empty string if there isn't any secret key (yet).
 */
function tool_opcache_get_secretkey($tool, $generate = false) {
    // If a secret key is set in config.php, use it.
    $secretkey = tool_opcache_get_cfg_secretkey($tool);
    if ($secretkey !== '') {
        return $secretkey;
    }

    // Otherwise, get the secret key from the plugin config.
    $secretkey = (string) get_config('tool_opcache', $tool . 'secretkey');

    // If there isn't any secret key yet and if requested, generate and store a secret key.
    // The secret key is alphanumeric as the web part cleans the submitted secret key with PARAM_ALPHANUM.
    if ($secretkey === '' && $generate == true) {
        $secretkey = random_string(64);
        set_config($tool . 'secretkey', $secretkey, 'tool_opcache');
    }

    return $secretkey;
}

/**
 * Helper function to check if the given submitted secret key matches the secret key of the given CLI tool.
 *
 * @param string $tool The CLI tool, i.e. 'check' (Nagios check) or 'reset' (CLI tool to reset PHP opcache).
 * @param string $submittedsecretkey The submitted secret key.
 *
 * @return bool
 */
function tool_opcache_check_secretkey($tool, $submittedsecretkey) {
    // If there isn't any secret key, no submitted secret key can match.
    $secretkey = tool_opcache_get_secretkey($tool);
    if ($secretkey === '') {
        return false;
    }

    // Compare the secret keys with hash_equals() as it compares the strings in constant time and as it does not compare
    // numeric strings as numbers (as '!=' does, where '1e3' would match '1000').
    return hash_equals($secretkey, (string) $submittedsecretkey);
}

/**
 * Helper function to evaluate the response of the web part of the Nagios check.
 *
 * @param string|bool $response The response of the web part of the Nagios check.
 * @param int $curlerrno The curl error number of the request to the web part (0 if there wasn't any error).
 * @param string $curlerror The curl error message of the request to the web part.
 * @param int $warning The threshold for warning (used cache in percent).
 * @param int $critical The threshold for critical (used cache in percent).
 *
 * @return array An array with the Nagios return status (0 = OK, 1 = WARNING, 2 = CRITICAL, 3 = UNKNOWN) and the output.
 */
function tool_opcache_evaluate_check_response($response, $curlerrno, $curlerror, $warning, $critical) {
    // Die if the Nagios check is not enabled on the webserver (basically, this should not happen).
    if ($response === 'DISABLED') {
        return [3, 'UNKNOWN - The Nagios check is not enabled on the webserver. See README.md for details.'];
    }

    // Die if the secret key is not correct (basically, this should not happen).
    if ($response === 'FORBIDDEN') {
        return [2, 'CRITICAL - Secret key is required but is not correct.'];
    }

    // Die if Opcache is not available on the webserver.
    if ($response === 'UNAVAILABLE') {
        return [3, 'UNKNOWN - Opcache is not available on the webserver (extension not loaded, disabled or API access ' .
                'restricted).'];
    }

    // Die if the webserver could not be reached.
    if ($curlerrno) {
        return [3, 'UNKNOWN - The Opcache figures could not be fetched from the webserver: ' . $curlerror];
    }

    // Die if the webserver did not return the four Opcache figures (for example an error page or an empty response).
    // The web part separates the figures with "\r\n", so do not split by PHP_EOL here.
    $figures = preg_split('/\r?\n/', trim((string) $response));
    if (count($figures) != 4 || count(array_filter($figures, 'is_numeric')) != 4) {
        return [3, 'UNKNOWN - The webserver did not return valid Opcache figures. Opcache may be disabled on the webserver.'];
    }

    // Pick Opcache figures.
    $used = clean_param($figures[0], PARAM_INT);
    $free = clean_param($figures[1], PARAM_INT);
    $hitspct = clean_param($figures[2], PARAM_FLOAT);
    $misspct = clean_param($figures[3], PARAM_FLOAT);

    // Die if the Opcache memory figures are empty (basically, this should not happen).
    if ($used + $free <= 0) {
        return [3, 'UNKNOWN - The webserver returned empty Opcache memory figures.'];
    }

    // Calculate used percentage value.
    $usedpct = round($used / ($used + $free) * 100, 1);
    // Calculate return status.
    if ($usedpct >= $critical) {
        $returnstatus = 2;
        $prefix = 'CRITICAL';
    } else if ($usedpct >= $warning) {
        $returnstatus = 1;
        $prefix = 'WARNING';
    } else {
        $returnstatus = 0;
        $prefix = 'OK';
    }

    // Concatenate return string including performance data.
    $out = $prefix . ' - ' . $usedpct . "% cache used | " .
            "used_pct=" . $usedpct . "%;" . $warning . ";" . $critical . " hit_pct=" . $hitspct .
                    "%; miss_pct=" . $misspct . "%;";

    return [$returnstatus, $out];
}
