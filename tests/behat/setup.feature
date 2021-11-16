@mod @mod_peerreview

Feature: Add and configure an instance of the Peer review module
  @javascript
  Scenario: setup a peer review ready for student submissions
  Background:
    Given the following "users" exist:
        | username | firstname | lastname | email                |
        | teacher1 | user1     | teacher1 | t1@example.com       |
        | student1 | user1     | student1 | student1@example.com |
        | student2 | user2     | student2 | student2@example.com |
        | student3 | user3     | student3 | studen31@example.com |
        | student4 | user4     | student4 | studen41@example.com |
        | student5 | user5     | student5 | studen51@example.com |

    And the following "courses" exist:
        | fullname | shortname | category |
        | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
        | student2 | C1     | student        |
        | student3 | C1     | student        |
        | student4 | C1     | student        |
        | student5 | C1     | student        |

    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer review" to section "1" and I fill the form with:
        | Peer review name  | P1             |
        | Description       | P1 Description |
        | Submission format | Online text    |
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

    And I log out

# submissions
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I set the field "Submission" to "student1 submission"
    And I press "Submit"
    And I press "Yes"
    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I set the field "Submission" to "student1 submission"
    And I press "Submit"
    And I press "Yes"
    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I set the field "Submission" to "student1 submission"
    And I press "Submit"
    And I press "Yes"
    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I set the field "Submission" to "student1 submission"
    And I press "Submit"
    And I press "Yes"
    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student5"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I set the field "Submission" to "student1 submission"
    And I press "Submit"
    And I press "Yes"
    And I press "Continue"

# Reviews`

    And I click on "Show submission to continue" "link"
    And I press "Continue"

    And I click on "Review text 1" "checkbox"
    And I click on "Review text 2" "checkbox"
    And I click on "Review text 3" "checkbox"

    And I set the field "comment" to "Excellent work5"
    And I press "Save Review"

    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I click on "Show submission to continue" "link"
    And I press "Continue"

    And I click on "Review text 1" "checkbox"
    And I click on "Review text 2" "checkbox"
    And I click on "Review text 3" "checkbox"

    And I set the field "comment" to "Excellent work4"
    And I press "Save Review"

    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I click on "Show submission to continue" "link"
    And I press "Continue"

    And I click on "Review text 1" "checkbox"
    And I click on "Review text 2" "checkbox"
    And I click on "Review text 3" "checkbox"

    And I set the field "comment" to "Excellent work3"
    And I press "Save Review"

    #xxx
    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I click on "Show submission to continue" "link"
    And I press "Continue"

    And I click on "Review text 1" "checkbox"
    And I click on "Review text 2" "checkbox"
    And I click on "Review text 3" "checkbox"

    And I set the field "comment" to "Excellent work2"
    And I press "Save Review"

    And I am on "Course 1" course homepage
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "P1"
    And I click on "Show submission to continue" "link"
    And I press "Continue"

    And I click on "Review text 1" "checkbox"
    And I click on "Review text 2" "checkbox"
    And I click on "Review text 3" "checkbox"

    And I set the field "comment" to "Excellent work1"
    And I press "Save Review"
