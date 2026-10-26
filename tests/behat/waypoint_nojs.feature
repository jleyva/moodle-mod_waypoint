@mod @mod_waypoint
Feature: The waypoint list is readable without JavaScript
  In order to read my waypoints in any browser
  As a student
  I need the list and my progress to render server-side

  Deliberately untagged with @javascript. React never mounts, so what renders is
  the fallback markup inside the {{#react}} helper in mod_waypoint/view. The
  list and the progress are readable; nothing is interactive.

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

  Scenario: The waypoints and the progress render without JavaScript
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    Then I should see "Your progress"
    And I should see "0 of 3"
    And I should see "Read the assignment brief"
    And I should see "Draft an outline"
    And I should see "Submit for review"

  Scenario: The interactive controls are absent without JavaScript
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    Then "Hide completed" "checkbox" should not exist
    And "Add waypoint" "button" should not exist

  Scenario: An empty activity says so rather than rendering nothing
    Given the following "activities" exist:
      | activity | course | idnumber | name  |
      | waypoint | C1     | wp2      | Empty |
    When I am on the "Empty" "waypoint activity" page logged in as "student1"
    Then I should see "No waypoints yet."
