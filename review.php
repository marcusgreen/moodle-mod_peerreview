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
$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // peerreview instance ID - it should be named as the first character of the module
if ($id) {
    $cm         = get_coursemodule_from_id('peerreview', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $peerreview = $DB->get_record('peerreview', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $peerreview = $DB->get_record('peerreview', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $peerreview->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('peerreview', $peerreview->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO fix logging
//add_to_log($course->id, 'peerreview', 'view', "view.php?id={$cm->id}", $peerreview->name, $cm->id);

$PAGE->set_url('/mod/peerreview/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('peerreview-'.$somevar);

// Check capabilities
require_capability('mod/peerreview:view', $context);
$cangrade = has_capability('mod/peerreview:grade', $context);

// Get the criteria if they exist
$criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($peerreview->id),'ordernumber');
$numberOfCriteria = 0;
if(is_array($criteriaList)) {
    $criteriaList = array_values($criteriaList);
    $numberOfCriteria = count($criteriaList);
}

// Ensure criteria are set
if($cangrade && $numberOfCriteria==0) {
    $attributes = array('peerreviewid' => $peerreview->id, 'id' => $cm->id);
    $redirecturl = new moodle_url('/mod/peerreview/criteria.php', $attributes);
    redirect($redirecturl,0);
    die();
}

// Output starts here
echo $OUTPUT->header();

// Show teachers tabs
if($cangrade) {
    echo peerreview_tabs($cm->id, $peerreview->id, 'description');
}

// Shows students their status
else {

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

    // Not yet submitted
    if(!$submission) {
        if(isopen($peerreview)) {
            print_progress_box('blueProgressBox', '1', 'submit', 'submitbelow');
        }
        else {
            print_progress_box('redProgressBox', '1', 'submit', 'closed');
        }
        print_progress_box('greyProgressBox', '2', 'reviews', 'submitfirst');
        print_progress_box('greyProgressBox', '3', 'feedback', 'notavailable');
    }

    // Submitted
    else {
        print_progress_box('greenProgressBox', '1', 'submit', 'submitted');

        // Completing Reviews
        if ($numberOfReviewsCompleted < 2) {
            if ($numberOfReviewsCompleted == 1) {
                print_progress_box('blueProgressBox', '2', 'reviews', 'reviewsonemore');
            }
            else if ($numberOfReviewsAllocated==0) {
                print_progress_box('blueProgressBox', '2', 'reviews', 'reviewsnotallocated');
            }
            else {
                print_progress_box('blueProgressBox', '2', 'reviews', 'completereviewsbelow');
            }
            print_progress_box('greyProgressBox', '3', 'feedback', 'notavailable');
        }

        // Viewing feedback
        else {
            print_progress_box('greenProgressBox', '2', 'reviews', 'reviewscomplete');
            if ($submission->timemarked == 0) {
                print_progress_box('blueProgressBox', '3', 'feedback', 'marknotassigned');
            }
            else {
                print_progress_box('greenProgressBox', '3', 'feedback', 'markassigned');
            }
        }
    }
}
print_progress_box('end');

// Completing Reviews
if(!$cangrade && $submission && $numberOfReviewsAllocated==2 && $numberOfReviewsCompleted<2) {
    echo $OUTPUT->box_start();

    // Allow review file to be downloaded
/*    if(
        isset($peerreview->submissionformat == SUBMIT_DOCUMENT &&
        $numberOfReviewsDownloaded == $numberOfReviewsCompleted
    ) {
        // Show the link to download the file
        // TODO: find a better way to do this using renderers
        echo $OUTPUT->heading(get_string('reviewnumber', 'peerreview', $numberOfReviewsCompleted+1), 2, 'leftHeading');
        echo $OUTPUT->heading(get_string('getthedocument','peerreview'), 3, 'leftHeading');
        $javascript = "setTimeout('document.getElementById(\\'continueButton\\').disabled=false;',3000); return true;";
        $downloadprompt = get_string('clicktodownload','peerreview');
        echo $this->review_file_link($downloadprompt, $numberOfReviewsCompleted+1, $javascript);
        echo $OUTPUT->heading(get_string('continuetoreviewdocument', 'peerreview'), 3, 'leftHeading');

        // Show the link/button to continue, but disable until file is downloaded
        $url = new moodle_url('/mod/peerreview/view.php', array(id => $cm->id));
        $javascript = 'document.write(\'<input type="button" disabled id="continueButton" '.
                      'onclick="document.location=\\\'view.php?id='.$cm->id.'\\\'" '.
                      'value="'.get_string('continue','peerreview').'" />\');';
        echo HTML_WRITER::start_tag('noscript');
        echo HTML_WRITER::link($url, get_string('continue','peerreview'));
        echo HTML_WRITER::end_tag('noscript');
        echo HTML_WRITER::script($javascript);
        echo HTML_WRITER::tag('br');

        // Show the assignment instructions but hidden
        $this->showHiddenDescription();
    }

    // Reviewing
    else {
*/
    // Save review
    if($comment = clean_param(htmlspecialchars(optional_param('comment', NULL, PARAM_RAW)), PARAM_CLEAN)) {
        $reviewnumber = required_param('review', PARAM_INT);
        $reviewToUpdate = $DB->get_record('peerreview_review', array('id'=>$reviewsAllocated[$numberOfReviewsCompleted]->id));
        $url = new moodle_url($CFG->wwwroot.'/mod/peerreview/view.php', array('id'=>$cm->id));
        if($reviewToUpdate && $reviewToUpdate->completed != 1 && $reviewnumber==$numberOfReviewsCompleted+1) {
            echo $OUTPUT->heading(get_string('reviewnumber', 'peerreview', $numberOfReviewsCompleted+1));
            // echo $OUTPUT->notification(get_string('savingreview', 'peerreview'), 'notifysuccess');
            for($i=0; $i<$numberOfCriteria; $i++) {
                $criterionToSave = new stdClass();
                $criterionToSave->review = $reviewsAllocated[$numberOfReviewsCompleted]->id;
                $criterionToSave->criterion = $i;
                $criterionToSave->checked = optional_param('criterion'.$i, 0, PARAM_BOOL);
                $DB->insert_record('peerreview_review_criterion',$criterionToSave);
            }
            $reviewToUpdate->reviewcomment = $comment;
            $reviewToUpdate->completed     = 1;
            $reviewToUpdate->timecompleted = time();
            $reviewToUpdate->timemodified  = $reviewToUpdate->timecompleted;
            $DB->update_record('peerreview_review', $reviewToUpdate);

            // Send an email to student
            $student = $reviewToUpdate->reviewee;
            $subject = get_string('peerreviewreceivedsubject','peerreview');
            $messagehtml = get_string('peerreviewreceivedmessage','peerreview').'<br /><br />'.
                           s($peerreview->name).'<br />'.
                           get_string('course').': '.s($course->fullname).'<br /><br />'.
                           HTML_WRITER::link($url, get_string('peerreviewreceivedlinktext','peerreview'));
            $messagetext = get_string('peerreviewreceivedmessage','peerreview')."\n\n".
                           s($peerreview->name)."\n".
                           get_string('course').': '.s($course->fullname)."\n\n".
                           get_string('peerreviewreceivedlinktext','peerreview').': '.$url;
            email_from_teacher($student, $subject, $messagetext, $messagehtml, 'reviewreceived');

            // TODO: Make this a redirect
            echo $OUTPUT->notification(get_string('reviewsaved', 'peerreview'),'notifysuccess');
        }
        else {
            echo $OUTPUT->notification(get_string('reviewalreadysaved', 'peerreview'));
        }
        echo $OUTPUT->continue_button($url);
    }

    // Show review form
    else if($numberOfCriteria>0) {
        $review = get_next_review($peerreview);
        if($review) {
            echo $OUTPUT->heading(get_string('reviewnumber','peerreview',$numberOfReviewsCompleted+1),2,'leftHeading');
            $submission = get_submission($peerreview->id, $review->reviewee);
            if($peerreview->submissionformat==ONLINE_TEXT) {
                // TODO: Check if this is the best way to show a submission
                echo $OUTPUT->box(format_text(stripslashes($submission->onlinetext), PARAM_CLEAN),
                                  'generalbox', 'onlineTextSubmission');

                // Set the file status to downloaded
                $review->downloaded = 1;
                $review->timedownloaded = time();
                $review->timemodified = $review->timedownloaded;
                $DB->update_record('peerreview_review',$review);
            }
            else {
                echo $OUTPUT->heading(get_string('reviewdocument','peerreview'), 3, 'leftHeading');
            }
        }


        echo HTML_WRITER::start_tag('form', array('action' => 'view.php', 'method' => 'post', 'id' => 'peerreviewform'));
        echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
        echo HTML_WRITER::empty_tag('input', array('type' => 'hidden', 'name' => 'review', 'value' => $numberOfReviewsCompleted+1));
        echo HTML_WRITER::tag('p', get_string('criteriainstructions','peerreview'));
        $table = new html_table();
        $table->attributes = array('class' => 'criteriaTable');
        $table->colclasses = array('criteriaCheckboxColumn', 'criteriaTextColumn');
        $options = new stdClass();
        $options->para = false;

        foreach($criteriaList as $i=>$criterion) {
            $row = new html_table_row();
            $cell = new html_table_cell();
            $attributes = array('type' => 'checkbox', 'name' => 'criterion'.$criterion->ordernumber,
                                'id' => 'criterion'.$criterion->ordernumber);
            $cell->text = HTML_WRITER::empty_tag('input', $attributes);
            $row->cells[] = $cell;
            $cell = new html_table_cell();
            if (!empty($criterion->textshownatreview)) {
                $text = $criterion->textshownatreview;
            }
            else {
                $text = $criterion->textshownwithinstructions;
            }
            $attributes = array('for' => 'criterion'.$criterion->ordernumber);
            $cell->text = HTML_WRITER::tag('label', format_text($text, FORMAT_MOODLE, $options), $attributes);
            $row->cells[] = $cell;
            $table->data[] = $row;
        }

        echo HTML_WRITER::table($table);

        echo $OUTPUT->spacer(array('height'=>5));
        echo HTML_WRITER::tag('p', get_string('commentinstructions','peerreview'));
        $attributes = array('name' => 'comment', 'id' => 'comment', 'rows' => '5', 'class' => 'writeCommentTextBox');
        echo HTML_WRITER::tag('textarea', '', $attributes);

        echo $OUTPUT->spacer(array('height'=>5));
        $PAGE->requires->string_for_js('nocommentalert', 'peerreview');
        $attributes = array('type' => 'submit', 'value' => get_string('savereview','peerreview'),
                            'onclick' => 'return M.peerreview.checkComment();');
        echo HTML_WRITER::empty_tag('input', $attributes);
        echo HTML_WRITER::end_tag('form');
        $jsmodule = array(
            'name' => 'peerreview',
            'fullpath' => '/mod/peerreview/module.js',
            'requires' => array(),
            'strings' => array(),
        );
        $PAGE->requires->js_init_call(null, null, false, $jsmodule);

    }
    else {
        echo $OUTPUT->notification(get_string('nocriteriaset','peerreview'));
    }
    echo $OUTPUT->box_end();
}

// For early submitters waiting for reviews to be allocated
else if(!$cangrade && $submission && $numberOfReviewsAllocated==0) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->notification(get_string("poolnotlargeenough", "peerreview"),'notifysuccess');
    echo $OUTPUT->heading(get_string('yoursubmission','peerreview'),2,'leftHeading');
    $table = new html_table();
    $table->attributes = array('class' => 'submissionInfoTable');
    $table->colclasses = array('labelColumn');
    $table->data[] = array(get_string('status').':', get_string('waitingforpeerstosubmit','peerreview'));
    $table->data[] = array(get_string('submission','peerreview').':', submission_link($peerreview->id, $submission->id, $context->id,false,$cm->id,$cangrade));
    $table->data[] = array(get_string('submittedtime','peerreview').':',
                           userdate($submission->timecreated,get_string('strftimedaydatetime')));
    echo HTML_WRITER::table($table);

    // Show the assignment instructions but hidden
    showHiddenDescription($peerreview, $cm->id);
    echo $OUTPUT->box_end();
}

// Feedback on submission and reviews of student
else if(!$cangrade && $submission && $numberOfReviewsCompleted==2) {
    echo $OUTPUT->box_start();

    // Find the reviews for this student
    $reviews = get_reviews_of_student($peerreview->id, $USER->id);
    $numberOfReviewsOfThisStudent = 0;
    if(is_array($reviews)) {
        $numberOfReviewsOfThisStudent = count($reviews);
    }
    $status = get_status($reviews,$numberOfCriteria);

    // Table at top of page
    echo HTML_WRITER::start_tag('div',array('class'=>'submissionstats'));

    // Table about student submission
    echo $OUTPUT->heading(get_string('yoursubmission','peerreview'),1,'leftHeading');
    $table = new html_table();
    $table->attributes = array('class' => 'submissionInfoTable');
    $table->colclasses = array('labelColumn');
    $grade = $submission->timemarked==0?get_string('notavailable','peerreview'):$this->display_grade($submission->grade);
    $table->data[] = array(get_string('grade','peerreview').':', $grade);
    $statusString = '';
    switch($status) {
        case FLAGGED:
        case CONFLICTING:
        case FLAGGEDANDCONFLICTING:
            $statusstring =  get_string('waitingforteacher','peerreview');
            break;
        case LESSTHANTWOREVIEWS:
            $statusstring = get_string('waitingforpeers','peerreview');
            break;
        case CONCENSUS:
            $statusstring = get_string('reviewconcensus','peerreview');
            break;
        case OVERRIDDEN:
            $statusstring = get_string('reviewsoverridden','peerreview');
            break;
    }
    $table->data[] = array(get_string('status').':', $statusstring);
    $table->data[] = array(get_string('submission','peerreview').':', submission_link($peerreview->id, $submission->id, $context->id,false,$cm->id,$cangrade));
    $submittedtime = userdate($submission->timecreated,get_string('strftimedaydatetime'));
    $table->data[] = array(get_string('submittedtime','peerreview').':', $submittedtime);
    echo HTML_WRITER::table($table);
    echo HTML_WRITER::end_tag('div');
    echo HTML_WRITER::start_tag('div', array('class'=>'reviewstats'));

    // Gather stats about student reviewing
    $reviewStats = get_review_statistics($peerreview);
    $reviewsByThisStudent = $DB->get_records_select('peerreview_review','peerreview=\''.$peerreview->id.'\' AND reviewer=\''.$submission->userid.'\' AND completed=\'1\'');
    $numberOfReviewsByThisStudent = is_array($reviewsByThisStudent)?count($reviewsByThisStudent):0;
    $timeTakenReviewing = 0;
    $commentLength = 0;
    $criterionMatchesWithInstructor = 0;
    $comparableReviews = 0;
    $flags = 0;
    foreach($reviewsByThisStudent as $id=>$review) {
        $timeTakenReviewing += $review->timecompleted - $review->timedownloaded;
        $commentLength += strlen($review->reviewcomment);
        $teacherReviews = $DB->get_records_select('peerreview_review','peerreview=\''.$peerreview->id.'\' AND reviewee=\''.$review->reviewee.'\' AND teacherreview=\'1\' AND completed=\'1\'',null,'timecompleted DESC','*',0,1);
        if(is_array($teacherReviews) && count($teacherReviews)>0) {
        $comparableReviews++;
            $teacherReviews = array_values($teacherReviews);
            $teacherCriteria = array_values($DB->get_records('peerreview_review_criterion',array('review'=>$teacherReviews[0]->id),'criterion'));
            $studentCriteria = array_values($DB->get_records('peerreview_review_criterion',array('review'=>$review->id),'criterion'));
            for($i=0; $i<$numberOfCriteria; $i++) {
                if($teacherCriteria[$i]->checked == $studentCriteria[$i]->checked) {
                    $criterionMatchesWithInstructor++;
                }
            }
        }
        if($review->flagged==1) {
            $flags++;
        }
    }

    // Table about reviews by student
    echo $OUTPUT->heading(get_string('yourreviewing','peerreview'),1,'leftHeading');
    $table = new html_table();
    $table->attributes = array('class' => 'submissionInfoTable');
    $table->colclasses = array('labelColumn');
    $icon = $OUTPUT->pix_icon('tick','','peerreview');
    $label = $numberOfReviewsByThisStudent.' '.get_string('completedlabel','peerreview',$peerreview->reviewreward);
    $table->data[] = array(get_string('completed','peerreview').':', $icon.$label);

    // Time taken
    if ($timeTakenReviewing/2 < MINIMAL_REVIEW_TIME) {
        $icon = $OUTPUT->pix_icon('alert','','peerreview');
        $label = get_string('tooshort','peerreview');
    }
    else if($reviewStats->numberOfReviews < REVIEW_FEEDBACK_MIN) {
        $icon = $OUTPUT->pix_icon('questionMark','','peerreview');
        $label = get_string('notenoughreviewstocompare','peerreview');
    }
    else if ($timeTakenReviewing/2 < $reviewStats->reviewTimeOutlierLowerBoundary) {
        $icon = $OUTPUT->pix_icon('alert','','peerreview');
        $label = get_string('shorterthanmost','peerreview');
    }
    else if ($timeTakenReviewing/2 > $reviewStats->reviewTimeOutlierUpperBoundary) {
        $icon = $OUTPUT->pix_icon('alert','','peerreview');
        $label = get_string('longerthanmost','peerreview');
    }
    else {
        $icon = $OUTPUT->pix_icon('tick','','peerreview');
        $label = get_string('good','peerreview');
    }
    $table->data[] = array(get_string('reviewtimetaken','peerreview').':', $icon.$label);

    // Comments made
    if ($commentLength/2 < MINIMAL_REVIEW_COMMENT_LENGTH) {
        $icon = $OUTPUT->pix_icon('alert','','peerreview');
        $label = get_string('tooshort','peerreview');
    }
    else if($reviewStats->numberOfReviews < REVIEW_FEEDBACK_MIN) {
        $icon = $OUTPUT->pix_icon('questionMark','','peerreview');
        $label = get_string('notenoughreviewstocompare','peerreview');
    }
    else if ($commentLength/2 < $reviewStats->commentLengthOutlierLowerBoundary) {
        $icon = $OUTPUT->pix_icon('alert','','peerreview');
        $label = get_string('shorterthanmost','peerreview');
    }
    else if ($commentLength/2 > $reviewStats->commentLengthOutlierUpperBoundary) {
        $icon = $OUTPUT->pix_icon('alert','','peerreview');
        $label = get_string('longerthanmost','peerreview');
    }
    else {
        $icon = $OUTPUT->pix_icon('tick','','peerreview');
        $label = get_string('goodlength','peerreview');
    }
    $table->data[] = array(get_string('reviewcomments','peerreview').':', $icon.$label);

    // Accuracy
    if($comparableReviews < 1) {
        $icon = $OUTPUT->pix_icon('questionMark','','peerreview');
        $label = get_string('notenoughmoderationstocompare','peerreview');
    }
    else {
        if ($criterionMatchesWithInstructor/($comparableReviews*$numberOfCriteria) < ACCURACY_REQUIRED) {
            $icon = $OUTPUT->pix_icon('alert','','peerreview');
            $label = get_string('poor','peerreview');
        }
        else {
            $icon = $OUTPUT->pix_icon('tick','','peerreview');
            $label = get_string('good','peerreview');
        }
        $label .= ' ('.(int)($criterionMatchesWithInstructor/($comparableReviews*$numberOfCriteria)*100).'%)';
    }
    $table->data[] = array(get_string('reviewaccuracy','peerreview').':', $icon.$label);

    // Flags
    if ($flags>=1) {
        $icon = $OUTPUT->pix_icon('alert', '', 'peerreview');
    }
    else {
        $icon = $OUTPUT->pix_icon('tick', '', 'peerreview');
    }
    $label = get_string('flags'.$flags,'peerreview').'</td></tr>';
    $table->data[] = array(get_string('flags','peerreview').':', $icon.$label);

    echo HTML_WRITER::table($table);
    echo HTML_WRITER::end_tag('div');

    showHiddenDescription($peerreview, $cm->id);

    // If reviews are available, show to student
    echo $OUTPUT->heading(get_string('reviewsofyoursubmission','peerreview'),1,'leftHeading');
    if($reviews) {
        $table = new html_table();
        $table->attributes = array('class' => 'criteriaTable');
        $table->colclasses = array('labelColumn');
        $rowattributes = array('class'=>'criteriaDisplayRow');
        for($i=0; $i<$numberOfCriteria; $i++) {
            $row = new html_table_row();
            $row->attributes = $rowattributes;
            $cellattributes = array('class'=>'criteriaCheckboxColumn');
            for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
                $cell = new html_table_cell();
                $cell->attributes = $cellattributes;
                $cell->style = 'background:'.$REVIEW_COLOURS[$j % $NUMBER_OF_COLOURS];
                $myreview = $reviews[$j];
                $cellval = $myreview->{'checked'.$i};
                if ($reviews[$j]->{'checked'.$i}==1 ) {
                    $prm = array('type'=>'checkbox', 'disabled'=>'true', 'checked'=>$reviews[$j]->{'checked'.$i}==1);
                } else {
                    $prm = array('type'=>'checkbox', 'disabled'=>'true');
                }

                $cell->text = HTML_WRITER::empty_tag('input', $prm);
                $row->cells[] = $cell;
            }
            $options = new stdClass();
            $options->para = false;
            // echo '<td class="criteriaDisplayColumn">'..'</td>';
            // echo '</tr>';
            $cell = new html_table_cell();
            $cellattributes = array('class'=>'criteriaDisplayColumn');
            $cell->attributes = $cellattributes;
            $cell->text = format_text(($criteriaList[$i]->textshownatreview!=''?$criteriaList[$i]->textshownatreview:$criteriaList[$i]->textshownwithinstructions),FORMAT_MOODLE,$options);
            // <input type="checkbox" disabled'.(?' checked':'').' /></td>';
            $row->cells[] = $cell;
            $table->data[] = $row;
        }
        $studentCount = 1;
        for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
            $row = new html_table_row();
            $cellattributes = array('class'=>'criteriaCheckboxColumn');
            for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
                $cell = new html_table_cell();
                $cell->attributes = $cellattributes;
                $cell->style  = 'background:'.($j>$numberOfReviewsOfThisStudent-$i-1?$REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$NUMBER_OF_COLOURS]:$REVIEW_COLOURS[$j%$NUMBER_OF_COLOURS]).';';
                $cell->text = '&nbsp';
                // echo '<td class="criteriaCheckboxColumn" style="background:'.($j>$numberOfReviewsOfThisStudent-$i-1?$REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$NUMBER_OF_COLOURS]:$REVIEW_COLOURS[$j%$NUMBER_OF_COLOURS]).';">&nbsp;</td>';
                $row->cells[] = $cell;
            }
            // echo '<td class="reviewCommentRow" style="background:'.$REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$NUMBER_OF_COLOURS].';">';
            $cell = new html_table_cell();
            $cellattributes = array('class'=>'criteriaDisplayColumn');
            $cell->style = 'background:'.$REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$NUMBER_OF_COLOURS];
            $cell->attributes = $cellattributes;
            // $cell->text  = '<table width="100%" cellpadding="0" cellspacing="0" border="0" class="reviewDetailsTable">';
            // $cell->text .= '<tr class="reviewDetailsRow">';
            $cell->text .= HTML_WRITER::start_tag('div', array('class' => 'reviewDetailsRow'));
            $cell->text .= HTML_WRITER::start_tag('div');
            $cell->text .= get_string('conductedby','peerreview').': ';
            if($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview==1) {
                $cell->text .= $reviews[$numberOfReviewsOfThisStudent-$i-1]->firstname . ' ' . $reviews[$numberOfReviewsOfThisStudent-$i-1]->lastname .' ('.get_string('defaultcourseteacher').')';
            } else {
                $cell->text .= get_string('defaultcoursestudent').' '.$studentCount++;
            }
            $cell->text .= HTML_WRITER::end_tag('div');
            $cell->text .= HTML_WRITER::start_tag('div', array('class'=>'reviewDateColumn'));
            $cell->text .= userdate($reviews[$numberOfReviewsOfThisStudent-$i-1]->timemodified,get_string('strftimedatetime'));
            $cell->text .= HTML_WRITER::end_tag('div');
            $cell->text .= HTML_WRITER::end_tag('div');
            // $cell->text .= '</tr>';
            // $cell->text .= '<tr><td colspan="2"><div class="commentTextBox" style="background:'.$REVIEW_COMMENT_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%count($REVIEW_COMMENT_COLOURS)].';">'.format_string(stripslashes($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment)).'</div></td></tr>';
            $cell->text .= HTML_WRITER::start_tag('div', array('class'=>'commentTextBox', 'style'=>'background:'.$REVIEW_COMMENT_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%count($REVIEW_COMMENT_COLOURS)]));
            // TODO Fix output formatting
            $cell->text .= format_string(stripslashes($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment));
            $cell->text .= HTML_WRITER::end_tag('div');

            if($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview!=1) {
                $cell->text .= HTML_WRITER::start_tag('div', array('class' => 'reviewDetailsRow'));
                // $cell->text .= '<tr class="reviewDetailsRow"><td colspan="2"><em>';
                $cell->text .= $reviews[$numberOfReviewsOfThisStudent-$i-1]->flagged==1?get_string('flagprompt1','peerreview').' ':get_string('flagprompt2','peerreview').' ';

                // TODO Do this with AJAX
                $attributes = array('id'=>$cm->id, 'peerreviewid'=>$peerreview->id, 'r'=>$reviews[$numberOfReviewsOfThisStudent-$i-1]->review);
                $flagToggleURL = new moodle_url('/mod/peerreview/toggleFlag.php', $attributes);
                $flagged = $reviews[$numberOfReviewsOfThisStudent-$i-1]->flagged==1;
                $linkText = $OUTPUT->pix_icon($flagged?'flagRed':'flagGreen', '', 'mod_peerreview').' '.get_string($flagged?'flaglink1':'flaglink2', 'peerreview');
                $cell->text .= HTML_WRITER::link($flagToggleURL, $linkText);
                // $cell->text .= '</em></td></tr>';
                $cell->text .= HTML_WRITER::end_tag('div');
            }
            // $cell->text .= '</table>';
            // echo '</td>';
            // echo '</tr>';
            $row->cells[] = $cell;
            $table->data[] = $row;

            // Record that the review has been viewed by the student
            $reviewRecordToSave = new stdClass;
            $reviewRecordToSave->id = $reviews[$numberOfReviewsOfThisStudent-$i-1]->review;
            if($reviews[$numberOfReviewsOfThisStudent-$i-1]->timefirstviewedbyreviewee==0) {
                $reviewRecordToSave->timefirstviewedbyreviewee = time();
            }
            $reviewRecordToSave->timelastviewedbyreviewee = time();
            if (!property_exists($reviewRecordToSave, 'timesviewedbyreviewee')) {
                $reviewRecordToSave->timesviewedbyreviewee = 0;
            }
            $reviewRecordToSave->timesviewedbyreviewee++;
            $DB->update_record('peerreview_review', $reviewRecordToSave);
        }
        echo HTML_WRITER::table($table);
    }
    else {
        echo '<p>'.get_string('noreviews','peerreview');
    }

    echo $OUTPUT->box_end();
}

// First page with description and criteria
else {
    // Show description
    view_intro($peerreview, $cm->id);

    // Show criteria
    echo $OUTPUT->box_start();
    echo HTML_WRITER::tag('a', '', array('name' => 'criteria'));
    echo $OUTPUT->heading(get_string('criteria','peerreview'), 2, 'leftHeading');
    if ($cangrade) {
        $criteriaurl = new moodle_url('/mod/peerreview/criteria.php', array('id'=>$cm->id, 'peerreviewid'=>$peerreview->id));
        echo $OUTPUT->action_link($criteriaurl, get_string('setcriteria', 'peerreview'));
        echo $OUTPUT->heading(get_string('criteriabeforesubmission','peerreview'), 3, 'leftHeading');
    }
    if($numberOfCriteria>0) {
        echo criteria_table($criteriaList);
        if ($cangrade) {
            echo $OUTPUT->heading(get_string('criteriaaftersubmission','peerreview'),3,'leftHeading');
            echo criteria_table($criteriaList, false);
        }
    }
    else {
        echo $OUTPUT->notification(get_string('nocriteriaset','peerreview'));
    }
    echo $OUTPUT->box_end();

    // With peer review teachers can grade but not submit (not here)
    if(!isopen($peerreview)) {
        print_string("notopen","peerreview");
    }
    else if (has_capability('mod/peerreview:submit', $context) && !$cangrade && !$submission) {
        echo $OUTPUT->heading(get_string('submission','peerreview'), 2, 'leftHeading');
        echo $OUTPUT->notification(get_string("singleuploadwarning","peerreview"));
        view_upload_form($peerreview, $cm->id);
    }
}

// Finish the page
echo $OUTPUT->footer();
