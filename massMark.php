<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Peer Review submission grade release page
 *
 * @package    contrib
 * @subpackage assignment_progress
 * @copyright  2010 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $CFG,$DB,$OUTPUT,$PAGE;
require_once($CFG->dirroot."/mod/peerreview/locallib.php");
require_once($CFG->libdir.'/tablelib.php');
// Get course ID and assignment ID
$id     = optional_param('id', 0, PARAM_INT);          // Course module ID
$peerreviewid      = optional_param('peerreviewid', 0, PARAM_INT);           // peerreviewID

if ($id) {
	$cm = get_coursemodule_from_id('peerreview', $id, 0, false, MUST_EXIST);
	$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	
} else {
    $peerreview = $DB->get_record('peerreview', array('id'=>$peerreviewid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerreview', $peerreview->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $id = $cm->id;
}
$context = context_module::instance($cm->id);
if (! $peerreview = $DB->get_record("peerreview", array('id' => $peerreviewid))) {
    print_error('invalidid', 'peerreview');
}

// Check user is logged in and capable of submitting
require_login($course, false, $cm);
require_capability('mod/peerreview:grade', $context);

/// Load up the required assignment code
require('assignment.class.php');
$assignmentclass = 'assignment_peerreview';
$assignmentinstance = new $assignmentclass($cm->id, $peerreview, $cm, $course);

// Sets all unset calculatable marks

$attributes = array('peerreviewid' => $peerreview->id, 'id' => $cm->id);
$PAGE->set_url('/mod/peerreview/massMark.php', $attributes);
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($peerreview->name));

echo '<div style="text-align:center;margin:0 0 10px 0;">';

// Get submissions with reviews so that grades can be set
$criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($peerreview->id),null,'ordernumber, value');
if($criteriaList && count($criteriaList)>0) {
    $criteriaList = array_values($criteriaList);
    $query = 'SELECT a.userid, SUM(r.completed) as reviewscomplete, a.timemarked, a.timecreated, a.id, u.firstname, u.lastname '.
        'FROM '.$CFG->prefix.'peerreview_submissions a, '.$CFG->prefix.'peerreview_review r, '.$CFG->prefix.'user u '.
        'WHERE a.peerreview='.$peerreview->id.' '.
        'AND r.peerreview='.$peerreview->id.' '.
        'AND r.teacherreview=0 '.
        'AND a.userid=r.reviewer '.
        'AND a.userid=u.id '.
        'GROUP BY a.userid, a.timemarked, a.timecreated, a.id, u.firstname, u.lastname '.
        'ORDER BY a.timecreated ASC, a.id ASC';
    $submissions = $DB->get_records_sql($query);

    if($submissions && count($submissions)>0) {
        $submissions = array_values($submissions);
        $numberOfSubmissions = count($submissions);

        // Setup submissions table
        $table = new flexible_table('mod-assignment-peerreview-marks');
        $table->define_baseurl(new moodle_url('/mod/peerreview/massMark.php'));
        $tablecolumns = array('name', 'status', 'grade', 'email');
        $table->define_columns($tablecolumns);
        $tableheaders = array(
            get_string('fullname'),
            get_string('status'),
            get_string('grade'),
            get_string('emailstatus', 'peerreview')
        );
        $table->define_headers($tableheaders);
        $table->sortable(false);
        $table->pageable(false);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->set_attribute('class', 'generalbox');
        $table->column_style_all('padding', '5px 10px');
        $table->column_style_all('text-align','left');
        $table->setup();
        $continueurl = $CFG->wwwroot.'/mod/peerreview/view.php?id='. $id . '&peerreviewid='. $peerreview->id;
        // Build table of submissions as they are marked
        for($i=0; $i<$numberOfSubmissions; $i++) {
            $name = $submissions[$i]->firstname . ' ' . $submissions[$i]->lastname;
            if ($submissions[$i]->timemarked==0) {

                $reviews = get_reviews_of_student($peerreview->id, $submissions[$i]->userid);
                $grade = get_marks($reviews,$criteriaList,$submissions[$i]->reviewscomplete,$peerreview->reviewreward);
                if($grade!='???') {
                    if($submission = peerreview_set_mark($peerreview, $course, $submissions[$i]->userid,$grade, $USER->id, $continueurl)) {
                        $status = get_string('gradeset','peerreview');
                        $gradeToDisplay = $grade;
                        $email = ($submission->mailed?get_string('emailsent','peerreview'):get_string('emailnotsent','peerreview'));
                    }
                    else {
                        $status = get_string('unabletoset','peerreview');
                        $gradeToDisplay = get_string('unabletowritetodb','peerreview');
                        $email = get_string('emailnotsent','peerreview');
                    }
                }
                else {
                    $status = get_string('unabletoset','peerreview');
                    $gradeToDisplay = get_string('moderationrequired','peerreview');
                    $email = get_string('emailnotsent','peerreview');
                }
            }
            else {
                $status = get_string('previouslyset','peerreview');
                $gradeToDisplay = get_string('nochange','peerreview');
                $email = get_string('emailnotsent','peerreview').'</td>';
            }
            $row = array($name, $status, $gradeToDisplay, $email);
            $table->add_data($row);
        }
        $table->print_html();
        echo $OUTPUT->spacer(array('height'=>10, 'width'=>10));
    }
    else {
        echo $OUTPUT->notification(get_string('nocompletesubmissions','peerreview'));
    }
}
else {
    echo $OUTPUT->notification(get_string('nocriteriaset','peerreview'));
}

echo $OUTPUT->continue_button($CFG->wwwroot.'/mod/peerreview/submissions.php?id='.$cm->id . '&peerreviewid='. $peerreview->id);
echo '</div>';
echo $OUTPUT->footer();