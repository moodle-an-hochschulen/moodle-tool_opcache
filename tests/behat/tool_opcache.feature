@tool @tool_opcache @javascript
Feature: Using the admin tool opcache plugin
  In order to show the opcache management
  As admin
  I need to be able open the opcache manangement page

  Scenario: Calling the opcache management page
    When I log in as "admin"
    And I navigate to "Server > Opcache management" in site administration
    Then I should see "Overview" in the ".opcache-gui .nav-tab-list" "css_element"
    And I should see "memory" in the ".opcache-gui #counts" "css_element"
    And I should see "hit rate" in the ".opcache-gui #counts" "css_element"
    And I should see "keys" in the ".opcache-gui #counts" "css_element"
    And I should see "opcache statistics" in the ".opcache-gui #counts" "css_element"

  # Unfortunately, the refusal of the write operations without sesskey can't be tested with Behat as Moodle core would throw a
  # 'A required parameter (sesskey) was missing' exception when calling the URL without sesskey.
  # Scenario Outline: Write operations of the opcache management page are refused without sesskey (Countercheck)

  Scenario: Resetting the opcache from the opcache management page
    Given I log in as "admin"
    And I visit "/admin/tool/opcache/tests/behat/fixtures/cache_behat_file.php"
    And I should see "This file has been cached in the opcache"
    And I navigate to "Server > Opcache management" in site administration
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see ", 1 showing due to filter 'cache_behat_file'" in the "#cached-content" "css_element"
    When I click on "Reset cache" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    Then I should see "Overview" in the ".opcache-gui .nav-tab-list" "css_element"
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see ", 0 showing due to filter 'cache_behat_file'" in the "#cached-content" "css_element"
    And "tr[data-path$='cache_behat_file.php']" "css_element" should not exist

  Scenario: Invalidating a file from the opcache management page
    Given I log in as "admin"
    And I visit "/admin/tool/opcache/tests/behat/fixtures/cache_behat_file.php"
    And I should see "This file has been cached in the opcache"
    And I navigate to "Server > Opcache management" in site administration
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see ", 1 showing due to filter 'cache_behat_file'" in the "#cached-content" "css_element"
    And I should not see "has been invalidated" in the "tr[data-path$='cache_behat_file.php']" "css_element"
    And the "href" attribute of "tr[data-path$='cache_behat_file.php'] a" "css_element" should contain "sesskey="
    When I click on "force file invalidation" "link" in the "tr[data-path$='cache_behat_file.php']" "css_element"
    Then I should see "Overview" in the ".opcache-gui .nav-tab-list" "css_element"
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see "has been invalidated" in the "tr[data-path$='cache_behat_file.php']" "css_element"

  Scenario: Invalidating all matching files from the opcache management page
    Given I log in as "admin"
    And I visit "/admin/tool/opcache/tests/behat/fixtures/cache_behat_file.php"
    And I should see "This file has been cached in the opcache"
    And I navigate to "Server > Opcache management" in site administration
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see ", 1 showing due to filter 'cache_behat_file'" in the "#cached-content" "css_element"
    And I should not see "has been invalidated" in the "tr[data-path$='cache_behat_file.php']" "css_element"
    And the "href" attribute of "Invalidate all matching files" "link" should contain "sesskey="
    When I click on "Invalidate all matching files" "link"
    Then I should see "Overview" in the ".opcache-gui .nav-tab-list" "css_element"
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see "has been invalidated" in the "tr[data-path$='cache_behat_file.php']" "css_element"

  Scenario: Invalidating a file from the opcache management page in real-time mode
    Given I log in as "admin"
    And I visit "/admin/tool/opcache/tests/behat/fixtures/cache_behat_file.php"
    And I should see "This file has been cached in the opcache"
    And I navigate to "Server > Opcache management" in site administration
    And I click on "Enable real-time update" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I click on "Cached" "text" in the ".opcache-gui .nav-tab-list" "css_element"
    And I set the field "Start typing to filter on script path" to "cache_behat_file"
    And I should see ", 1 showing due to filter 'cache_behat_file'" in the "#cached-content" "css_element"
    And I should not see "has been invalidated" in the "tr[data-path$='cache_behat_file.php']" "css_element"
    When I click on "force file invalidation" "link" in the "tr[data-path$='cache_behat_file.php']" "css_element"
    # Wait for the next real-time update of the Opcache GUI which happens every 5 seconds.
    And I wait "6" seconds
    Then I should see "has been invalidated" in the "tr[data-path$='cache_behat_file.php']" "css_element"
