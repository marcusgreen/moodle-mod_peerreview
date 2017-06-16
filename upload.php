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
 * Peer review upload page
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2012 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/peerreview/upload_form.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');

$cmid = required_param('id', PARAM_INT);
$peerreviewid = required_param('peerreviewid', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$cm = get_coursemodule_from_id('peerreview', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
if (! $peerreview = $DB->get_record("peerreview", array('id' => $peerreviewid))) {
    print_error('invalidid', 'peerreview');
}
$continueurl = new moodle_url($CFG->wwwroot.'/mod/peerreview/view.php',array('id'=>$cm->id));

// Check user is logged in and capable of submitting
require_login($course, false, $cm);

// Teacher?
$cangrade = has_capability('mod/peerreview:grade', $context);
// Set up the page
$attributes = array('peerreviewid' => $peerreview->id, 'id' => $cm->id);
$PAGE->set_url('/mod/peerreview/criteria.php', $attributes);
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('upload'), 2, 'leftHeading');

$NUM_REVIEWS = 2;
$POOL_SIZE = 2*$NUM_REVIEWS+1; // including current submitter

if (isopen($peerreview)) {

    // Students can submit only once, teachers can resubmit students work
    if($cangrade || !$DB->record_exists('peerreview_submissions',array('peerreview'=>$peerreview->id,'userid'=>$userid))) {

        $newsubmission = NULL;

        // Process online text
        if($peerreview->submissionformat == ONLINE_TEXT) {
            $mform = new mod_peerreview_edit_form($CFG->wwwroot.'/mod/peerreview/upload.php',array('peerreviewid'=>$peerreview->id, 'id'=>$cm->id));
            if ($formdata = $mform->get_data()) {
                $newsubmission = $DB->get_record('peerreview_submissions', ['peerreview' => $peerreview->id, 'userid' => $userid]);
                if ($newsubmission == false) {
                    $newsubmission = prepare_new_submission($peerreview->id, $userid);
                    $newsubmission->onlinetext = format_text($formdata->onlinetext['text'], PARAM_CLEANHTML);
                    $DB->insert_record('peerreview_submissions', $newsubmission);
                } else {
                    $newsubmission->onlinetext = format_text($formdata->onlinetext['text'], PARAM_CLEANHTML);
                    $DB->update_record('peerreview_submissions', $newsubmission);
                }



                // TODO fix logging
                // add_to_log($course->id, 'peerreview', 'upload', 'view.php?id='.$cmid, 'Peer review submission uploaded', $cmid, $userid);
            }
            else {
                echo $OUTPUT->notification(get_string('nosubmission','peerreview'));
                echo $OUTPUT->continue_button($continueurl);
            }
        }

        // Process submitted document
        else {
            $options = get_file_options($peerreview);
            $mform = new mod_peerrview_upload_form(null, array('peerreviewid'=>$peerreview->id, 'id'=>$cmid, 'userid'=>$userid, 'options'=>$options));
            if ($formdata = $mform->get_data()) {

                // Check that a file was submitted
                // $fs = get_file_storage();
                // $files = $fs->get_area_files(
                //     $cm->id,
                //     'mod_peerreview',
                //     'peerreview_submissions',
                //     false,
                //     'id',
                //     false
                // );
                $newfilename = $mform->get_new_filename('peerreview_file');
                if ($newfilename) {
                    // Check the extension
                    $extension = $peerreview->fileextension;
                    $providedExtension = strtolower(substr($newfilename,strlen($newfilename)-strlen($extension)));
                    if($providedExtension == $extension) {

                        // Save the submission file
                        $submission = get_submission($peerreview->id, $userid, true); //create new submission if needed
                        // $fs->delete_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id);
                        $file = $mform->save_stored_file('peerreview_file', $context->id, 'mod_peerreview', 'submission', $submission->id, '/', $newfilename, true);
                        $newsubmission = $submission;
                        $sumbissionName = $newfilename;
                        $newsubmission->numfiles = 1;

                        // Record the submission details
                        $DB->update_record('peerreview_submissions', $newsubmission);

                        // TODO fix logging
                        //add_to_log($course->id, 'peerreview', 'upload', 'view.php?id='.$cmid, 'Peer review submission uploaded', $cmid, $userid);

                        // Let Moodle know that an assessable file was uploaded (eg for plagiarism detection)
                        // $eventdata = new stdClass();
                        // $eventdata->modulename   = 'peerreview';
                        // $eventdata->cmid         = $cmid;
                        // $eventdata->itemid       = $submission->id;
                        // $eventdata->courseid     = $course->id;
                        // $eventdata->userid       = $userid;
                        // $eventdata->file         = $file;
                        // events_trigger('assessable_file_uploaded', $eventdata);
                    }
                    else {
                        echo $OUTPUT->notification(get_string("incorrectfileextension","peerreview",$extension), 'redirectmessage');
                        echo $OUTPUT->continue_button($continueurl);
                    }
                }
                else {
                    echo $OUTPUT->notification(get_string('nosubmission','peerreview'));
                    echo $OUTPUT->continue_button($continueurl);
                }
            }
        }
        if ($newsubmission) {
            echo $OUTPUT->heading(get_string('uploadsuccessful','peerreview'));

            // Allocate reviews
            $recentSubmissions = array();
            $numberOfRecentSubmissions = 0;
            $query = "SELECT userid, timecreated
                        FROM {peerreview_submissions}
                       WHERE peerreview = $peerreview->id
                    ORDER BY timecreated DESC, id DESC";
            if ($submissionResult = $DB->get_records_sql($query, null, 0, ($POOL_SIZE+1))) {
                $recentSubmissions = array_values($submissionResult);
                $numberOfRecentSubmissions = count($recentSubmissions);
            }
            if ($numberOfRecentSubmissions >= $POOL_SIZE) {
                for($i=2; $i<2*$NUM_REVIEWS+1; $i+=2) {
                    if (!$DB->insert_record('peerreview_review', prepare_new_review($peerreview->id, $USER->id,$recentSubmissions[$i]->userid))) {
                        debugging('Unable to allocate review');
                    }
                }
            }

            // If pool just got large enough, allocated reviews to previous submitters
            if ($numberOfRecentSubmissions == $POOL_SIZE) {
                $subject = get_string('reviewsallocatedsubject','peerreview');
                $url = new moodle_url($CFG->wwwroot.'/mod/peerreview/view.php', array('id'=>$cm->id));
                $messagehtml = get_string('reviewsallocated','peerreview').'<br /><br />'.
                               s($peerreview->name).'<br />'.
                               get_string('course').': '.s($course->fullname).'<br /><br />'.
                               HTML_WRITER::link($url, get_string('reviewsallocatedlinktext','peerreview'));
                $messagetext = get_string('reviewsallocated','peerreview')."\n\n".
                               s($peerreview->name)."\n".
                               get_string('course').': '.s($course->fullname)."\n\n".
                               get_string('reviewsallocatedlinktext','peerreview').': '.$url;
                $recentSubmissions = array_reverse($recentSubmissions);
                for($i=0; $i<$POOL_SIZE-1; $i++) {
                    for($j=1; $j<=$NUM_REVIEWS; $j++) {
                        $DB->insert_record('peerreview_review', prepare_new_review($peerreview->id, $recentSubmissions[$i]->userid,$recentSubmissions[$i-2*$j+($i-2*$j>=0?0:$NUM_REVIEWS*2+1)]->userid));
                    }

                    // Send an email to student
                    $student = $recentSubmissions[$i]->userid;
                    email_from_teacher($student, $subject, $messagetext, $messagehtml, 'reviewsallocated');
                }
            }

            if($numberOfRecentSubmissions>=$POOL_SIZE) {
                // TODO: Make this a redirect
                echo $OUTPUT->notification(get_string('reviewsallocated', 'peerreview'),'notifysuccess');
                echo $OUTPUT->continue_button($continueurl);
            }
            else {
                echo $OUTPUT->notification(get_string('poolnotlargeenough', 'peerreview'),'notifysuccess');
                echo $OUTPUT->continue_button($continueurl);
            }
        }
    }
    else {
        echo $OUTPUT->notification(get_string('resubmit', 'peerreview')); // re-submitting not allowed
        echo $OUTPUT->continue_button($continueurl);
    }
}
else {
    echo $OUTPUT->notification(get_string('closed', 'peerreview')); // assignment closed
    echo $OUTPUT->continue_button($continueurl);
}

echo $OUTPUT->footer();
