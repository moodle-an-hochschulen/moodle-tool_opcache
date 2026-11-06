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

namespace tool_opcache;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/opcache/locallib.php');

/**
 * Admin tool "Opcache management" - Local library tests
 *
 * @package    tool_opcache
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Data provider for test_is_write_request().
     *
     * @return array
     */
    public static function is_write_request_provider(): array {
        return [
            'no parameters' => [
                'request' => [],
                'expected' => false,
            ],
            'read-only parameters only' => [
                'request' => ['time' => '1234567890', 'section' => 'files'],
                'expected' => false,
            ],
            'reset' => [
                'request' => ['reset' => '1'],
                'expected' => true,
            ],
            'reset with zero value' => [
                'request' => ['reset' => '0'],
                'expected' => true,
            ],
            'reset with empty value' => [
                'request' => ['reset' => ''],
                'expected' => true,
            ],
            'invalidate' => [
                'request' => ['invalidate' => '/var/www/moodle/index.php'],
                'expected' => true,
            ],
            'invalidate with array value' => [
                'request' => ['invalidate' => ['/var/www/moodle/index.php']],
                'expected' => true,
            ],
            'invalidate searched' => [
                'request' => ['invalidate_searched' => 'moodle'],
                'expected' => true,
            ],
            'similar parameter name' => [
                'request' => ['resetx' => '1', 'invalidate_foo' => 'moodle'],
                'expected' => false,
            ],
        ];
    }

    /**
     * Test that the requested write operations are detected in the request parameters.
     *
     * @dataProvider is_write_request_provider
     * @covers ::tool_opcache_is_write_request
     * @covers ::tool_opcache_get_write_params
     *
     * @param array $request The request parameters.
     * @param bool $expected The expected result.
     */
    public function test_is_write_request(array $request, bool $expected): void {
        $this->assertSame($expected, tool_opcache_is_write_request($request));
    }

    /**
     * Test that the sesskey is added to the URLs which trigger write operations and only to them.
     *
     * The JavaScript code in the output is taken from the Opcache GUI library.
     *
     * @covers ::tool_opcache_add_sesskey_to_write_links
     */
    public function test_add_sesskey_to_write_links(): void {
        // The backtick of the JavaScript template literal is composed with chr() as backticks in PHP strings are discouraged.
        $bt = chr(96);
        $output = "window.location.href = '?reset=1';\n" .
            "href: {$bt}?invalidate_searched=\${encodeURIComponent(this.state.searchTerm)}{$bt},\n" .
            "href: '?invalidate=' + this.props.full_path,\n" .
            "axios.get(window.location.pathname, { time: Date.now() })\n" .
            "href: '?section=files',\n";

        $result = tool_opcache_add_sesskey_to_write_links($output);

        $sesskey = sesskey();
        $this->assertStringContainsString("window.location.href = '?sesskey={$sesskey}&reset=1';", $result);
        $this->assertStringContainsString(
            "href: {$bt}?sesskey={$sesskey}&invalidate_searched=\${encodeURIComponent(this.state.searchTerm)}{$bt},",
            $result
        );
        $this->assertStringContainsString("href: '?sesskey={$sesskey}&invalidate=' + this.props.full_path,", $result);
        $this->assertStringContainsString("axios.get(window.location.pathname, { time: Date.now() })", $result);
        $this->assertStringContainsString("href: '?section=files',", $result);

        // Each write operation URL must have got exactly one sesskey.
        $this->assertSame(3, substr_count($result, 'sesskey='));
    }

    /**
     * Test that the axios request interceptor is added to the head of the Opcache GUI output.
     *
     * @covers ::tool_opcache_add_sesskey_to_write_requests
     */
    public function test_add_sesskey_to_write_requests(): void {
        $output = "<html><head><script>/* axios */</script><style></style></head><body></body></html>";

        $result = tool_opcache_add_sesskey_to_write_requests($output);

        // The interceptor must be added after the axios library and before the end of the head.
        $interceptorpos = strpos($result, 'axios.interceptors.request.use(');
        $this->assertNotFalse($interceptorpos);
        $this->assertGreaterThan(strpos($result, '/* axios */'), $interceptorpos);
        $this->assertLessThan(strpos($result, '</head>'), $interceptorpos);

        // The interceptor must contain the sesskey and all write operation parameters.
        $this->assertStringContainsString(json_encode(sesskey()), $result);
        $this->assertStringContainsString(json_encode(tool_opcache_get_write_params()), $result);

        // The rest of the output must remain untouched.
        $this->assertStringStartsWith('<html><head><script>/* axios */</script><style></style><script>', $result);
        $this->assertStringEndsWith('</script></head><body></body></html>', $result);
    }

    /**
     * Test that the output is left as it is if it does not contain any head end tag.
     *
     * @covers ::tool_opcache_add_sesskey_to_write_requests
     */
    public function test_add_sesskey_to_write_requests_without_head(): void {
        $output = 'The Zend OPcache extension is installed but not active';

        $this->assertSame($output, tool_opcache_add_sesskey_to_write_requests($output));
    }

    /**
     * Test that the sesskey is added to all write operation URLs and requests of the bundled Opcache GUI library.
     *
     * This test makes sure that the output processing still matches the Opcache GUI library after an update of the library.
     *
     * @covers ::tool_opcache_add_sesskey_to_write_links
     * @covers ::tool_opcache_add_sesskey_to_write_requests
     */
    public function test_bundled_gui_matches_output_processing(): void {
        global $CFG;

        // Take the bundled Opcache GUI library as output, as it contains the JavaScript code which is output as it is.
        $output = file_get_contents($CFG->dirroot . '/' . $CFG->admin . '/tool/opcache/lib/opcache-gui/index.php.inc');

        // Each write operation URL must have got the sesskey.
        $result = tool_opcache_add_sesskey_to_write_links($output);
        foreach (tool_opcache_get_write_params() as $param) {
            $this->assertStringNotContainsString('?' . $param . '=', $result);
            $this->assertStringContainsString('?sesskey=' . sesskey() . '&' . $param . '=', $result);
        }

        // The interceptor must have been added.
        $result = tool_opcache_add_sesskey_to_write_requests($result);
        $this->assertStringContainsString('axios.interceptors.request.use(', $result);
    }

    /**
     * Data provider for test_is_cli_tool_enabled().
     *
     * @return array
     */
    public static function is_cli_tool_enabled_provider(): array {
        return [
            'nothing set' => [
                'enabled' => null,
                'secretkey' => null,
                'expected' => false,
            ],
            'enabled' => [
                'enabled' => true,
                'secretkey' => null,
                'expected' => true,
            ],
            'explicitly disabled' => [
                'enabled' => false,
                'secretkey' => null,
                'expected' => false,
            ],
            'secret key set (backwards compatibility)' => [
                'enabled' => null,
                'secretkey' => 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa',
                'expected' => true,
            ],
            'empty secret key set' => [
                'enabled' => null,
                'secretkey' => '',
                'expected' => false,
            ],
            'enabled and secret key set' => [
                'enabled' => true,
                'secretkey' => 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa',
                'expected' => true,
            ],
        ];
    }

    /**
     * Test that the CLI tools are disabled by default and that they are enabled by the settings in config.php.
     *
     * @dataProvider is_cli_tool_enabled_provider
     * @covers ::tool_opcache_is_cli_tool_enabled
     * @covers ::tool_opcache_get_cfg_secretkey
     *
     * @param bool|null $enabled The value of the enabled setting in config.php or null if not set.
     * @param string|null $secretkey The value of the secret key setting in config.php or null if not set.
     * @param bool $expected The expected result.
     */
    public function test_is_cli_tool_enabled(?bool $enabled, ?string $secretkey, bool $expected): void {
        $this->resetAfterTest();

        foreach (['check', 'reset'] as $tool) {
            // Set the settings in config.php.
            if ($enabled !== null) {
                $this->set_config_php_setting('tool_opcache_' . $tool . '_enabled', $enabled);
            }
            if ($secretkey !== null) {
                $this->set_config_php_setting('tool_opcache_' . $tool . '_secretkey', $secretkey);
            }

            $this->assertSame($expected, tool_opcache_is_cli_tool_enabled($tool));
        }
    }

    /**
     * Test that the CLI tools are enabled independently from each other.
     *
     * @covers ::tool_opcache_is_cli_tool_enabled
     */
    public function test_is_cli_tool_enabled_independently(): void {
        $this->resetAfterTest();

        $this->set_config_php_setting('tool_opcache_check_enabled', true);

        $this->assertTrue(tool_opcache_is_cli_tool_enabled('check'));
        $this->assertFalse(tool_opcache_is_cli_tool_enabled('reset'));
    }

    /**
     * Test that any truthy value in config.php enables the CLI tools and that any falsy value does not.
     *
     * @covers ::tool_opcache_is_cli_tool_enabled
     */
    public function test_is_cli_tool_enabled_truthy(): void {
        $this->resetAfterTest();

        foreach ([true, 1, '1', 'yes'] as $value) {
            $this->set_config_php_setting('tool_opcache_check_enabled', $value);
            $this->assertTrue(tool_opcache_is_cli_tool_enabled('check'));
        }

        foreach ([false, 0, '0', ''] as $value) {
            $this->set_config_php_setting('tool_opcache_check_enabled', $value);
            $this->assertFalse(tool_opcache_is_cli_tool_enabled('check'));
        }
    }

    /**
     * Test that the CLI tools can also be enabled with a setting in the Moodle database.
     *
     * @covers ::tool_opcache_is_cli_tool_enabled
     */
    public function test_is_cli_tool_enabled_from_db(): void {
        $this->resetAfterTest();

        set_config('tool_opcache_check_enabled', 1);

        $this->assertTrue(tool_opcache_is_cli_tool_enabled('check'));
        $this->assertFalse(tool_opcache_is_cli_tool_enabled('reset'));

        set_config('tool_opcache_check_enabled', 0);

        $this->assertFalse(tool_opcache_is_cli_tool_enabled('check'));
    }

    /**
     * Test that the CLI tools can also be enabled with a secret key in the Moodle database and that it is used then.
     *
     * @covers ::tool_opcache_is_cli_tool_enabled
     * @covers ::tool_opcache_get_cfg_secretkey
     * @covers ::tool_opcache_get_secretkey
     * @covers ::tool_opcache_check_secretkey
     */
    public function test_secretkey_from_db(): void {
        $this->resetAfterTest();

        set_config('tool_opcache_reset_secretkey', 'dbsecretkey');

        $this->assertTrue(tool_opcache_is_cli_tool_enabled('reset'));
        $this->assertSame('dbsecretkey', tool_opcache_get_secretkey('reset', true));
        $this->assertTrue(tool_opcache_check_secretkey('reset', 'dbsecretkey'));

        // No secret key has been generated as the secret key from the Moodle database is used.
        $this->assertFalse(get_config('tool_opcache', 'resetsecretkey'));
    }

    /**
     * Test that the settings in config.php take precedence over the settings in the Moodle database.
     *
     * @covers ::tool_opcache_is_cli_tool_enabled
     * @covers ::tool_opcache_get_cfg_secretkey
     */
    public function test_config_php_takes_precedence_over_db(): void {
        $this->resetAfterTest();

        // A falsy value in config.php disables the CLI tool even if it is enabled in the Moodle database.
        $this->set_config_php_setting('tool_opcache_check_enabled', false);
        set_config('tool_opcache_check_enabled', 1);

        $this->assertFalse(tool_opcache_is_cli_tool_enabled('check'));

        // The secret key from config.php is used instead of the secret key from the Moodle database.
        $this->set_config_php_setting('tool_opcache_reset_secretkey', 'cfgsecretkey');
        set_config('tool_opcache_reset_secretkey', 'dbsecretkey');

        $this->assertSame('cfgsecretkey', tool_opcache_get_cfg_secretkey('reset'));
    }

    /**
     * Helper function to set a setting as if it was set in config.php.
     *
     * Moodle keeps the original value in $CFG->config_php_settings, but converts the value in $CFG to a string during
     * bootstrap (see initialise_cfg() and get_config()). This is mimicked here.
     *
     * @param string $name The name of the setting.
     * @param mixed $value The value of the setting.
     */
    private function set_config_php_setting(string $name, $value): void {
        global $CFG;

        $CFG->config_php_settings[$name] = $value;
        $CFG->$name = (string) $value;
    }

    /**
     * Test that the secret key from config.php takes precedence over the generated secret key.
     *
     * @covers ::tool_opcache_get_secretkey
     * @covers ::tool_opcache_get_cfg_secretkey
     */
    public function test_get_secretkey_from_cfg(): void {
        global $CFG;
        $this->resetAfterTest();

        set_config('checksecretkey', 'generatedsecretkey', 'tool_opcache');
        $CFG->tool_opcache_check_secretkey = 'cfgsecretkey';

        $this->assertSame('cfgsecretkey', tool_opcache_get_secretkey('check'));
        $this->assertSame('cfgsecretkey', tool_opcache_get_secretkey('check', true));
        $this->assertSame('generatedsecretkey', get_config('tool_opcache', 'checksecretkey'));
    }

    /**
     * Test that the secret key is only generated if requested and that it is stored and reused then.
     *
     * @covers ::tool_opcache_get_secretkey
     */
    public function test_get_secretkey_generated(): void {
        $this->resetAfterTest();

        // Without generation, there isn't any secret key yet.
        $this->assertSame('', tool_opcache_get_secretkey('check'));
        $this->assertFalse(get_config('tool_opcache', 'checksecretkey'));

        // With generation, an alphanumeric secret key is generated and stored.
        $secretkey = tool_opcache_get_secretkey('check', true);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $secretkey);
        $this->assertSame($secretkey, get_config('tool_opcache', 'checksecretkey'));

        // The stored secret key is reused afterwards.
        $this->assertSame($secretkey, tool_opcache_get_secretkey('check'));
        $this->assertSame($secretkey, tool_opcache_get_secretkey('check', true));

        // The secret key of the other CLI tool is not affected.
        $this->assertSame('', tool_opcache_get_secretkey('reset'));
        $this->assertNotSame($secretkey, tool_opcache_get_secretkey('reset', true));
    }

    /**
     * Data provider for test_check_secretkey().
     *
     * @return array
     */
    public static function check_secretkey_provider(): array {
        return [
            'correct secret key' => [
                'secretkey' => 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa',
                'submittedsecretkey' => 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa',
                'expected' => true,
            ],
            'wrong secret key' => [
                'secretkey' => 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa',
                'submittedsecretkey' => 'wrongsecretkey',
                'expected' => false,
            ],
            'empty submitted secret key' => [
                'secretkey' => 'P3ethed8yCrAYfshzUTsGVKwedwfNZE89o3L6JZqHKMa',
                'submittedsecretkey' => '',
                'expected' => false,
            ],
            'correct numeric secret key' => [
                'secretkey' => '1000',
                'submittedsecretkey' => '1000',
                'expected' => true,
            ],
            'numeric string with the same numeric value' => [
                'secretkey' => '1000',
                'submittedsecretkey' => '1e3',
                'expected' => false,
            ],
            'numeric string with leading zeros' => [
                'secretkey' => '1000',
                'submittedsecretkey' => '01000',
                'expected' => false,
            ],
        ];
    }

    /**
     * Test that the submitted secret key is checked strictly against the secret key.
     *
     * @dataProvider check_secretkey_provider
     * @covers ::tool_opcache_check_secretkey
     *
     * @param string $secretkey The secret key in config.php.
     * @param string $submittedsecretkey The submitted secret key.
     * @param bool $expected The expected result.
     */
    public function test_check_secretkey(string $secretkey, string $submittedsecretkey, bool $expected): void {
        global $CFG;
        $this->resetAfterTest();

        $CFG->tool_opcache_check_secretkey = $secretkey;

        $this->assertSame($expected, tool_opcache_check_secretkey('check', $submittedsecretkey));
    }

    /**
     * Test that no submitted secret key matches if there isn't any secret key and that no secret key is generated then.
     *
     * @covers ::tool_opcache_check_secretkey
     */
    public function test_check_secretkey_without_secretkey(): void {
        $this->resetAfterTest();

        $this->assertFalse(tool_opcache_check_secretkey('check', ''));
        $this->assertFalse(tool_opcache_check_secretkey('check', 'anysecretkey'));
        $this->assertFalse(get_config('tool_opcache', 'checksecretkey'));
    }

    /**
     * Test that the submitted secret key is checked against the generated secret key.
     *
     * @covers ::tool_opcache_check_secretkey
     */
    public function test_check_secretkey_generated(): void {
        $this->resetAfterTest();

        $secretkey = tool_opcache_get_secretkey('reset', true);

        $this->assertTrue(tool_opcache_check_secretkey('reset', $secretkey));
        $this->assertFalse(tool_opcache_check_secretkey('reset', strrev($secretkey)));
        $this->assertFalse(tool_opcache_check_secretkey('check', $secretkey));
    }

    /**
     * Data provider for test_evaluate_check_response().
     *
     * @return array
     */
    public static function evaluate_check_response_provider(): array {
        $invalidfigures = 'UNKNOWN - The webserver did not return valid Opcache figures. Opcache may be disabled on the ' .
                'webserver.';
        return [
            'nagios check disabled on the webserver' => [
                'response' => 'DISABLED',
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => 'UNKNOWN - The Nagios check is not enabled on the webserver. See README.md for details.',
            ],
            'wrong secret key' => [
                'response' => 'FORBIDDEN',
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 2,
                'expectedout' => 'CRITICAL - Secret key is required but is not correct.',
            ],
            'opcache unavailable on the webserver' => [
                'response' => 'UNAVAILABLE',
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => 'UNKNOWN - Opcache is not available on the webserver (extension not loaded, disabled or ' .
                        'API access restricted).',
            ],
            'webserver not reachable' => [
                'response' => false,
                'curlerrno' => 7,
                'curlerror' => 'Failed to connect to localhost',
                'expectedstatus' => 3,
                'expectedout' => 'UNKNOWN - The Opcache figures could not be fetched from the webserver: ' .
                        'Failed to connect to localhost',
            ],
            'empty response' => [
                'response' => '',
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => $invalidfigures,
            ],
            'error page' => [
                'response' => '<html><body>Internal Server Error</body></html>',
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => $invalidfigures,
            ],
            'figures preceded by a php warning' => [
                'response' => "Warning: Something went wrong\r\n30\r\n70\r\n99.5\r\n0.5",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => $invalidfigures,
            ],
            'too few figures' => [
                'response' => "30\r\n70\r\n99.5",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => $invalidfigures,
            ],
            'empty memory figures' => [
                'response' => "0\r\n0\r\n0\r\n0",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 3,
                'expectedout' => 'UNKNOWN - The webserver returned empty Opcache memory figures.',
            ],
            'below warning threshold' => [
                'response' => "30\r\n70\r\n99.5\r\n0.5",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 0,
                'expectedout' => 'OK - 30% cache used | used_pct=30%;80;90 hit_pct=99.5%; miss_pct=0.5%;',
            ],
            'below warning threshold with unix line endings' => [
                'response' => "30\n70\n99.5\n0.5\n",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 0,
                'expectedout' => 'OK - 30% cache used | used_pct=30%;80;90 hit_pct=99.5%; miss_pct=0.5%;',
            ],
            'warning threshold reached' => [
                'response' => "80\r\n20\r\n99.5\r\n0.5",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 1,
                'expectedout' => 'WARNING - 80% cache used | used_pct=80%;80;90 hit_pct=99.5%; miss_pct=0.5%;',
            ],
            'critical threshold reached' => [
                'response' => "95\r\n5\r\n99.5\r\n0.5",
                'curlerrno' => 0,
                'curlerror' => '',
                'expectedstatus' => 2,
                'expectedout' => 'CRITICAL - 95% cache used | used_pct=95%;80;90 hit_pct=99.5%; miss_pct=0.5%;',
            ],
        ];
    }

    /**
     * Test that the response of the web part of the Nagios check is evaluated to the correct Nagios status and output.
     *
     * @dataProvider evaluate_check_response_provider
     * @covers ::tool_opcache_evaluate_check_response
     *
     * @param string|bool $response The response of the web part of the Nagios check.
     * @param int $curlerrno The curl error number.
     * @param string $curlerror The curl error message.
     * @param int $expectedstatus The expected Nagios return status.
     * @param string $expectedout The expected output.
     */
    public function test_evaluate_check_response($response, int $curlerrno, string $curlerror, int $expectedstatus,
            string $expectedout): void {
        $this->assertSame([$expectedstatus, $expectedout],
                tool_opcache_evaluate_check_response($response, $curlerrno, $curlerror, 80, 90));
    }
}
