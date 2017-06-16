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
 * Peer review online submission viewing page
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2012 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');

//mod may 2017. chekk if it have to come back, and get also the cmdid, it serves to redirect to submissions.php page
$back='s';
if (isset($_GET['back'])) {
    $back = $_GET['back'];
}

$cmid=-1;
if (isset($_GET['cmid'])) {
    $cmid = $_GET['cmid'];
}

$cangrade=false;
if (isset($_GET['cangrade'])) {
    $cangrade = $_GET['cangrade'];
}


$contextid = required_param('contextid', PARAM_INT);
$peerreviewid = required_param('peerreviewid', PARAM_INT);
$courseid = $DB->get_record('peerreview', ['id' => $peerreviewid], 'course')->course;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);
if (! $peerreview = $DB->get_record("peerreview", array('id' => $peerreviewid))) {
    print_error('invalidid', 'peerreview');
}

$submissionID = required_param('submissionid',PARAM_TEXT);
$submission = $DB->get_record('peerreview_submissions', array('id'=>$submissionID));
$user = $DB->get_record('user', array('id'=>$submission->userid));

// Check user is logged in and capable of viewing the submission
require_login($course->id, false);
if($USER->id != $user->id) {
    require_capability('mod/peerreview:grade', $context);
}

// Set up the page
$attributes = array('peerreviewid' => $peerreview->id, 'contextid' => $contextid, 'submissionid'=>$submissionID);
$PAGE->set_url('/mod/peerreview/viewOnlneText.php', $attributes);
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('submission', 'peerreview').': '.fullname($user), 2, 'leftHeading');
echo $OUTPUT->box_start();
echo format_text(stripslashes($submission->onlinetext), PARAM_CLEAN);
$continueattributes = array('n' => $peerreview->id, 'contextid' => $contextid, 'submissionid'=>$submissionID);

//if back is submission back to tab Submission
if ($back=='s' && $cangrade){
    $continueurl = new moodle_url('/mod/peerreview/submissions.php', array('id' => $cmid , 'peerreviewid'=>$peerreview->id));
}else{
    $continueurl = new moodle_url('/mod/peerreview/view.php', $continueattributes);
}

echo $OUTPUT->continue_button($continueurl);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();