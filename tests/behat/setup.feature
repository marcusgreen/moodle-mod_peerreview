@mod @mod_peerreview

Feature: Add and configure an instance of the Peer review module
  @javascript
  Scenario: setup a peer review ready for student submissions
  Background:
    Given the following "users" exist:
        | username | firstname | lastname | email                |
        | teacher1 | user1     | teacher1 | t1@example.com       |
        | student1 | user1     | student1 | student1@example.com |
    And the following "courses" exist:
        | fullname | shortname | category |
        | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer review" to section "1" and I fill the form with:
        | Peer review name | P1             |
        | Description      | P1 Description |
    And I follow "P1"
    When I press "Save and display"
    Then I should see "You must now create criteria"

    And I set the field "id_criterionDescription_0" to "Criteria description 1"
    And I set the field "id_criterionReview_0" to "Review text 1"
    And I set the field "id_value_0" to "25"

    And I set the field "id_criterionDescription_1" to "Criteria description 2"
    And I set the field "id_criterionReview_1" to "Review text 2"
    And I set the field "id_value_1" to "25"

    And I set the field "id_criterionDescription_2" to "Criteria description 3"
    And I set the field "id_criterionReview_2" to "Review text 3"
    And I set the field "id_value_2" to "30"
    When I press "Save and display"

    Then I should see "Criteria shown to students"
    And I pause
