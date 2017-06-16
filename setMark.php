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
global $CFG, $DB, $OUTPUT, $PAGE, $USER;
require_once($CFG->dirroot."/mod/peerreview/locallib.php");

// Get course ID and assignment ID
$id     = optional_param('id', 0, PARAM_INT);          // Course module ID
$peerreviewid      = optional_param('peerreviewid', 0, PARAM_INT);           // peerreviewID
$userid     = required_param('userid', PARAM_INT);
$grade = required_param('grade', PARAM_INT);
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
$continueurl = $CFG->wwwroot.'/mod/peerreview/submissions.php?id='. $id . '&peerreviewid='. $peerreviewid;
$student = $CFG->wwwroot.'/mod/peerreview/view.php?id='. $id . '&peerreviewid='. $peerreviewid;
peerreview_set_mark($peerreview, $course, $userid, $grade, $USER->id, $student);

$attributes = array('peerreviewid' => $peerreviewid, 'id' => $id);
$PAGE->set_url('/mod/peerreview/setMark.php', $attributes);
/*
$PAGE->set_title(format_string($this->assignment->name));
$PAGE->set_heading(format_string($this->course->fullname));
$PAGE->set_context($this->context);
*/
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Done'));
echo $OUTPUT->continue_button($continueurl);
echo '</div>';
echo $OUTPUT->footer();

