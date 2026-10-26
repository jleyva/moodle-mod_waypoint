@mod @mod_waypoint @javascript
Feature: Students tick off waypoints and see their progress
  In order to keep track of a piece of work
  As a student
  I need to tick waypoints off and have that remembered

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

  # One journey rather than three scenarios: what a student sees, ticking a
  # waypoint, and the tick surviving a reload. Splitting these meant paying for
  # a browser restart and a database reset to re-reach the same state, since
  # core restarts the Mink session before every @javascript scenario.
  Scenario: A student ticks a waypoint and it is remembered
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    Then I should see "0 of 3"
    And "Add waypoint" "button" should not exist
    And "Delete waypoint" "button" should not exist
    When I click on "Read the assignment brief" "checkbox"
    Then I should see "1 of 3"
    And I should see "Done" in the "Read the assignment brief" "list_item"
    When I reload the page
    Then I should see "1 of 3"
    And the field "Read the assignment brief" matches value "1"

  Scenario: Unticking a waypoint takes the progress away again
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    When I click on "Read the assignment brief" "checkbox"
    And I should see "1 of 3"
    And I click on "Read the assignment brief" "checkbox"
    Then I should see "0 of 3"
    And I should not see "Done"

  # The design system Switch renders a visually-hidden input (1x1px, clipped) with
  # the visible control in a sibling <label for=...>. Both "I click on ... checkbox"
  # and "I set the field ... to" end up calling click() on that hidden input, which
  # Selenium refuses. Click the label text, which is what a user actually clicks.
  Scenario: Completed waypoints can be hidden from the list
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    When I click on "Read the assignment brief" "checkbox"
    And I should see "1 of 3"
    And I click on "Hide completed" "text"
    Then I should not see "Read the assignment brief"
    And I should see "Draft an outline"
    And I should see "1 of 3"

  Scenario: One student's progress is their own
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    And I click on "Read the assignment brief" "checkbox"
    And I should see "1 of 3"
    And I log out
    When I am on the "Checklist" "waypoint activity" page logged in as "teacher1"
    Then I should see "0 of 3"

