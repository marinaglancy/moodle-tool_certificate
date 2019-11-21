@tool @tool_certificate @moodleworkplace
Feature: Being able to view the certificates that have been issued
  In order to ensure that a user can view the certificates that have been issued
  As an admin
  I need to view the certificates that have been issued

  Background:
    Given "2" tenants exist with "3" users and "0" courses in each
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | manager0 | Manager   | A        | viewer1@example.com |
      | manager1 | Manager   | 1        | viewer1@example.com |
      | manager2 | Manager   | 2        | viewer1@example.com |
    And the following users allocations to tenants exist:
      | user     | tenant  |
      | manager1 | Tenant1 |
      | manager2 | Tenant2 |
    And the following certificate templates exist:
      | name          | category  |
      | Certificate 0 |           |
      | Certificate 1 | Category1 |
      | Certificate 2 | Category2 |
    And the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
      | Certificate 2 | user21 |
      | Certificate 0 | user22 |
      | Certificate 0 | user12 |
    And the following "roles" exist:
      | shortname             | name                        | archetype |
      | certificatemanager    | Certificate manager         |           |
      | certificatemanagerall | Certificate manager for all |           |
      | certificateissuer     | Certificate issuer          |           |
      | certificateissuerall  | Certificate issuer for all  |           |
      | certificateviewer     | Certificate viewer          |           |
      | configviewer          | Config viewer               |           |
    And I log in as "admin"
    And I set the following system permissions of "Certificate manager for all" role:
      | capability                     | permission |
      | tool/certificate:manage        | Allow      |
      | moodle/category:viewcourselist | Allow      |
      | moodle/site:configview         | Allow      |
    And I set the following system permissions of "Certificate manager" role:
      | capability              | permission |
      | tool/certificate:manage | Allow      |
    And I set the following system permissions of "Certificate issuer" role:
      | capability             | permission |
      | tool/certificate:issue | Allow      |
    And I set the following system permissions of "Certificate issuer for all" role:
      | capability                     | permission |
      | tool/certificate:issue         | Allow      |
      | tool/tenant:allocate           | Allow      |
      | moodle/category:viewcourselist | Allow      |
      | moodle/site:configview         | Allow      |
    And I set the following system permissions of "Certificate viewer" role:
      | capability                           | permission |
      | tool/certificate:viewallcertificates | Allow      |
      | moodle/site:configview               | Allow      |
    And I set the following system permissions of "Config viewer" role:
      | capability                           | permission |
      | moodle/site:configview               | Allow      |
    And I log out

  Scenario: View the issued certificates as admin
    When I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"

  @javascript
  Scenario: Revoke an issued certificate as admin
    When I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And I should see "User 11"
    And I should see "User 12"
    And I click on "Revoke" "link" in the "User 12" "table_row"
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I should see "User 12"
    And I click on "Revoke" "link" in the "User 12" "table_row"
    And I click on "Revoke" "button" in the "Confirm" "dialogue"
    And I should see "User 11"
    And I should not see "User 12"

  Scenario: View certificates in your own tenant as a certificate issuer
    And the following "role assigns" exist:
      | user     | role              | contextlevel | reference |
      | manager1 | certificateissuer | Category     | CAT1      |
      | manager1 | configviewer      | System       |           |
    And I log in as "manager1"
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should not see "Certificate 2"
    And "Issue new certificate from this template" "link" should exist in the "Certificate 1" "table_row"
    And I should not see "Certificate 0"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And "Issue new certificate" "link" should exist
    And I should see "User 11"
    And I should see "User 12"
    And I log out

  Scenario: View certificates in your own tenant as a certificate viewer
    And the following "role assigns" exist:
      | user     | role              | contextlevel | reference |
      | manager1 | certificateviewer | Category     | CAT1      |
      | manager1 | configviewer      | System       |           |
    And I log in as "manager1"
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should not see "Certificate 2"
    And "Issue new certificate from this template" "link" should not exist in the "Certificate 1" "table_row"
    And I should not see "Certificate 0"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And "Issue new certificate" "link" should not exist
    And I should see "User 11"
    And I should see "User 12"
    And I log out

  Scenario: View certificates as a person who can manage certificates for one tenant but can not issue
    And the following "role assigns" exist:
      | user     | role               | contextlevel | reference |
      | manager1 | certificatemanager | Category     | CAT1      |
      | manager1 | configviewer       | System       |           |
    And I log in as "manager1"
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should not see "Certificate 2"
    And "Issue new certificate from this template" "link" should not exist in the "Certificate 1" "table_row"
    And I should not see "Certification 0"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And "Issue new certificate" "link" should not exist
    And I should see "User 11"
    And I should see "User 12"
    And I log out

  Scenario: View certificates in all tenants as a certificate issuer
    And the following "role assigns" exist:
      | user     | role                 | contextlevel | reference |
      | manager0 | certificateissuerall | System       |           |
    And I log in as "manager0"
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And "Issue new certificate from this template" "link" should exist in the "Certificate 2" "table_row"
    And "Issue new certificate from this template" "link" should exist in the "Certificate 1" "table_row"
    And "Issue new certificate from this template" "link" should exist in the "Certificate 0" "table_row"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And "Issue new certificate" "link" should exist
    And I should see "User 11"
    And I should see "User 12"
    And I follow "Manage certificate templates"
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And "Issue new certificate" "link" should exist
    And I should not see "User 11"
    And I should see "User 12"
    And I should see "User 22"
    And I log out

  Scenario: View certificates in all tenants as a certificate manager
    And the following "role assigns" exist:
      | user     | role                  | contextlevel | reference |
      | manager1 | certificatemanagerall | System       |           |
    And I log in as "manager1"
    And I follow "Site administration"
    Then I should see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Certificate images"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And "Issue new certificate from this template" "link" should not exist in the "Certificate 2" "table_row"
    And "Issue new certificate from this template" "link" should not exist in the "Certificate 1" "table_row"
    And "Issue new certificate from this template" "link" should not exist in the "Certificate 0" "table_row"
    And I click on "Certificates issued" "link" in the "Certificate 1" "table_row"
    And "Issue new certificate" "link" should not exist
    And I should see "User 12"
    And I follow "Manage certificate templates"
    And I click on "Certificates issued" "link" in the "Certificate 0" "table_row"
    And "Issue new certificate" "link" should not exist
    And I should not see "User 11"
    And I should see "User 12"
    And I should not see "User 2"
    And I log out

  Scenario: Verify any certificate for any tenant as a guest using the site-wide URL
    When I log in as "admin"
    And I set the following system permissions of "Guest" role:
      | capability              | permission |
      | tool/certificate:verify | Allow      |
    And I log out
    And I visit the sites certificates verification url
    And I verify the "Certificate 1" site certificate for the user "user11"
    And I verify the "Certificate 1" site certificate for the user "user12"
    And I verify the "Certificate 2" site certificate for the user "user21"
    And I verify the "Certificate 0" site certificate for the user "user22"
    And I verify the "Certificate 0" site certificate for the user "user12"

  Scenario: User who can verify certificates but can not manage or issue should not see it in site administration
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability              | permission |
      | tool/certificate:verify | Allow      |
      | moodle/site:configview  | Allow      |
    And I log out
    When I log in as "manager1"
    And I follow "Site administration"
    Then "Certificates" "text" should not exist in the "region-main" "region"
    And I should not see "Manage certificate templates"
    And I should not see "Verify certificates"
    And I should not see "Add certificate template"
    And I should not see "Certificate images"
    And I log out
