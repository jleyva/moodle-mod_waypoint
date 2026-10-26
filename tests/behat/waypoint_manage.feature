@mod @mod_waypoint @javascript
Feature: Teachers manage the waypoint list through the REST API
  In order to describe a piece of work
  As a teacher
  I need to add and remove waypoints

  Each of these scenarios drives a REST call from the React UI: adding sends a
  POST, deleting sends a DELETE, and both come back with the whole activity
  state. Reloading the page afterwards is what proves the change reached the
  database rather than only the DOM.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name       | intro                     |
      | waypoint | C1     | wp1      | Checklist  | Work through these as you go. |
    And the following "mod_waypoint > items" exist:
      | waypoint  | title                     |
      | Checklist | Read the assignment brief |
      | Checklist | Draft an outline          |
      | Checklist | Submit for review         |

  Scenario: Adding a waypoint sends a POST and the new item persists
    Given I am on the "Checklist" "waypoint activity" page logged in as "teacher1"
    And I should see "0 of 3"
    When I set the field "Add waypoint" to "Celebrate"
    And I click on "Add waypoint" "button"
    Then I should see "Celebrate"
    And I should see "0 of 4"
    And I reload the page
    And I should see "Celebrate"
    And I should see "0 of 4"

  Scenario: Deleting a waypoint sends a DELETE and the item stays gone
    Given I am on the "Checklist" "waypoint activity" page logged in as "teacher1"
    And I should see "0 of 3"
    And I should see "Draft an outline"
    When I click on "Delete waypoint" "button" in the "Draft an outline" "list_item"
    Then I should not see "Draft an outline"
    And I should see "0 of 2"
    And I should see "Read the assignment brief"
    And I should see "Submit for review"
    And I reload the page
    And I should not see "Draft an outline"
    And I should see "0 of 2"

  Scenario: Deleting a ticked waypoint recalculates everyone's progress
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    And I click on "Draft an outline" "checkbox"
    And I should see "1 of 3"
    And I log out
    When I am on the "Checklist" "waypoint activity" page logged in as "teacher1"
    And I click on "Delete waypoint" "button" in the "Draft an outline" "list_item"
    And I should see "0 of 2"
    And I log out
    And I am on the "Checklist" "waypoint activity" page logged in as "student1"
    Then I should see "0 of 2"
    And I should not see "Draft an outline"

  Scenario: An empty title is not accepted
    Given I am on the "Checklist" "waypoint activity" page logged in as "teacher1"
    When I set the field "Add waypoint" to " "
    Then the "Add waypoint" "button" should be disabled
    And I should see "0 of 3"
