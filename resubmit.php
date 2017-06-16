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
 * Prints a particular instance of peerreview
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2013 Michael de Raadt (michaeld@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/peerreview/lib.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');

// Gather module information
$userid = optional_param('userid', 0, PARAM_INT); // course_module ID, or
$peerreviewid  = required_param('peerreviewid', PARAM_INT);  // peerreview instance ID - it should be named as the first character of the module

$peerreview = $DB->get_record('peerreview', array('id' => $peerreviewid), '*', MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $peerreview->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('peerreview', $peerreview->id, $course->id, false, MUST_EXIST);


require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO fix logging
//add_to_log($course->id, 'peerreview', 'view', "view.php?id={$cm->id}", $peerreview->name, $cm->id);

$PAGE->set_url('/mod/peerreview/resubmit.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Check capabilities
require_capability('mod/peerreview:view', $context);
require_capability('mod/peerreview:grade', $context);


// Output starts here
echo $OUTPUT->header();

// Check for existing submissions and reviews
$submission = get_submission($peerreview->id);
$reviewsAllocated = get_reviews_allocated_to_student($peerreview->id, $USER->id);
$numberOfReviewsAllocated  = 0;
$numberOfReviewsDownloaded = 0;
$numberOfReviewsCompleted  = 0;
if(is_array($reviewsAllocated)) {
    $numberOfReviewsAllocated = count($reviewsAllocated);
    foreach($reviewsAllocated as $review) {
        if($review->downloaded == 1) {
            $numberOfReviewsDownloaded++;
        }
        if($review->completed == 1) {
            $numberOfReviewsCompleted++;
        }
    }
}


// Show description
view_intro($peerreview, $cm->id);


echo $OUTPUT->heading(get_string('submission','peerreview'), 2, 'leftHeading');
view_upload_form($peerreview, $cm->id, $userid);


// Finish the page
echo $OUTPUT->footer();
