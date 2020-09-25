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
