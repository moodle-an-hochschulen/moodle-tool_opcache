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
}
