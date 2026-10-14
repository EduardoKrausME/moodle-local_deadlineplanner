@local @local_deadlineplanner
Feature: Deadline planner calendar and proposals
  In order to avoid invisible cross-course workload disclosure
  As a course teacher
  I need a restricted course dashboard and an approval workflow

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Academic planning | PLANNER101 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | plannerteacher | Planning | Teacher | plannerteacher@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | plannerteacher | PLANNER101 | editingteacher |

  @javascript
  Scenario: Course dashboard does not expose private student data
    Given I log in as "plannerteacher"
    And I am on the "Academic planning" course page
    When I follow "Academic deadline planner"
    Then I should see "Scheduled academic deadlines"
    And I should not see "Other course grades"

  @javascript
  Scenario: Proposal form requires a supported dated activity
    Given I log in as "plannerteacher"
    And I am on the "Academic planning" course page
    When I follow "Academic deadline planner"
    Then I should see "No eligible deadlines were found"
    And I should not see "Simulate without changing Moodle"

  @javascript
  Scenario: Simulating and approving a proposal does not update the activity
    Given the following "activities" exist:
      | activity | name | course | duedate |
      | assign | Project A | PLANNER101 | 1893456000 |
    And I log in as "admin"
    And I am on the "Academic planning" course page
    When I follow "Academic deadline planner"
    And I set the field "Proposed date" to "2030-01-03T12:00"
    And I press "Simulate without changing Moodle"
    Then I should see "Simulation saved"
    And I should see "Approve proposal"
    When I press "Approve proposal"
    Then I should see "Proposal approved"
    And I should see "Open activity editor"
