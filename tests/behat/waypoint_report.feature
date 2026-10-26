@mod @mod_waypoint @javascript
Feature: Teachers see everyone's progress on the routed report page
  In order to see how a class is getting on
  As a teacher
  I need a report listing each student's progress

  The report is served by the routing subsystem rather than by a file on disk,
  so its URL is resolved through \core\router\util::get_path_for_callable()
  in behat_mod_waypoint::resolve_page_instance_url(). That keeps these steps
  working whether or not the site has $CFG->routerconfigured set.

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

  Scenario: The report is reachable from the activity navigation
    Given I am on the "Checklist" "waypoint activity" page logged in as "teacher1"
    When I click on "Waypoint progress" "link"
    Then I should see "Waypoint progress: Checklist"
    And I should see "Student 1"

  Scenario: The report shows each student's progress
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    And I click on "Read the assignment brief" "checkbox"
    And I should see "1 of 3"
    And I log out
    When I am on the "Checklist" "mod_waypoint > Report" page logged in as "teacher1"
    Then I should see "Student 1"
    And I should see "1 of 3"

  Scenario: Students cannot reach the report
    Given I am on the "Checklist" "waypoint activity" page logged in as "student1"
    Then "Waypoint progress" "link" should not exist
