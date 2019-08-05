@tool @tool_opcache @javascript
Feature: Using the admin tool opcache plugin
  In order to show the opcache management
  As admin
  I need to be able open the opcache manangement page

  Scenario: Calling the opcache management page
    When I log in as "admin"
    And I navigate to "Server > Opcache management" in site administration
    Then I should see "Overview"
    And I should see "File usage"
    And I should see "hit rate"
    And I should see "memory usage"
