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
 * Peer review moderation review page
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2012 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/tablelib.php');

$peerreviewid = required_param('peerreviewid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
// $offset = required_param('offset', PARAM_INT);//offset for where to start looking for student.

$cm = get_coursemodule_from_instance('peerreview', $peerreviewid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
if (! $peerreview = $DB->get_record("peerreview", array('id' => $peerreviewid))) {
    print_error('invalidid', 'peerreview');
}

// Check user is logged in and capable of submitting
require_login($course, false, $cm);
require_capability('mod/peerreview:grade', $context);

// Set up the page
$attributes = array('peerreviewid' => $peerreview->id, 'id' => $cm->id, 'userid' => $userid);
$PAGE->set_url('/mod/peerreview/mark.php', $attributes);
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();

if (!$user = $DB->get_record('user', array('id'=>$userid))) {
    print_error('No such user!');
}

if (!$submission = get_submission($peerreview->id, $user->id)) {
    $submission = prepare_new_submission($peerreview->id, $user->id);
}
if ($submission->timemodified > $submission->timemarked) {
    $subtype = 'assignmentnew';
} else {
    $subtype = 'assignmentold';
}

 // Get the criteria
$criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($peerreview->id),'ordernumber');
$numberOfCriteria = 0;
if(is_array($criteriaList)) {
    $criteriaList = array_values($criteriaList);
    $numberOfCriteria = count($criteriaList);
}

$reviews = get_reviews_of_student($peerreviewid, $user->id);
$numberOfReviewsOfThisStudent = 0;
if(is_array($reviews)) {
    $numberOfReviewsOfThisStudent = count($reviews);
}
$reviewStats = get_review_statistics($peerreview);

// TODO fix grades
// $grading_info = grade_get_grades($course->id, 'mod', 'peerreview', $peerreview->id, array($user->id));
// $disabled = $grading_info->items[0]->grades[$userid]->locked || $grading_info->items[0]->grades[$userid]->overridden;

// /// Get all ppl that can submit assignments
// $currentgroup = groups_get_activity_group($cm);
// if ($users = get_users_by_capability($context, 'mod/assignment:submit', 'u.id', '', '', '', $currentgroup, '', false)) {
//     $users = array_keys($users);
// }

// // if groupmembersonly used, remove users who are not in any group
// if ($users and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
//     if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
//         $users = array_intersect($users, array_keys($groupingusers));
//     }
// }

// $nextid = 0;

// if ($users && $offset>=0) {
//     $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt,
//                       s.id AS submissionid, s.grade, s.submissioncomment,
//                       s.timecreated as timecreated, s.timemarked as timemarked ';
//     $sql = 'FROM {user} u '.
//            'LEFT JOIN {assignment_submissions} s ON u.id = s.userid
//             AND s.assignment = '.$peerreview->id.' '.
//            'WHERE u.id IN ('.implode(',', $users).') ';

//     if ($sort = flexible_table::get_sort_for_table('mod-assignment-peerreview-submissions')) {
//         $sort = ' ORDER BY '.$sort;
//         $sort = str_replace('timecreated', 'COALESCE(s.timecreated,2147483647)', $sort);
//     }
//     else {
//         $sort = 'ORDER BY COALESCE(s.timecreated,2147483647) ASC, submissionid ASC, u.lastname ASC';
//     }

//     // Find the next user who has submitted
//     if (($auser = $DB->get_records_sql($select.$sql.$sort, null, $offset+1)) !== false) {

//         $nextuser = array_shift($auser);
//         $offset++;

//         while($nextuser && !$nextuser->timecreated) {
//             $nextuser = array_shift($auser);
//             $offset++;
//         }

//         // Calculate user status
//         if($nextuser && $nextuser->timecreated) {
//             $nextuser->status = ($nextuser->timemarked > 0);
//             $nextid = $nextuser->id;
//         }
//     }
// }

//print_header(get_string('feedback', 'assignment').':'.fullname($user, true).':'.format_string($peerreview->name));

// Start of student info row
echo $OUTPUT->container_start('userdetails');
echo $OUTPUT->container_start('userpicture');
echo $OUTPUT->user_picture($user, array('course'=>$course->id));
echo $OUTPUT->container_end();
echo $OUTPUT->container_start('submissiondetails');
echo $OUTPUT->container(fullname($user, true), 'fullname');
if ($submission->timemodified) {
    echo $OUTPUT->container_start('submissiontime');
    echo get_string('submitted','peerreview').': '.userdate($submission->timecreated).
         display_lateness($submission->timecreated, $peerreview->duedate);
    echo $OUTPUT->container_end();
}
echo $OUTPUT->container_start('moderations');
$moderationCountSQL = 'SELECT count(r.id) FROM {peerreview} p, {peerreview_review} r WHERE p.course='.$course->id.' AND p.id=r.peerreview AND r.teacherreview=1 AND r.reviewee=\''.$user->id.'\'';
$moderationCount = $DB->count_records_sql($moderationCountSQL);
$moderationtarget = get_user_preferences('assignment_moderationtarget', 0);
echo get_string('moderations','peerreview').': ';
if ($moderationCount < $moderationtarget) {
    echo $OUTPUT->tag('span', $moderationCount.' ('.get_string('moderationtargetnotmet','peerreview'), array('class'=>'errorStatus'));
}
else {
    echo $moderationCount;
}
echo $OUTPUT->container_end();
echo $OUTPUT->container_start('status');
echo get_string('status').': ';
$statusCode = get_status($reviews, $numberOfCriteria);
print_status($statusCode);
if ($statusCode <= 3) {
    echo ' ('.get_string('moderationrequired','peerreview').')';
}
echo $OUTPUT->container_end();

// Show submitted document
if (isset($peerreview->submissionformat) && $peerreview->submissionformat == SUBMIT_DOCUMENT) {
    echo submission_link($peerreview,$submission->id,$context->id,false,$cm->id,true);
}
// Show submission for online text
if (isset($peerreview->submissionformat) && $peerreview->submissionformat == ONLINE_TEXT) {
    echo $OUTPUT->box(format_text(stripslashes($submission->onlinetext), PARAM_CLEAN),'generalbox', 'onlineTextSubmission');
}
echo $OUTPUT->container_end();
echo $OUTPUT->container_end();

// Marking form
echo HTML_WRITER::start_tag('form', array('action' => 'submissions.php', 'method' => 'post', 'id' => 'peerreviewform'));
echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'peerreviewid', 'value' => $peerreviewid));
echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'review', 'value' => $numberOfReviewsOfThisStudent+1));
echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'userid', 'value' => $userid));
echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'timeLoaded', 'value' => time()));
echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

///Start of marking row
$table = new html_table();
$table->attributes = array('class' => 'criteriaTable');
$table->colclasses = array('labelColumn');
$rowattributes = array('class'=>'criteriaDisplayRow');

echo '<table width="99%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">';
echo '<tr>';
for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
    echo '<td class="criteriaCheckboxColumn" style="text-align:center;vertical-align:middle;background:'.$REVIEW_COLOURS[$i%$NUMBER_OF_COLOURS].'">';
    $timeTakenReviewing = $reviews[$i]->timecompleted - $reviews[$i]->timedownloaded;
    $commentLength = strlen($reviews[$i]->reviewcomment);
    if($reviews[$i]->flagged) {
        echo '<img src="'.$CFG->wwwroot.'/mod/peerreview/pix/flagRed.gif">';
    }
    else if(
        $reviews[$i]->teacherreview==0 &&
        ($timeTakenReviewing < MINIMAL_REVIEW_TIME || $timeTakenReviewing < $reviewStats->reviewTimeOutlierLowerBoundary) ||
        ($commentLength < MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength < $reviewStats->commentLengthOutlierLowerBoundary)
    ) {
        echo '<span style="color:#ff0000;font-weight:bold">?</span> ';
    }
    echo '</td>';
}
echo '<td colspan="2" class="reviewStatus"><span style="padding-left:5px;font-weight:bold;">';
echo get_string('newreview','peerreview');
echo '</span>&nbsp;(';
echo $OUTPUT->help_icon('whatdostudentssee', 'peerreview', true);
echo ')</td></tr>';
$options = new stdClass();
$options->para = false;
for($i=0; $i<$numberOfCriteria; $i++) {
    echo '<tr class="criteriaDisplayRow">';
    for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
        $disabled = $reviews[$j]->teacherreview==1 ? 'disabled' : '';
        echo '<td class="criteriaCheckboxColumn" style="background:'.$REVIEW_COLOURS[$j%$NUMBER_OF_COLOURS].'"><input type="checkbox" name="checked'.$reviews[$j]->review.'crit'.$i.'" '.
            ($reviews[$j]->{'checked'.$i}==1?' checked':'').' onchange="M.peerreview.allowSavePrev();" ' . $disabled . '/></td>';
    }
    echo '<td class="criteriaCheckboxColumn"><input type="checkbox" name="newChecked'.$i.'" id="newChecked'.$i.'" onchange="M.peerreview.allowSaveNew();" /></td>';
    echo '<td class="criteriaDisplayColumn"><label for="newChecked'.$i.'">'.
        format_text($criteriaList[$i]->textshownatreview!=''?$criteriaList[$i]->textshownatreview:$criteriaList[$i]->textshownwithinstructions,FORMAT_MOODLE,$options).'</label></td>';
    echo '</tr>';
}
echo '<tr>';
for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
    echo '<td class="criteriaCheckboxColumn" style="background:'.$REVIEW_COLOURS[$i%$NUMBER_OF_COLOURS].';">&nbsp;</td>';

}
echo '<td colspan="2" style="padding:5px;">';
echo '<table width="100%" cellspacing="3">';

echo '</tr>';
echo '<td width="50%">'.get_string('comment','peerreview');
echo '</td>';
echo '<td width="50%">'.get_string('savedcomments','peerreview');
echo $OUTPUT->help_icon('savedcomments', 'peerreview', false);
echo '<button style="height:25px" id="savedcomments" name="submit" value="savedcomments">' . get_string('savecomments','peerreview') . '</button>';
echo '</td>';
echo '</tr>';

echo '</tr>';
echo '<td width="50%"><textarea name="newComment" rows="10" style="width:99%;" onkeypress="M.peerreview.allowSaveNew();"></textarea>';

echo '</td>';
echo '<td  width="50%"><textarea rows="10" style="width:99%; " name="savedcomments" >'.($peerreview->savedcomments?format_string(stripslashes($peerreview->savedcomments)):'').'</textarea>';
echo '</td>';
echo '</tr>';
echo '</table>';

/*

echo '<tr>';
echo '<td   width="50%">'.get_string('comment','peerreview').
    '<br /><br /><textarea name="newComment" rows="10" style="width:99%;" onkeypress="M.peerreview.allowSaveNew();"></textarea></td>';
echo '<td >'.get_string('savedcomments','peerreview').' ';
echo $OUTPUT->help_icon('savedcomments', 'peerreview', false);
echo '<button style="height:25px" id="savedcomments" name="submit" value="savedcomments">' . get_string('savecomments','peerreview') . '</button>';
 //echo '(<a href="#null" onclick="savedComments()" />' . get_string('savecomments','peerreview'). '</a>)';
echo '<br /><br /><textarea rows="10" style="width:99%; " name="savedcomments" >'.($peerreview->savedcomments?format_string(stripslashes($peerreview->savedcomments)):'').'</textarea></td>';
echo '</tr>';
echo '</table>';
echo '</td>';
echo '</tr>';
*/
$studentCount = 1;
for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
    echo '<tr>';
    for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
        echo '<td class="criteriaCheckboxColumn" style="background:'.
            ($j>$numberOfReviewsOfThisStudent-$i-1?$REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$NUMBER_OF_COLOURS]:$REVIEW_COLOURS[$j%$NUMBER_OF_COLOURS]).';">&nbsp;</td>';

    }
    echo '<td colspan="2" class="reviewCommentRow" style="background:'.$REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$NUMBER_OF_COLOURS].';">';

    echo '<table width="99%" cellpadding="0" cellspacing="0" border="0" class="reviewCommentTable">';
    echo '<tr class="reviewDetailsRow">';
    echo '<td><em>'.get_string('conductedby','peerreview').': '.$reviews[$numberOfReviewsOfThisStudent-$i-1]->firstname.' '.
        $reviews[$numberOfReviewsOfThisStudent-$i-1]->lastname.' ('.
        ($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview==1?get_string('defaultcourseteacher'):get_string('defaultcoursestudent')).')</em></td>';
    echo '<td width="50%"><em>';
    if($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview==0) {
        $timeTakenReviewing = $reviews[$numberOfReviewsOfThisStudent-$i-1]->timecompleted - $reviews[$numberOfReviewsOfThisStudent-$i-1]->timedownloaded;
        echo format_time($timeTakenReviewing);
        if($timeTakenReviewing < MINIMAL_REVIEW_TIME || $timeTakenReviewing < $reviewStats->reviewTimeOutlierLowerBoundary) {
            echo ' <span style="color:#ff0000;font-weight:bold;">'.get_string('quick','peerreview').'!</span>';
        }
        echo ', ';
        $commentLength = strlen($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment);
        echo $commentLength.' '.get_string('characters','peerreview');
        if($commentLength < MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength < $reviewStats->commentLengthOutlierLowerBoundary) {
            echo ' <span style="color:#ff0000;font-weight:bold;">'.get_string('short','peerreview').'!</span>';
        }
        echo ', ';
    }
    echo userdate($reviews[$numberOfReviewsOfThisStudent-$i-1]->timemodified,get_string('strftimedatetime')).'</em></td>';
    echo '</tr>';
    echo '<tr><td  width="100%" colspan="3"><textarea name="preExistingComment'.($reviews[$numberOfReviewsOfThisStudent-$i-1]->review).
        '" rows="3" class="commentTextBox" onkeypress="M.peerreview.allowSavePrev()" style="width:100%; background:'.$REVIEW_COMMENT_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%count($REVIEW_COMMENT_COLOURS)].';">'.
        format_string(stripslashes($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment)).'</textarea></td></tr>';

    if($reviews[$numberOfReviewsOfThisStudent-$i-1]->flagged==1) {
        echo '<tr class="reviewDetailsRow" style="color:#ff0000;"><td colspan="2"><em>'.get_string('flagged','peerreview').'&nbsp;<img src="'.$CFG->wwwroot.
            '/mod/peerreview/pix/flagRed.gif"></em></td></tr>';
    }
    echo '</table>';
    echo '</td>';
    echo '</tr>';
}
echo '</table>';

$lastmailinfo = get_user_preferences('assignment_mailinfo', 1) ? 'checked="checked"' : '';

///Print Buttons in Single View
echo $OUTPUT->container_start('buttons');
echo '<div class="">';
// echo '<input type="hidden" name="mailinfo" value="0" />';
// echo '<input type="checkbox" id="mailinfo" name="mailinfo" value="1" '.$lastmailinfo.' /><label for="mailinfo">'.get_string('enableemailnotification','peerreview').'</label>';
echo '<button name="cancel" value="cancel">' . get_string('cancel') . '</button>';
if($numberOfReviewsOfThisStudent>0) {
    echo '<button id="savepreexistingonly" name="submit" value="savepreexistingonly">' . get_string('savepreexistingonly','peerreview') . '</button>';
}
echo '<button id="savenew" name="submit" value="savenewonly">' . get_string('savenew','peerreview') . '</button>';
if($numberOfReviewsOfThisStudent>0) {
    echo '<button id="savenewandold" name="submit" value="savenewandold">' . get_string('savenewandold','peerreview') . '</button>';
}



echo $OUTPUT->help_icon('moderationbuttons', 'peerreview', false);
echo '</div>';
echo $OUTPUT->container_end();
// $PAGE->requires->js_init_call('M.peerreview.initModerationButtons',null,false,$this->jsmodule);

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
