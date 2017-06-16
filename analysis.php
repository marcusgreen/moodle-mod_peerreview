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
 * Peer review submissions page
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2012 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

$cmid = required_param('id', PARAM_INT);
$peerreviewid = required_param('peerreviewid', PARAM_INT);
$cm = get_coursemodule_from_id('peerreview', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
if (! $peerreview = $DB->get_record("peerreview", array('id' => $peerreviewid))) {
    print_error('invalidid', 'peerreview');
}

// Check user is logged in and capable of submitting
require_login($course, false, $cm);
require_capability('mod/peerreview:grade', $context);

// Set up the page
$attributes = array('peerreviewid' => $peerreview->id, 'id' => $cm->id);
$baseurl = '/mod/peerreview/analysis.php';
$PAGE->set_url($baseurl, $attributes);
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo peerreview_tabs($cm->id, $peerreview->id, 'analysis');

// Require JS needed for page.
$jsmodule = array(
    'name' => 'peerreview',
    'fullpath' => '/mod/peerreview/module.js',
    'requires' => array(),
    'strings' => array(),
);
$PAGE->requires->js_init_call(null, null, false, $jsmodule);


// Log this view
// TODO fix logging
//add_to_log($course->id, 'peerreview', 'view submissions', 'submissions.php?peerreviewid='.$peerreview->id, $peerreview->id, $cm->id);

// Update preferences
if (optional_param('updatepref', 0, PARAM_INT)) {
    $perpage = optional_param('perpage', 20, PARAM_INT);
    $perpage = ($perpage <= 0) ? 20 : $perpage ;
    set_user_preference('peerreview_perpage', $perpage);
    $moderationtarget = optional_param('moderationtarget', 0, PARAM_INT);
    $moderationtarget = ($moderationtarget <= 0) ? 0 : $moderationtarget ;
    set_user_preference('peerreview_moderationtarget', $moderationtarget);
    $quickgrade = optional_param('quickgrade', 0, PARAM_BOOL);
    set_user_preference('peerreview_quickgrade', $quickgrade);
}
if (optional_param('submit', 0, PARAM_TEXT)) {
    save_teacher_submission($peerreviewid);
}
$perpage          = get_user_preferences('peerreview_perpage', 20);
$moderationtarget = get_user_preferences('peerreview_moderationtarget', 0);
$quickgrade       = get_user_preferences('peerreview_quickgrade', 0);
$page             = optional_param('page', 0, PARAM_INT);


peerreview_print_analysis($peerreview, $course, $cm, $context, $baseurl, $attributes);
die;

$reviewStats = get_review_statistics($peerreview);

// Print optional message
if (!empty($message)) {
    echo $message;   // display messages here if any
}

// Help on review process
echo html_writer::start_tag('div', array('style'=>'text-align:center;margin:-10px 0 10px 0;'));
echo $OUTPUT->help_icon('reviewallocation', 'peerreview', true);
echo html_writer::end_tag('div');

// Get all users who are allowed to submit assignments
if ($users = get_users_by_capability($context, 'mod/peerreview:submit', 'u.id')) {
    $users = array_keys($users);
}

// TODO fix groupings
// If groupmembersonly is used, remove users who are not in any group.
if ($users and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
    if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
        $users = array_intersect($users, array_keys($groupingusers));
    }
}

// Filter out teachers
// if ($users && $teachers = get_users_by_capability($context, 'mod/peerreview:grade', 'u.id')) {
//     $users = array_diff($users, array_keys($teachers));
// }

// Warn if class is too small
if(count($users) < 5) {
    echo $OUTPUT->notification(get_string('numberofstudentswarning','peerreview'));
}

// Show due date and time remaining
echo get_string('duedate', 'peerreview') . ' ' . userdate($peerreview->duedate) . ' ' . display_remainingtime($peerreview->duedate);
// Create the table to be shown
require_once($CFG->libdir.'/tablelib.php');
$table = new flexible_table('mod-peerreview-submissions');
$tablecolumns = array('picture', 'fullname', 'timecreated', 'reviews', 'moderations', 'status', 'seedoreviews', 'suggestedmark','finalgrade');
$table->define_columns($tablecolumns);
$tableheaders = array('',
    get_string('fullname'),
    get_string('submission','peerreview').$OUTPUT->help_icon('submission','peerreview',false),
    get_string('reviewsbystudent','peerreview').$OUTPUT->help_icon('reviewsbystudent','peerreview',false),
    get_string('moderationstitle','peerreview').$OUTPUT->help_icon('moderationtarget','peerreview',false),
    get_string('status').$OUTPUT->help_icon('status','peerreview',false),
    get_string('seedoreviews','peerreview').$OUTPUT->help_icon('seedoreviews','peerreview',false),
    get_string('suggestedgrade','peerreview').$OUTPUT->help_icon('suggestedgrade','peerreview',false),
    get_string('finalgrade', 'peerreview').$OUTPUT->help_icon('finalgrade','peerreview',false)
);
$table->define_headers($tableheaders);
//$table->define_baseurl($CFG->wwwroot.'/mod/peerreview/submissions.php?id='.$cm->id.'&amp;currentgroup='.$currentgroup);
$table->define_baseurl($PAGE->url);
$table->sortable(true, 'timecreated');
$table->collapsible(false);
// TODO fix initial bars
$table->initialbars(false);
$table->column_suppress('picture');
$table->column_suppress('fullname');
$table->column_class('picture', 'picture');
$table->column_class('fullname', 'fullname');
$table->column_class('timecreated', 'timecreated');
$table->column_class('reviews', 'reviews');
$table->column_class('moderations', 'moderations');
$table->column_class('status', 'status');
$table->column_class('seedoreviews', 'seedoreviews');
$table->column_class('suggestedmark', 'suggestedmark');
$table->column_class('finalgrade', 'finalgrade');
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'attempts');
$table->set_attribute('class', 'submissions');
$table->set_attribute('width', '99%');
$table->set_attribute('align', 'center');
$table->column_style('timecreated','text-align','left');
$table->column_style('fullname','text-align','left');
$table->no_sorting('picture');
$table->no_sorting('reviews');
$table->no_sorting('moderations');
$table->no_sorting('status');
$table->no_sorting('seedoreviews');
$table->no_sorting('suggestedmark');
$table->no_sorting('finalgrade');

$table->setup();

if (empty($users)) {
    echo $OUTPUT->heading(get_string('nosubmitusers','peerreview'));
    return true;
}

// Construct the SQL
if ($where = $table->get_sql_where() && !empty($where)) {
    $where .= ' AND ';
}
else {
    $where = '';
}
$fields = user_picture::fields('u');
$sql = "   SELECT $fields,s.id AS submissionid, s.grade,s.timecreated as timecreated, s.timemarked
             FROM {user} u
        LEFT JOIN {peerreview_submissions} s ON u.id=s.userid AND s.peerreview=$peerreview->id
            WHERE $where u.id IN (".implode(',',$users).")";
if ($sort = $table->get_sql_sort()) {
    $sort = ' ORDER BY '.$sort;
    $sort = str_replace('timecreated', 'COALESCE(s.timecreated,2147483647)', $sort);
}
else {
    $sort = 'ORDER BY COALESCE(s.timecreated,2147483647) ASC, submissionid ASC, u.lastname ASC';
}
$table->pagesize($perpage, count($users));
///offset used to calculate index of student in that particular query, needed for the pop up to know who's next
// $offset = $page * $perpage;

$strupdate = get_string('update');
$strgrade  = get_string('grade');
$grademenu = make_grades_menu($peerreview->grade);

// Get the criteria
$criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($peerreview->id),'ordernumber');
$numberOfCriteria = 0;
if(is_array($criteriaList)) {
    $criteriaList = array_values($criteriaList);
    $numberOfCriteria = count($criteriaList);
}
if (($ausers = $DB->get_records_sql($sql.$sort,null,$table->get_page_start(), $table->get_page_size())) !== false) {
    foreach ($ausers as $auser) {

        // Calculate user status
        $auser->status = $auser->timemarked > 0;
        $picture = $OUTPUT->user_picture($auser, array('course'=>$course->id));
        $url = new moodle_url('/user/view.php', array('id'=>$auser->id, 'course'=>$course->id));
        $studentName = html_writer::link($url, fullname($auser));

        // If submission has been made
        if (!empty($auser->submissionid)) {
            $fileLink = submission_link($peerreview->id,$auser->submissionid,$context->id,true,$cmid,true);
            $timecreated = $fileLink.' '.userdate($auser->timecreated,get_string('strftimeintable','peerreview'));
            $url = new moodle_url('/mod/peerreview/resubmit.php', array('peerreviewid'=>$peerreview->id,'userid'=>$auser->id));
            $timecreated .= html_writer::link($url, '<br />('.get_string('resubmitlabel','peerreview').')');

            // Reviews by student
            $numberOfReviewsByThisStudent = 0;
            $reviews = html_writer::start_tag('div', array('id'=>'re'.$auser->id));
            // TODO get this once for all students
            if($reviewsByThisStudent = $DB->get_records('peerreview_review', array('peerreview'=>$peerreview->id, 'reviewer'=>$auser->id, 'completed'=>'1'))) {
                $numberOfReviewsByThisStudent = count($reviewsByThisStudent);
                $reviewsByThisStudent = array_values($reviewsByThisStudent);

                for($i=0; $i<$numberOfReviewsByThisStudent; $i++) {
                    $params = array(
                        'id'=>'rev'.$reviewsByThisStudent[$i]->id,
                        'class'=>'reviewButton',
                        'onmouseover'=>'M.peerreview.highlight(\'se'.$reviewsByThisStudent[$i]->reviewee.'\', \'#ff9999\');',
                        'onmouseout'=>'M.peerreview.highlight(\'se'.$reviewsByThisStudent[$i]->reviewee.'\', \'transparent\');'
                    );
                    $reviews .= html_writer::start_tag('span', $params);
                    $url = new moodle_url('/mod/peerreview/mark.php', array('peerreviewid'=>$peerreview->id, 'userid'=>$reviewsByThisStudent[$i]->reviewee));
                    $buttonText = ''.($i+1);
                    $timeTakenReviewing = $reviewsByThisStudent[$i]->timecompleted - $reviewsByThisStudent[$i]->timedownloaded;
                    $commentLength = strlen($reviewsByThisStudent[$i]->reviewcomment);
                    if(
                        ($timeTakenReviewing < MINIMAL_REVIEW_TIME || $timeTakenReviewing < $reviewStats->reviewTimeOutlierLowerBoundary) ||
                        ($commentLength < MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength < $reviewStats->commentLengthOutlierLowerBoundary)
                    ) {
                        $buttonText .= '?';
                    }
                    if($reviewsByThisStudent[$i]->flagged) {
                        $buttonText .= 'F';
                    }
                    $options = array('class'=>'peerReviewButton');
                    $reviews .= $OUTPUT->single_button($url, $buttonText, 'get', $options);
                    $reviews .= html_writer::end_tag('span');
                }
            }
            $reviews .= html_writer::end_tag('div');

            // Reviews of student
            // TODO get this once for all students
            $reviewsOfThisStudent = get_reviews_of_student($peerreview->id, $auser->id);
            $numberOfReviewsOfThisStudent = 0;
            $reviewids = '';
            if(is_array($reviewsOfThisStudent)) {
                $numberOfReviewsOfThisStudent = count($reviewsOfThisStudent);
                for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
                    $reviewids .= 'rev'.$reviewsOfThisStudent[$i]->review;
                    if($i < $numberOfReviewsOfThisStudent-1) {
                        $reviewids .= ',';
                    }
                }
            }

            // Status of submission
            // TODO get this once for all students
            $statusCode = get_status($reviewsOfThisStudent,$numberOfCriteria);
            $status = print_status($statusCode, true);

            // Review button
            $params = array(
                'id'=>'se'.$auser->id,
                'class'=>'reviewButton',
                'onmouseover'=>"M.peerreview.highlight('$reviewids', '#ff9999');",
                'onmouseout'=>"M.peerreview.highlight('$reviewids', 'transparent');"
            );
            $seedoreviews = html_writer::start_tag('div', $params);
            $url = new moodle_url('/mod/peerreview/mark.php', array('peerreviewid'=>$peerreview->id, 'userid'=>$auser->id)); //, 'offset'=>$offset));
            $buttontext = get_string('review','peerreview');
            $button = $OUTPUT->single_button($url, $buttontext);
            $seedoreviews .= html_writer::tag('span', $button, array('class'=>'status'.($statusCode<=3?'0':'1')));
            $seedoreviews .= html_writer::end_tag('div');

            // Suggest mark
            $suggestedMarkToDisplay = get_marks($reviewsOfThisStudent,$criteriaList,$numberOfReviewsByThisStudent,$peerreview->reviewreward);
            if($quickgrade) {
                $suggestedmark = html_writer::start_tag('form', array('action'=>$CFG->wwwroot.'/mod/peerreview/setMark.php', 'method'=>'post'));
                $suggestedmark .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'peerreviewid', 'value'=>$peerreview->id));
                $suggestedmark .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'userid', 'value'=>$auser->id));
                $suggestedmark .= html_writer::empty_tag('input', array('type'=>'text', 'size'=>'2', 'name'=>'grade', 'value'=>$suggestedMarkToDisplay));
                $params = array(
                    'type'=>'submit',
                    'name'=>'mark',
                    'value'=>get_string('set','peerreview'),
                    'onclick'=>"if(isNaN(parseInt(document.getElementById('gvalue$auser->id').value))) {alert('".get_string('gradenotanumber','peerreview')."'); return false;}"
                );
                $suggestedmark .= html_writer::empty_tag('input', $params);
                $suggestedmark .= html_writer::end_tag('form');
            }
            else {
                $suggestedmark = $suggestedMarkToDisplay;
            }

            // Final grade
            if ($auser->timemarked > 0) {
                $grade = $auser->grade;
            }
            else {
                $grade = get_string('notset','peerreview');
            }
        }

        // No submission made yet
        else {
            $timecreated   = html_writer::tag('div', '&nbsp;', array('id'=>'tt'));
            $reviews       = html_writer::tag('div', '&nbsp;', array('id'=>'re'));
            $status        = html_writer::tag('div', '&nbsp;', array('id'=>'st'));
            $seedoreviews  = html_writer::tag('div', '&nbsp;', array('id'=>'se'));
            $suggestedmark = html_writer::tag('div', '&nbsp;', array('id'=>'su'));
            $grade         = html_writer::tag('div', '-', array('id'=>'g'));
        }

        // Check for moderations
        // TODO do this once for whole class
        $moderationCountSQL = "SELECT count(r.id)
                                 FROM {peerreview} p, {peerreview_review} r
                                WHERE p.course='$course->id'
                                  AND p.id = r.peerreview
                                  AND r.teacherreview=1
                                  AND r.reviewee='$auser->id'";
        $moderationCount = $DB->count_records_sql($moderationCountSQL);
        $attributes = array();
        if($moderationCount<$moderationtarget) {
            $attributes['class'] = 'errorStatus';
        }
        $moderations = html_writer::tag('div', $moderationCount, $attributes);

        // Add the row to the table
        $row = array($picture, $studentName, $timecreated, $reviews, $moderations, $status, $seedoreviews, $suggestedmark, $grade);
        $table->add_data($row, 'row'.$auser->id);
        // $offset++;
    }
}

/// Print the table.
$table->print_html();

echo html_writer::start_tag('div', array('class'=>'userPrefs'));

// Form for mass marking.
echo html_writer::start_tag('form', array('action'=>'massMark.php', 'method'=>'post'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'peerreviewid', 'value'=>$peerreview->id));
echo html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('massmark','peerreview')));
echo $OUTPUT->help_icon('massmark', 'peerreview', false);
echo html_writer::end_tag('form');
echo $OUTPUT->spacer(array('height'=>10),true);

/// Mini form for setting user preference.
echo html_writer::start_tag('form', array('action'=>'submissions.php', 'method'=>'post'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$cm->id));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'peerreviewid', 'value'=>$peerreview->id));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'updatepref', 'value'=>'1'));
echo get_string('pagesize','peerreview');
echo $OUTPUT->help_icon('pagesize', 'peerreview', false);
echo '&nbsp;';
echo html_writer::empty_tag('input', array('type'=>'text', 'name'=>'perpage', 'size'=>'1', 'value'=>$perpage));
echo html_writer::end_tag('br');
echo $OUTPUT->spacer(array('height'=>'10px'),true);
echo get_string('moderationtarget','peerreview');
echo $OUTPUT->help_icon('moderationtarget', 'peerreview', false);
echo '&nbsp;';
echo html_writer::empty_tag('input', array('type'=>'text', 'name'=>'moderationtarget', 'size'=>'1', 'value'=>$moderationtarget));
echo html_writer::end_tag('br');
echo $OUTPUT->spacer(array('height'=>10),true);
echo get_string('quickgrade','peerreview');
echo $OUTPUT->help_icon('quickgrade', 'peerreview', false);
echo '&nbsp;';
echo html_writer::checkbox('quickgrade', null, $quickgrade);
echo html_writer::end_tag('br');
echo $OUTPUT->spacer(array('height'=>10),true);
echo html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('savepreferences')));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
