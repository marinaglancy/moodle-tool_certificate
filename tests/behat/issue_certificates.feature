@tool @tool_certificate
Feature: Being able to manually issue a certificate to a user
  In order to manually issue a new certificate to a user
  As an admin
  I need to be able to issue a certificate from a list of users

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log in as "admin"

  @javascript
  Scenario: Issue a certificate as admin, from the list of templates
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Issue new certificate from this template" "link"
    And I set the field "Select users to issue certificate for" to "Student"
    And I wait until the page is ready
    And I press "Issue new certificates"
    Then I should see "One issue was created"

  @javascript
  Scenario: Issue a certificate as admin, from the list of issues
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificates issued"
    And I click on "Issue new certificates" "link"
    And I set the field "Select users to issue certificate for" to "Student"
    And I wait until the page is ready
    And I press "Issue new certificates"
    Then I should see "One issue was created"
