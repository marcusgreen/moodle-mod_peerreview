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
 * Peer Review assignment class extension
 *
 * @package    contrib
 * @subpackage assignment_progress
 * @copyright  2010 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Notes about generic Assignment fields
 *
 *   assignment->var1 is used for reward value of each review
 *   assignment->var2 is used for optional self-reflection
 *   assignment->var3 is used to distinguish between submission of a file or online text
 *   submission->data1 is used for an online submission text (if that form of submission is chosen)
 *
 */

// Extend the base assignment class for peer review assignments
class assignment_peerreview  {

    // File related constants
    const FILE_PREFIX = 'toReview';
    const CRITERIA_FILE = 'criteria.php';
    const DOWNLOAD_PEERREVIEW_FILE = 'downloadFileToReview.php';
    const VIEW_ONLINE_TEXT = 'viewOnlineText.php';
    const MASS_MARK_FILE = 'massMark.php';
    const SAVE_COMMENTS_FILE = 'saveComments.php';
    const SET_MARK_FILE = 'setMark.php';
    const STYLES_FILE = 'styles.php';
    const TOGGLE_FLAG_FILE = 'toggleFlag.php';
    const RESUBMIT_FILE = 'resubmit.php';
    const SUBMISSIONS_FILE = 'submissions.php';
    const ANALYSIS_FILE = 'analysis.php';
    const VIEW_FILE = 'view.php';

	// Submission formats
	const SUBMIT_DOCUMENT = 0;
	const ONLINE_TEXT = 1;

    // Review constants
    public $REVIEW_COLOURS = array('#DCB39D','#AEA97E','#C1D692','#E1E1AE');
    public $REVIEW_COMMENT_COLOURS = array('#FCD3BD','#CEC99E','#E1F6B2','#F1F1CE');
    public $NUMBER_OF_COLOURS = 4; //count($this->REVIEW_COLOURS);
    const REVIEW_FEEDBACK_MIN = 10; // reviews
    const MINIMAL_REVIEW_TIME = 30; // seconds
    const MINIMAL_REVIEW_COMMENT_LENGTH = 12; // characters
    const ACCURACY_REQUIRED = 0.7;
    const ACCEPTABLE_REVIEW_RATE = 0.9;
    const ACCEPTABLE_REVIEW_ATTENTION_RATE = 0.9;
    const ACCEPTABLE_MODERATION_RATE = 0.6;
    const ACCEPTABLE_REVIEW_TIME = 60; // seconds
    const ACCEPTABLE_COMMENT_LENGTH = 50; // characters
    const ACCEPTABLE_FLAG_RATE = 0.1;
    const ACCEPTABLE_CHECKED_RATE = 0.5;

    // Status values
    const FLAGGED               = 0; // Moderation required
    const CONFLICTING           = 1; // Moderation required
    const FLAGGEDANDCONFLICTING = 2; // Moderation required
    const LESSTHANTWOREVIEWS    = 3; // Moderation required
    const CONCENSUS             = 4; // Good
    const OVERRIDDEN            = 5; // Good

    //--------------------------------------------------------------------------
    // Constructor
    function assignment_peerreview($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
     $this->base($cmid, $assignment, $cm, $course);
     global $DB;
        $this->strassignment = get_string('modulename', 'peerreview');
        $this->strassignments = get_string('modulenameplural', 'peerreview');
        $this->type = 'peerreview';
        if(isset($this->assignment->id) && $extra_settings = $DB->get_record('peerreview',array('id'=>$this->assignment->id))) {
            $this->assignment->fileextension = $extra_settings->fileextension;
            $this->assignment->savedcomments = $extra_settings->savedcomments;
        }
 	}
 	
 	function base($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        global $COURSE;

        if ($cmid == 'staticonly') {
            //use static functions only!
            return;
        }

        global $CFG,$DB;

        if ($cm) {
            $this->cm = $cm;
        } else if (! $this->cm = get_coursemodule_from_id('peerreview', $cmid)) {
            error('Course Module ID was incorrect');
        }

        $this->context = context_module::instance($this->cm->id);

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (! $this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
            error('Course is misconfigured');
        }

        if ($assignment) {
            $this->assignment = $assignment;
        } else if (! $this->assignment = $DB->get_record('peerreview',array('id'=> $this->cm->instance))) {
            error('assignment ID was incorrect');
        }

        $this->assignment->cmidnumber = $this->cm->id;     // compatibility with modedit assignment obj
        $this->assignment->courseid   = $this->course->id; // compatibility with modedit assignment obj

        $this->strassignment = get_string('modulename', 'peerreview');
        $this->strassignments = get_string('modulenameplural', 'peerreview');
        $this->strsubmissions = get_string('submissions', 'peerreview');
        $this->strlastmodified = get_string('lastmodified');
        $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strassignment.': '.format_string($this->assignment->name,true));

        // visibility handled by require_login() with $cm parameter
        // get current group only when really needed

    /// Set up things for a HTML editor if it's needed
        /*if ($this->usehtmleditor = can_use_html_editor()) {
            $this->defaultformat = FORMAT_HTML;
        } else {
            $this->defaultformat = FORMAT_MOODLE;
        }*/
           $this->defaultformat = FORMAT_MOODLE;

    }


    //--------------------------------------------------------------------------
	// Not used with peerreview but needs to be overloaded here
    function print_student_answer($userid, $return=false){
    }

    //--------------------------------------------------------------------------
    // The main view function
    function view() {
        global $USER, $CFG;

        $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        require_capability('mod/assignment:view', $context);
		$teacher = has_capability('mod/assignment:grade', $context);
		$criteriaList = get_records_list('assignment_criteria','assignment',$this->assignment->id,'ordernumber');
        $numberOfCriteria = 0;
		if(is_array($criteriaList)) {
            $criteriaList = array_values($criteriaList);
			$numberOfCriteria = count($criteriaList);
        }

        if($teacher && $numberOfCriteria==0) {
            redirect($CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::CRITERIA_FILE.'?id='.$this->cm->id.'&a='.$this->assignment->id,0);
            return;
        }

		$submission = $this->get_submission();
		$reviewsAllocated = get_records_select('assignment_review','assignment=\''.$this->assignment->id.'\' and reviewer=\''.$USER->id.'\' ORDER BY id ASC');
		if(is_array($reviewsAllocated)) {
			$reviewsAllocated = array_values($reviewsAllocated);
			$numberOfReviewsAllocated = count($reviewsAllocated);
		}
		else {
			$numberOfReviewsAllocated = 0;
		}
		$numberOfReviewsDownloaded = count_records('assignment_review','assignment',$this->assignment->id,'reviewer',$USER->id,'downloaded','1');
		$numberOfReviewsCompleted = count_records('assignment_review','assignment',$this->assignment->id,'reviewer',$USER->id,'complete','1');
        add_to_log($this->course->id, "assignment", "view", "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

        $this->view_header();

		//Determine what stage the student is up to and show progress
		if(!$teacher) {

			// Not yet submitted
			if(!$submission) {
				if($this->isopen()) {
					$this->print_progress_box('blueProgressBox','1',get_string('submit','peerreview'),get_string('submitbelow','peerreview'));
				}
				else {
					$this->print_progress_box('redProgressBox','1',get_string('submit','peerreview'),get_string('closedpastdue','peerreview'));
				}
				$this->print_progress_box('greyProgressBox','2',get_string('reviews','peerreview'),get_string('submitfirst','peerreview'));
				$this->print_progress_box('greyProgressBox','3',get_string('feedback','peerreview'),get_string('notavailable','peerreview'));
			}

			// Submitted
			else {
				$this->print_progress_box('greenProgressBox','1',get_string('submit','peerreview'),get_string('submitted','peerreview'));

				// Completing Reviews
				if($numberOfReviewsCompleted<2) {
					if($numberOfReviewsCompleted==1) {
						$this->print_progress_box('blueProgressBox','2',get_string('reviews','peerreview'),get_string('reviewsonemore','peerreview'));
					}
					else {
						if($numberOfReviewsAllocated==0) {
							$this->print_progress_box('blueProgressBox','2',get_string('reviews','peerreview'),get_string('reviewsnotallocated','peerreview'));
						}
						else {
							$this->print_progress_box('blueProgressBox','2',get_string('reviews','assignment_peerreview'),get_string('completereviewsbelow','assignment_peerreview'));
						}
					}
					$this->print_progress_box('greyProgressBox','3',get_string('feedback','assignment_peerreview'),get_string('notavailable','assignment_peerreview'));
				}

				// Viewing feedback
				else {
					$this->print_progress_box('greenProgressBox','2',get_string('reviews','assignment_peerreview'),get_string('reviewscomplete','assignment_peerreview'));
					if($submission->timemarked == 0) {
						$this->print_progress_box('blueProgressBox','3',get_string('feedback','assignment_peerreview'),get_string('marknotassigned','assignment_peerreview'));
					}
					else {
						$this->print_progress_box('greenProgressBox','3',get_string('feedback','assignment_peerreview'),get_string('markassigned','assignment_peerreview'));
					}
				}
			}
		}
    	$this->print_progress_box('end');

		// Completing Reviews
		if(!$teacher && $submission && $numberOfReviewsAllocated==2 && $numberOfReviewsCompleted<2) {
			print_box_start();

			// Allow review file to be downloaded
			if($numberOfReviewsDownloaded == $numberOfReviewsCompleted) {
                print_heading(get_string('reviewnumber','assignment_peerreview',$numberOfReviewsCompleted+1),"left");
				if(isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
					print_heading(get_string('gettheonlinetext','assignment_peerreview'),"left",3);
					echo '<a onclick="setTimeout(\'document.getElementById(\\\'continueButton\\\').disabled=false;\',3000);return openpopup(\'/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?a='.$this->assignment->id.'&id='.$this->cm->id.'&sesskey='.sesskey().'&view=peerreview\', \'window'.($numberOfReviewsCompleted+1).'\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);" target="window'.($numberOfReviewsCompleted+1).'" href="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?a='.$this->assignment->id.'&id='.$this->cm->id.'&view=peerreview">'.get_string('clicktoview','assignment_peerreview').'</a>';
					print_heading(get_string('continuetoreview','assignment_peerreview'),"left",3);
				}
				else {
					print_heading(get_string('getthedocument','assignment_peerreview'),"left",3);
					require_once($CFG->libdir.'/filelib.php');
					echo '<a onclick="setTimeout(\'document.getElementById(\\\'continueButton\\\').disabled=false;\',3000);return true;" href="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::DOWNLOAD_PEERREVIEW_FILE.'/'.self::FILE_PREFIX.($numberOfReviewsCompleted+1).'.'.$this->assignment->fileextension.'?a='.$this->assignment->id.'&id='.$this->cm->id.'&sesskey='.sesskey().'&forcedownload=1"><img class="icon" src="'.$CFG->pixpath.'/f/'.mimeinfo('icon', 'blah.'.$this->assignment->fileextension).'" alt="'.get_string('clicktodownload','assignment_peerreview').'" />'.get_string('clicktodownload','assignment_peerreview').'</a>';
					print_heading(get_string('continuetoreviewdocument','assignment_peerreview'),"left",3);
				}

                echo '<noscript>';
				echo '<a href="view.php?id='.$this->cm->id.'">'.get_string('continue','assignment_peerreview').'</a>';
                echo '</noscript>';
                echo '<script>';
                echo 'document.write(\'<input type="button" disabled id="continueButton" onclick="document.location=\\\'view.php?id='.$this->cm->id.'\\\'" value="'.get_string('continue','assignment_peerreview').'" />\');';
                echo '</script>';
                echo '<br />';

                // Show the assignment instructions but hidden
                echo $OUTPUT->spacer(array('height'=>30, 'width'=>30));
                echo '<p id="showDescription"><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'block\';document.getElementById(\'showDescription\').style.display=\'none\';">'.get_string('showdescription','assignment_peerreview').'</a></p>';
                echo '<div id="hiddenDescription" style="display:none;">';
                echo '<p><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'none\';document.getElementById(\'showDescription\').style.display=\'block\';">'.get_string('hidedescription','assignment_peerreview').'</a></p>';
                $this->view_intro();
                echo '</div>';
			}

			// Reviewing
			else {

				// Save review
				if($comment = clean_param(htmlspecialchars(optional_param('comment',NULL,PARAM_RAW)),PARAM_CLEAN)) {
                    print_heading(get_string('reviewnumber','assignment_peerreview',$numberOfReviewsCompleted+1));
					notify(get_string('savingreview','assignment_peerreview'),'notifysuccess');
					for($i=0; $i<$numberOfCriteria; $i++) {
						$criterionToSave = new Object;
						$criterionToSave->review = $reviewsAllocated[$numberOfReviewsCompleted]->id;
						$criterionToSave->criterion = $i;
						$criterionToSave->checked = optional_param('criterion'.$i,0,PARAM_BOOL);
						insert_record('assignment_review_criterion',$criterionToSave);
					}
					$reviewToUpdate = get_record('assignment_review','id',$reviewsAllocated[$numberOfReviewsCompleted]->id);
					$reviewToUpdate->reviewcomment = $comment;
					$reviewToUpdate->complete      = 1;
					$reviewToUpdate->timecompleted = time();
					$reviewToUpdate->timemodified  = $reviewToUpdate->timecompleted;
					update_record('assignment_review',$reviewToUpdate);

					// Send an email to student
					$subject = get_string('peerreviewreceivedsubject','assignment_peerreview');
					$linkToReview = $CFG->wwwroot.'/mod/assignment/view.php?id='.$this->cm->id;
                    $message = get_string('peerreviewreceivedmessage','assignment_peerreview')."\n\n".get_string('assignmentname','assignment').': '.$this->assignment->name."\n".get_string('course').': '.$this->course->fullname."\n\n";
					$messageText = $message.$linkToReview;
					$messageHTML = nl2br($message).'<a href="'.$linkToReview.'" target="_blank">'.get_string('peerreviewreceivedlinktext','assignment_peerreview').'</a>';
					$this->email_from_teacher($this->course->id, $reviewToUpdate->reviewee, $subject, $messageText, $messageHTML);

					redirect('view.php?id='.$this->cm->id, get_string('reviewsaved','assignment_peerreview'),1);
				}

				// Show review form
				else if($numberOfCriteria>0) {
					echo '<div style="position:relative;">';
                    print_heading(get_string('reviewnumber','assignment_peerreview',$numberOfReviewsCompleted+1),'left');
					echo '<div style="text-align:right;position:absolute;top:0;right:0">';
					if(isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
						echo '<a onclick="setTimeout(\'document.getElementById(\\\'continueButton\\\').disabled=false;\',3000);return openpopup(\'/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?a='.$this->assignment->id.'&id='.$this->cm->id.'&sesskey='.sesskey().'&view=peerreview\', \'window'.($numberOfReviewsCompleted+1).'\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);" target="window'.($numberOfReviewsCompleted+1).'" href="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?a='.$this->assignment->id.'&id='.$this->cm->id.'&view=peerreview">'.get_string('lostonlinetext','assignment_peerreview').'</a>';
					}
					else {
						require_once($CFG->libdir.'/filelib.php');
						echo '<a onclick="setTimeout(\'document.getElementById(\\\'continueButton\\\').disabled=false;\',3000);return true;" href="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::DOWNLOAD_PEERREVIEW_FILE.'/'.self::FILE_PREFIX.($numberOfReviewsCompleted+1).'.'.$this->assignment->fileextension.'?a='.$this->assignment->id.'&id='.$this->cm->id.'"><img class="icon" src="'.$CFG->pixpath.'/f/'.mimeinfo('icon', 'blah.'.$this->assignment->fileextension).'" alt="'.get_string('lostfile','assignment_peerreview').'" />'.get_string('lostfile','assignment_peerreview').'</a>';
					}
					echo '</div>';
					echo '<p id="showDescription"><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'block\';document.getElementById(\'showDescription\').style.display=\'none\';">'.get_string('showdescription','assignment_peerreview').'</a></p>';
					echo '<div id="hiddenDescription" style="display:none;">';
					echo '<p><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'none\';document.getElementById(\'showDescription\').style.display=\'block\';">'.get_string('hidedescription','assignment_peerreview').'</a></p>';
					$this->view_intro();
					echo '</div>';
					echo '<form action="view.php" method="post">';
					echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
					echo '<p>'.get_string('criteriainstructions','assignment_peerreview').'</p>';
					echo '<table style="width:99%;">';
                    $options = new object;
                    $options->para = false;
					foreach($criteriaList as $i=>$criterion) {
						echo '<tr'.($i%2==0?' class="evenCriteriaRow"':'').'><td class="criteriaCheckboxColumn"><input type="checkbox" name="criterion'.$criterion->ordernumber.'" id="criterion'.$criterion->ordernumber.'" /></td><td class="criteriaTextColumn"><label for="criterion'.$criterion->ordernumber.'">'.format_text(($criterion->textshownatreview!=''?$criterion->textshownatreview:$criterion->textshownwithinstructions),FORMAT_MOODLE,$options).'</label></td></tr>';
					}
					echo '</table>';
                    echo $OUTPUT->spacer(array('height'=>20, 'width'=>20));
					echo '<p>'.get_string('commentinstructions','assignment_peerreview').'</p>';
					echo '<textarea name="comment" id="comment" rows="5" style="width:99%;"></textarea>';
					echo '<input type="submit" value="'.get_string('savereview','assignment_peerreview').'" onclick="if(document.getElementById(\'comment\').value==\'\'){alert(\''.get_string('nocommentalert','assignment_peerreview').'\');document.getElementById(\'comment\').focus();return false;}">';
					echo '</form>';
					echo '</div>';
				}
				else {
					notify(get_string('nocriteriaset','assignment_peerreview'));
				}
			}
			print_box_end();
		}

        // For early submitters waiting for reviews to be allocated
        else if(!$teacher && $submission && $numberOfReviewsAllocated==0) {
			print_box_start();
            notify(get_string("poolnotlargeenough", "assignment_peerreview"),'notifysuccess','left');
			print_heading(get_string('yoursubmission','assignment_peerreview'),"left",2);
			echo '<table cellpadding="3">';
			if(isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
				echo '<tr><td><strong>'.get_string('submission','assignment_peerreview').': </strong></td><td>';
				link_to_popup_window($CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&sesskey='.sesskey().'&view=selfview');
				echo '</td></tr>';
			}
			else {
				require_once($CFG->libdir.'/filelib.php');
				$filearea = $this->file_area_name($USER->id);
				$files = get_directory_list($CFG->dataroot.'/'.$filearea, '', false);
				echo '<tr><td><strong>'.get_string('submittedfile','assignment_peerreview').': </strong></td><td><a href="'.get_file_url($filearea.'/'.$files[0], array('forcedownload'=>1)).'" ><img src="'.$CFG->pixpath.'/f/'.mimeinfo('icon', $files[0]).'" class="icon" alt="icon" />'.$files[0].'</a></td></tr>';
			}
			echo '<tr><td><strong>'.get_string('submittedtime','assignment_peerreview').': </strong></td><td>'.userdate($submission->timecreated,get_string('strftimedaydatetime')).'</td></tr>';
			echo '</table>';

            // Show the assignment instructions but hidden
            echo '<p id="showDescription"><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'block\';document.getElementById(\'showDescription\').style.display=\'none\';">'.get_string('showdescription','assignment_peerreview').'</a></p>';
			echo '<div id="hiddenDescription" style="display:none;">';
			echo '<p><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'none\';document.getElementById(\'showDescription\').style.display=\'block\';">'.get_string('hidedescription','assignment_peerreview').'</a></p>';
			$this->view_intro();
			echo '</div>';
			print_box_end();
        }

		// Feedback on submission and reviews of student
		else if(!$teacher && $submission && $numberOfReviewsCompleted==2) {
			print_box_start();

			// Find the reviews for this student
			$reviews = $this->get_reviews_of_student($USER->id);
			$numberOfReviewsOfThisStudent = 0;
			if(is_array($reviews)) {
				$numberOfReviewsOfThisStudent = count($reviews);
			}
			$status = $this->get_status($reviews,$numberOfCriteria);

            // Table at top of page
			echo '<table cellpadding="0" style="width:99%;"><tr><td style="width:50%;vertical-align:top;">';

			// Table about student submission
			print_heading(get_string('yoursubmission','assignment_peerreview'),"left",1);
			echo '<table cellpadding="3">';
			echo '<tr><td><strong>'.get_string('grade','assignment_peerreview').': </strong></td><td>'.($submission->timemarked==0?get_string('notavailable','assignment_peerreview'):$this->display_grade($submission->grade)).'</td></tr>';
			echo '<tr><td><strong>'.get_string('status').': </strong></td><td>';
			switch($status) {
				case self::FLAGGED:
				case self::CONFLICTING:
				case self::FLAGGEDANDCONFLICTING:
												  echo get_string('waitingforteacher','assignment_peerreview',$this->course->teacher); break;
				case self::LESSTHANTWOREVIEWS:    echo get_string('waitingforpeers','assignment_peerreview'); break;
				case self::CONCENSUS:             echo get_string('reviewconcensus','assignment_peerreview'); break;
				case self::OVERRIDDEN:            echo get_string('reviewsoverridden','assignment_peerreview',$this->course->teacher); break;
			}
			echo '</td></tr>';
			if(isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
				echo '<tr><td><strong>'.get_string('submission','assignment_peerreview').': </strong></td><td>';
				link_to_popup_window($CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&sesskey='.sesskey().'&view=selfview');
				echo '</td></tr>';
			}
			else {
				require_once($CFG->libdir.'/filelib.php');
				$filearea = $this->file_area_name($USER->id);
				$files = get_directory_list($CFG->dataroot.'/'.$filearea, '', false);
				echo '<tr><td><strong>'.get_string('submittedfile','assignment_peerreview').': </strong></td><td><a href="'.get_file_url($filearea.'/'.$files[0], array('forcedownload'=>1)).'" ><img src="'.$CFG->pixpath.'/f/'.mimeinfo('icon', $files[0]).'" class="icon" alt="icon" />'.$files[0].'</a></td></tr>';
			}
			echo '<tr><td><strong>'.get_string('submittedtime','assignment_peerreview').': </strong></td><td>'.userdate($submission->timecreated,get_string('strftimedaydatetime')).'</td></tr>';
			echo '</table>';
			echo '</td><td style="vertical-align:top;">';

			// Gather stats about student reviewing
            $reviewStats = $this->get_review_statistics();
            $reviewsByThisStudent = get_records_select('assignment_review','assignment=\''.$this->assignment->id.'\' AND reviewer=\''.$submission->userid.'\' AND complete=\'1\'');
            $numberOfReviewsByThisStudent = is_array($reviewsByThisStudent)?count($reviewsByThisStudent):0;
            $timeTakenReviewing = 0;
            $commentLength = 0;
            $criterionMatchesWithInstructor = 0;
            $comparableReviews = 0;
            $flags = 0;
            foreach($reviewsByThisStudent as $id=>$review) {
                $timeTakenReviewing += $review->timecompleted - $review->timedownloaded;
                $commentLength += strlen($review->reviewcomment);
                $teacherReviews = get_records_select('assignment_review','assignment=\''.$this->assignment->id.'\' AND reviewee=\''.$review->reviewee.'\' AND teacherreview=\'1\' AND complete=\'1\'','timecompleted DESC','*',0,1);
                if(is_array($teacherReviews) && count($teacherReviews)>0) {
                $comparableReviews++;
                    $teacherReviews = array_values($teacherReviews);
                    $teacherCriteria = array_values(get_records('assignment_review_criterion','review',$teacherReviews[0]->id,'criterion'));
                    $studentCriteria = array_values(get_records('assignment_review_criterion','review',$review->id,'criterion'));
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
			print_heading(get_string('yourreviewing','assignment_peerreview'),"left",1);
			echo '<table cellpadding="1">';
			echo '<tr><td><strong>'.get_string('completed','assignment_peerreview').': </strong></td><td><img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/tick.gif" style="vertical-align:middle;" />&nbsp;'.$numberOfReviewsByThisStudent.' '.get_string('completedlabel','assignment_peerreview',$this->assignment->var1).'</td></tr>';
			echo '<tr><td><strong>'.get_string('reviewtimetaken','assignment_peerreview').': </strong></td><td>';

            // Time taken
			if($reviewStats->numberOfReviews < self::REVIEW_FEEDBACK_MIN) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/questionMark.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('notenoughreviewstocompare','assignment_peerreview');
            }
            else if ($timeTakenReviewing/2 < self::MINIMAL_REVIEW_TIME || $timeTakenReviewing/2 < $reviewStats->reviewTimeOutlierLowerBoundary) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('shorterthanmost','assignment_peerreview');
            }
            else if ($timeTakenReviewing/2 > $reviewStats->reviewTimeOutlierUpperBoundary) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('longerthanmost','assignment_peerreview');
            }
            else {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/tick.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('good','assignment_peerreview');
            }
			echo '</td></tr>';

            // Comments made
			echo '<tr><td><strong>'.get_string('reviewcomments','assignment_peerreview').': </strong></td><td>';
			if($reviewStats->numberOfReviews < self::REVIEW_FEEDBACK_MIN) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/questionMark.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('notenoughreviewstocompare','assignment_peerreview');
            }
            else if ($commentLength/2 < self::MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength/2 < $reviewStats->commentLengthOutlierLowerBoundary) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('shorterthanmost','assignment_peerreview');
            }
            else if ($commentLength/2 > $reviewStats->commentLengthOutlierUpperBoundary) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('longerthanmost','assignment_peerreview');
            }
            else {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/tick.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('goodlength','assignment_peerreview');
            }
			echo '</td></tr>';

            // Accuracy
			echo '<tr><td><strong>'.get_string('reviewaccuracy','assignment_peerreview').': </strong></td><td>';
			if($comparableReviews < 1) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/questionMark.gif" style="vertical-align:middle;" />&nbsp;';
                print_string('notenoughmoderationstocompare','assignment_peerreview');
            }
            else {
                if ($criterionMatchesWithInstructor/($comparableReviews*$numberOfCriteria) < self::ACCURACY_REQUIRED) {
                    echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
                    print_string('poor','assignment_peerreview');
                }
                else {
                    echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/tick.gif" style="vertical-align:middle;" />&nbsp;';
                    print_string('good','assignment_peerreview');
                }
                echo ' ('.(int)($criterionMatchesWithInstructor/($comparableReviews*$numberOfCriteria)*100).'%)';
            }
            echo '</td></tr>';


			echo '<tr><td><strong>'.get_string('flags','assignment_peerreview').': </strong></td><td>';
            if ($flags>=1) {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            }
            else {
                echo '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/tick.gif" style="vertical-align:middle;" />&nbsp;';
            }
            echo get_string('flags'.$flags,'assignment_peerreview').'</td></tr>';
			echo '</table>';
			echo '</td></tr></table>';

			echo '<p id="showDescription"><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'block\';document.getElementById(\'showDescription\').style.display=\'none\';">'.get_string('showdescription','assignment_peerreview').'</a></p>';
			echo '<div id="hiddenDescription" style="display:none;">';
			echo '<p><a href="#null" onclick="document.getElementById(\'hiddenDescription\').style.display=\'none\';document.getElementById(\'showDescription\').style.display=\'block\';">'.get_string('hidedescription','assignment_peerreview').'</a></p>';
			$this->view_intro();
			echo '</div>';

			// If reviews are available, show to student
			if($reviews) {
				print_heading(get_string('reviewsofyoursubmission','assignment_peerreview'),"left",1);
				echo '<table width="99%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">';
                for($i=0; $i<$numberOfCriteria; $i++) {
					echo '<tr class="criteriaDisplayRow">';
					for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
						echo '<td class="criteriaCheckboxColumn" style="background:'.$this->REVIEW_COLOURS[$j%$this->NUMBER_OF_COLOURS].'"><input type="checkbox" disabled'.($reviews[$j]->{'checked'.$i}==1?' checked':'').' /></td>';

					}
                    $options = new object;
                    $options->para = false;
					echo '<td class="criteriaDisplayColumn">'.format_text(($criteriaList[$i]->textshownatreview!=''?$criteriaList[$i]->textshownatreview:$criteriaList[$i]->textshownwithinstructions),FORMAT_MOODLE,$options).'</td>';
					echo '</tr>';
				}
				$studentCount = 1;
				for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
					echo '<tr>';
					for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
						echo '<td class="criteriaCheckboxColumn" style="background:'.($j>$numberOfReviewsOfThisStudent-$i-1?$this->REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$this->NUMBER_OF_COLOURS]:$this->REVIEW_COLOURS[$j%$this->NUMBER_OF_COLOURS]).';">&nbsp;</td>';

					}
					echo '<td class="reviewCommentRow" style="background:'.$this->REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$this->NUMBER_OF_COLOURS].';">';

					echo '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
					echo '<tr class="reviewDetailsRow">';
					echo '<td><em>'.get_string('conductedby','assignment_peerreview').': '.($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview==1?$reviews[$numberOfReviewsOfThisStudent-$i-1]->firstname.' '.$reviews[$numberOfReviewsOfThisStudent-$i-1]->lastname.' ('.$this->course->teacher.')':$this->course->student.' '.$studentCount++).'</em></td>';
					echo '<td class="reviewDateColumn"><em>'.userdate($reviews[$numberOfReviewsOfThisStudent-$i-1]->timemodified,get_string('strftimedatetime')).'</em></td>';
					echo '</tr>';
					echo '<tr><td colspan="2"><div class="commentTextBox" style="background:'.$this->REVIEW_COMMENT_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%count($this->REVIEW_COMMENT_COLOURS)].';">'.format_string(stripslashes($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment)).'</div></td></tr>';

					if($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview!=1) {
						echo '<tr class="reviewDetailsRow"><td colspan="2"><em>';
						echo $reviews[$numberOfReviewsOfThisStudent-$i-1]->flagged==1?get_string('flagprompt1','assignment_peerreview',$this->course->teacher).' ':get_string('flagprompt2','assignment_peerreview').' ';
						$flagToggleURL = 'type/peerreview/'.self::TOGGLE_FLAG_FILE.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&r='.$reviews[$numberOfReviewsOfThisStudent-$i-1]->review.'&sesskey='.sesskey();
						echo '<a href="'.$flagToggleURL.'" id="flag'.($numberOfReviewsOfThisStudent-$i-1).'">';
						echo $reviews[$numberOfReviewsOfThisStudent-$i-1]->flagged==1?get_string('flaglink1','assignment_peerreview').'&nbsp;<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/flagRed.gif">':get_string('flaglink2','assignment_peerreview').'&nbsp;<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/flagGreen.gif">';
						echo '</a>';
						echo '</em></td></tr>';
					}
					echo '</table>';
					echo '</td>';
					echo '</tr>';

                    // Record that the review has been viewed by the student
                    if($reviews[$numberOfReviewsOfThisStudent-$i-1]->timefirstviewedbyreviewee==0) {
                        set_field('assignment_review', 'timefirstviewedbyreviewee', time(), 'id', $reviews[$numberOfReviewsOfThisStudent-$i-1]->review);
                    }
                    set_field('assignment_review', 'timelastviewedbyreviewee', time(), 'id', $reviews[$numberOfReviewsOfThisStudent-$i-1]->review);
                    set_field('assignment_review', 'timesviewedbyreviewee', $reviews[$numberOfReviewsOfThisStudent-$i-1]->timesviewedbyreviewee+1, 'id', $reviews[$numberOfReviewsOfThisStudent-$i-1]->review);
				}
				echo '</table>';
			}
			else {
				echo '<p>'.get_string('noreviews','assignment_peerreview');
			}

			print_box_end();
		}

        // First page with description and criteria
		else {
			// Show description
			$this->view_intro();

			// Show criteria
			print_box_start();
            echo '<a name="criteria"></a>';
            print_heading(get_string('criteria','assignment_peerreview'),'left');
			if (has_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE,$this->cm->id))) {
				echo '<p><a href="type/peerreview/'.self::CRITERIA_FILE.'?id='.$this->cm->id.'&a='.$this->assignment->id.'">'.get_string('setcriteria', 'assignment_peerreview').'</a></p>';
                print_heading(get_string('criteriabeforesubmission','assignment_peerreview'),'left',3);
			}
			if($numberOfCriteria>0) {
				echo '<table style="width:99%;">';
                $options = new object;
                $options->para = false;
				foreach($criteriaList as $i=>$criterion) {
					echo '<tr '.($i%2==0?'class="evenCriteriaRow"':'').'><td class="criteriaCheckboxColumn"><input type="checkbox" checked disabled /></td><td class="criteriaTextColumn">'.format_text($criterion->textshownwithinstructions,FORMAT_MOODLE,$options).'</td></tr>';
				}
				echo '</table>';
                if (has_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE,$this->cm->id))) {
                    print_heading(get_string('criteriaaftersubmission','assignment_peerreview'),'left',3);
                    echo '<table style="width:99%;">';
                    foreach($criteriaList as $i=>$criterion) {
    					echo '<tr '.($i%2==0?'class="evenCriteriaRow"':'').'><td class="criteriaCheckboxColumn"><input type="checkbox" checked disabled /></td><td class="criteriaTextColumn">'.format_text(($criteriaList[$i]->textshownatreview!=''?$criteriaList[$i]->textshownatreview:$criteriaList[$i]->textshownwithinstructions),FORMAT_MOODLE,$options).'</td></tr>';
                    }
                    echo '</table>';
                }
			}
			else {
				notify(get_string('nocriteriaset','assignment_peerreview'));
			}
			print_box_end();

			$this->view_dates();

			// With peer review teachers can grade but not submit (not here)
			if (has_capability('mod/assignment:submit', $context) && !$teacher && $this->isopen() && !$submission) {
				$this->view_upload_form();
			}
			else if(!$this->isopen()) {
				print_string("notopen","assignment_peerreview");
			}
		}

        $this->view_footer();
    }

    //--------------------------------------------------------------------------
    // Shows the assignment description
	function view_intro() {
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;

        print_box_start();
        echo format_text($this->assignment->description, $this->assignment->format, $formatoptions);
        print_box_end();
    }

    //--------------------------------------------------------------------------
    // After marking in a pop-up window, this javascript updates the submission listing
	function update_main_listing($submission) {
        global $SESSION, $CFG;
        $perpage = get_user_preferences('assignment_perpage', 10);
        $moderationtarget = get_user_preferences('assignment_moderationtarget', 0);

        $output = '';

        /// Run some Javascript to try and update the parent page
        $output .= '<script type="text/javascript">'."\n<!--\n";

        if (empty($SESSION->flextable['mod-assignment-submissions']->collapse['moderations'])) {
			$moderationCountSQL = 'SELECT count(r.id) FROM '.$CFG->prefix.'assignment a, '.$CFG->prefix.'assignment_review r WHERE a.course='.$this->course->id.' AND a.id=r.assignment AND r.teacherreview=1 AND r.reviewee=\''.$submission->userid.'\'';
			$moderationCount = count_records_sql($moderationCountSQL);
			$moderations = ($moderationCount<$moderationtarget)?'<span class="errorStatus">'.$moderationCount.'</span>':$moderationCount;
            $output .= 'if(opener.document.getElementById(\'mo'.$submission->userid.'\')) {'."\n";
			$output .= '    opener.document.getElementById(\'mo'.$submission->userid.'\').innerHTML=\''.$moderations."';\n";
            $output .= "}\n";
        }

		$reviewsOfThisStudent = $this->get_reviews_of_student($submission->userid);
		$criteriaList = get_records_list('assignment_criteria','assignment',$this->assignment->id,'ordernumber');
		$numberOfCriteria = 0;
		if(is_array($criteriaList)) {
            $criteriaList = array_values($criteriaList);
			$numberOfCriteria = count($criteriaList);
        }
		$statusCode = $this->get_status($reviewsOfThisStudent,$numberOfCriteria);

        if (empty($SESSION->flextable['mod-assignment-submissions']->collapse['status'])) {
            $output .= 'if(opener.document.getElementById(\'st'.$submission->userid.'\')) {'."\n";
			$output .= '    opener.document.getElementById(\'st'.$submission->userid.'\').innerHTML=\''.addslashes($this->print_status($statusCode,true))."';\n";
            $output .= "}\n";
        }

        if (empty($SESSION->flextable['mod-assignment-submissions']->collapse['seedoreviews'])) {
            $output .= 'if(opener.document.getElementById(\'seOutline'.$submission->userid.'\')) {'."\n";
            $output .= '    opener.document.getElementById(\'seOutline'.$submission->userid.'\').setAttribute(\'class\',\'s'.($statusCode<=3?'0':'1')."');\n";
            $output .= "}\n";
        }

		$numberOfReviewsByThisStudent = count_records('assignment_review','assignment',$this->assignment->id,'reviewer',$submission->userid,'complete','1');
		$suggestedMarkToDisplay = $this->get_marks($reviewsOfThisStudent,$criteriaList,$numberOfReviewsByThisStudent,$this->assignment->var1);
        if (empty($SESSION->flextable['mod-assignment-submissions']->collapse['suggestedmark'])) {
            $output .= 'if(opener.document.getElementById(\'gvalue'.$submission->userid.'\')) {'."\n";
            $output .= '    opener.document.getElementById(\'gvalue'.$submission->userid.'\').value=\''.$suggestedMarkToDisplay."';\n";
            $output .= "}\n";
        }

        $output .= "\n-->\n</script>";

        return $output;
    }

    //--------------------------------------------------------------------------
    // Directs calls from submissions.php to single pop-up window or submissions list
	function submissions($mode) {
        global $CFG, $USER;

        switch ($mode) {
            case 'grade':                         // We are in a popup window grading
                if ($submission = $this->process_feedback()) {
                    //IE needs proper header with encoding
                    print_header(get_string('feedback', 'assignment').':'.format_string($this->assignment->name));
                    print_heading(get_string('changessaved'));
                    print $this->update_main_listing($submission);
                }
                close_window();
                break;

            case 'single':                        // We are in a popup window displaying submission
                $this->display_submission();
                break;

            case 'all':                          // Main window, display everything
                $this->display_submissions();
                break;

            case 'next':
                /// We are currently in pop up, but we want to skip to next one without saving.
                ///    This turns out to be similar to a single case
                /// The URL used is for the next submission.

                $this->display_submission();
                break;

            case 'saveandnext':
                ///We are in pop up. save the current one and go to the next one.
                //first we save the current changes
                if ($submission = $this->process_feedback()) {
                    $extra_javascript = $this->update_main_listing($submission);
                }

                //then we display the next submission
                $this->display_submission($extra_javascript);
                break;

            default:
                echo "something seriously is wrong!!";
                break;
        }
    }

    //--------------------------------------------------------------------------
    // Outputs the list of submissions with various details
    function display_submissions($message='') {
        global $CFG, $db, $USER;
        require_once($CFG->libdir.'/gradelib.php');

        $CFG->stylesheets[] = $CFG->wwwroot . '/mod/assignment/type/peerreview/'.self::STYLES_FILE;

		// Update preferences
        if (isset($_POST['updatepref'])){
            $perpage = optional_param('perpage', 20, PARAM_INT);
            $perpage = ($perpage <= 0) ? 20 : $perpage ;
            set_user_preference('assignment_perpage', $perpage);
            $moderationtarget = optional_param('moderationtarget', 0, PARAM_INT);
            $moderationtarget = ($moderationtarget <= 0) ? 0 : $moderationtarget ;
            set_user_preference('assignment_moderationtarget', $moderationtarget);
        }

		// Get preferences
        $perpage         = get_user_preferences('assignment_perpage', 10);
        $moderationtarget = get_user_preferences('assignment_moderationtarget', 0);

		// Some shortcuts to make the code read better
        $grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id);
        $course       = $this->course;
        $assignment   = $this->assignment;
        $cm           = $this->cm;
        $context      = get_context_instance(CONTEXT_MODULE, $cm->id);
        $page         = optional_param('page', 0, PARAM_INT);

        $reviewStats = $this->get_review_statistics();

		// Log this view
        add_to_log($course->id, 'assignment', 'view submission', 'submissions.php?id='.$this->assignment->id, $this->assignment->id, $this->cm->id);

		// Print header and navigation breadcrumbs
        $navigation = build_navigation($this->strsubmissions, $this->cm);
        print_header_simple(format_string($this->assignment->name,true), "", $navigation,
                '', '', true, update_module_button($cm->id, $course->id, $this->strassignment), navmenu($course, $cm));

        $this->print_peerreview_tabs('submissions');

		// Print optional message
        if (!empty($message)) {
            echo $message;   // display messages here if any
        }

		// Check to see if groups are being used in this assignment
	    // find out current groups mode
        // $groupmode = groups_get_activity_groupmode($cm);
        // $currentgroup = groups_get_activity_group($cm, true);
        // groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/assignment/submissions.php?id=' . $cm->id);

		// Help on review process
		echo '<div style="text-align:center;margin:0 0 10px 0;">';
		helpbutton('reviewallocation', get_string('reviewallocation','assignment_peerreview'), 'assignment/type/peerreview', true,true);
		echo '</div>';

        // Get all ppl that are allowed to submit assignments
        // if ($users = get_users_by_capability($context, 'mod/assignment:submit', 'u.id', '', '', '', $currentgroup, '', false)) {
        if ($users = get_users_by_capability($context, 'mod/assignment:submit', 'u.id')) {
            $users = array_keys($users);
        }

        // Filter out teachers
        if ($users && $teachers = get_users_by_capability($context, 'mod/assignment:grade', 'u.id')) {
            $users = array_diff($users, array_keys($teachers));
        }

		// Warn if class is too small
		if(count($users) < 5) {
			notify(get_string('numberofstudentswarning','assignment_peerreview'));
		}

        // if groupmembersonly used, remove users who are not in any group
        // if ($users and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
            // if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                // $users = array_intersect($users, array_keys($groupingusers));
            // }
        // }

		// Create the table to be shown
        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-assignment-peerreview-submissions');
        $tablecolumns = array('picture', 'fullname', 'submitted', 'reviews', 'moderations', 'status', 'seedoreviews', 'suggestedmark','finalgrade');
        $table->define_columns($tablecolumns);
        $tableheaders = array('',
                              get_string('fullname'),
                              get_string('submission','assignment_peerreview').helpbutton('submission',get_string('submission','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true),
                              get_string('reviewsbystudent','assignment_peerreview').helpbutton('reviewsbystudent',get_string('reviewsbystudent','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true),
                              get_string('moderationstitle','assignment_peerreview').helpbutton('moderationtarget',get_string('moderationtarget','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true),
                              get_string('status').helpbutton('status',get_string('status','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true),
                              get_string('seedoreviews','assignment_peerreview').helpbutton('seedoreviews',get_string('seedoreviews','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true),
                              get_string('suggestedgrade','assignment_peerreview').helpbutton('suggestedgrade',get_string('suggestedgrade','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true),
                              get_string('finalgrade', 'assignment_peerreview').helpbutton('finalgrade',get_string('finalgrade','assignment_peerreview'),'assignment/type/peerreview/',true,false,'',true));
        $table->define_headers($tableheaders);
        // $table->define_baseurl($CFG->wwwroot.'/mod/assignment/submissions.php?id='.$this->cm->id.'&amp;currentgroup='.$currentgroup);
        $table->define_baseurl($CFG->wwwroot.'/mod/assignment/submissions.php?id='.$this->cm->id);
        // $table->sortable(true, 'submitted');
        $table->sortable(true);
        $table->collapsible(true);
        // $table->initialbars(true);
        $table->initialbars(false);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('submitted', 'submitted');
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
        $table->column_style('submitted','text-align','left');
        $table->column_style('finalgrade','text-align','center');

		$table->no_sorting('picture');
		$table->no_sorting('reviews');
		$table->no_sorting('moderations');
		$table->no_sorting('status');
		$table->no_sorting('seedoreviews');
		$table->no_sorting('suggestedmark');
		$table->no_sorting('finalgrade');

        $table->setup();

        if (empty($users)) {
            print_heading(get_string('nosubmitusers','assignment'));
            return true;
        }

		// Construct the SQL
        if ($where = $table->get_sql_where()) {
            $where .= ' AND ';
        }
        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt,
                          s.id AS submissionid, s.grade,
                          s.timecreated as submitted, s.timemarked ';
        $sql = 'FROM '.$CFG->prefix.'user u '.
               'LEFT JOIN '.$CFG->prefix.'assignment_submissions s ON u.id=s.userid AND s.assignment='.$this->assignment->id.' '.
               'WHERE '.$where.'u.id IN ('.implode(',',$users).') ';
        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
            $sort = str_replace('submitted', 'COALESCE(submitted,2147483647)', $sort);
        }
        else {
            $sort = 'ORDER BY COALESCE(submitted,2147483647) ASC, submissionid ASC, u.lastname ASC';
        }

        $table->pagesize($perpage, count($users));

        ///offset used to calculate index of student in that particular query, needed for the pop up to know who's next
        $offset = $page * $perpage;

        $strupdate = get_string('update');
        $strgrade  = get_string('grade');
        $grademenu = make_grades_menu($this->assignment->grade);

		// Get the criteria
		$criteriaList = get_records_list('assignment_criteria','assignment',$this->assignment->id,'ordernumber');
		$numberOfCriteria = 0;
		if(is_array($criteriaList)) {
            $criteriaList = array_values($criteriaList);
			$numberOfCriteria = count($criteriaList);
        }
        if (($ausers = get_records_sql($select.$sql.$sort, $table->get_page_start(), $table->get_page_size())) !== false) {
//            $grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id, array_keys($ausers));
            foreach ($ausers as $auser) {
  //              $final_grade = $grading_info->items[0]->grades[$auser->id];

				// Calculate user status
                $auser->status = $auser->timemarked > 0;
                $picture = print_user_picture($auser, $course->id, $auser->picture, false, true);
				$studentName = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$auser->id.'&course='.$this->course->id.'">'.fullname($auser).'</a>';

				// If submission has been made
                if (!empty($auser->submissionid)) {
					$filearea = $this->file_area_name($auser->id);
					$fileLink = '';
					if (isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
						$url = '/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&userid='.$auser->id.'&sesskey='.sesskey().'&view=moderation';
						$fileLink .= '<a href="'.$CFG->wwwroot.$url.'" target="_blank" onclick="return openpopup(\''.$url.'\',\'\',\'menubar=0,location=0,scrollbars,resizable,width=500,height=400\');" title="'.get_string('clicktoview','assignment_peerreview').'"><img src="'.$CFG->pixpath.'/f/html.gif" /></a>';
					}
					else {
						$basedir = $this->file_area($auser->id);
						if ($files = get_directory_list($basedir)) {
							require_once($CFG->libdir.'/filelib.php');
							foreach ($files as $key => $file) {
								$icon = mimeinfo('icon', $file);
								$ffurl = get_file_url("$filearea/$file", array('forcedownload'=>1));
								$fileLink .= '<a href="'.$ffurl.'" title="'.get_string('clicktodownload','assignment_peerreview').'"><img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" /></a>';
							}
						}
					}

					$submitted = '<div class="files" style="display:inline;">'.$fileLink.'</div><div style="display:inline;" id="tt'.$auser->id.'">'.userdate($auser->submitted,get_string('strftimeintable','assignment_peerreview')).'</div>';
					$submitted .= ' <a href="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::RESUBMIT_FILE.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&userid='.$auser->id.'&sesskey='.sesskey().'">('.get_string('resubmitlabel','assignment_peerreview').')</a>';

					// Reviews by student
					$numberOfReviewsByThisStudent = 0;
					if($reviewsByThisStudent = get_records_select('assignment_review','assignment=\''.$this->assignment->id.'\' AND reviewer=\''.$auser->id.'\' AND complete=\'1\'')) {
						$numberOfReviewsByThisStudent = count($reviewsByThisStudent);
						$reviewsByThisStudent = array_values($reviewsByThisStudent);

						$reviews  = '<div style="text-align:center;" id="re'.$auser->id.'">';
						for($i=0; $i<$numberOfReviewsByThisStudent; $i++) {
							$reviews .= '<span id="rev'.$reviewsByThisStudent[$i]->id.'"  style="padding:5px 2px;">';
							$popup_url = '/mod/assignment/submissions.php?id='.$this->cm->id. '&amp;userid='.$reviewsByThisStudent[$i]->reviewee.'&amp;mode=single&amp;offset=-1';
                            $buttonText = ''.($i+1);
                            $timeTakenReviewing = $reviewsByThisStudent[$i]->timecompleted - $reviewsByThisStudent[$i]->timedownloaded;
                            $commentLength = strlen($reviewsByThisStudent[$i]->reviewcomment);
                            if(
                                ($timeTakenReviewing < self::MINIMAL_REVIEW_TIME || $timeTakenReviewing < $reviewStats->reviewTimeOutlierLowerBoundary) &&
                                ($commentLength < self::MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength < $reviewStats->commentLengthOutlierLowerBoundary)
                            ) {
                                $buttonText .= '&nbsp;?';
                            }
							$reviews .= element_to_popup_window ('button', $popup_url, 'grade'.$auser->id, $buttonText, 600, 780, $buttonText, 'none', true, 'user'.$auser->id.'rev'.$i);
							$reviews .= '</span>';
							// $reviews .= $i<$numberOfReviewsByThisStudent-1?', ':'';
						}
						$reviews .= '</div>';
						$reviews .= '<script>';
						for($i=0; $i<$numberOfReviewsByThisStudent; $i++) {
							$reviews .= 'document.getElementById(\'user'.$auser->id.'rev'.$i.'\').setAttribute(\'onmouseover\',\'document.getElementById("se'.$reviewsByThisStudent[$i]->reviewee.'").style.background="#ff9999";\');';
							$reviews .= 'document.getElementById(\'user'.$auser->id.'rev'.$i.'\').setAttribute(\'onmouseout\',\'document.getElementById("se'.$reviewsByThisStudent[$i]->reviewee.'").style.background="transparent";\');';
						}
						$reviews .= '</script>';
					}
					else {
						$reviews       = '<div id="re'.$auser->id.'">&nbsp;</div>';
                    }

					// Reviews of student
					$reviewsOfThisStudent = $this->get_reviews_of_student($auser->id);
					$numberOfReviewsOfThisStudent = 0;
					if(is_array($reviewsOfThisStudent)) {
						$numberOfReviewsOfThisStudent = count($reviewsOfThisStudent);
					}

					$statusCode = $this->get_status($reviewsOfThisStudent,$numberOfCriteria);
                    $status  = '<div id="st'.$auser->id.'">'.$this->print_status($statusCode,true).'</div>';

					$buttontext = get_string('review','assignment_peerreview');
					$popup_url = '/mod/assignment/submissions.php?id='.$this->cm->id
							   . '&amp;userid='.$auser->id.'&amp;mode=single'.'&amp;offset='.$offset;
					$button = element_to_popup_window ('button', $popup_url, 'grade'.$auser->id, $buttontext, 600, 780, $buttontext, 'none', true, 'reviewbutton'.$auser->id);
					$seedoreviews  = '<div id="se'.$auser->id.'" style="text-align:center;padding:5px 0;"><span  id="seOutline'.$auser->id.'" class="s'.($statusCode<=3?'0':'1').'" style="padding:4px 1px;">'.$button.'</span></div>';
					$seedoreviews .= '<script>';
					$seedoreviews .= 'document.getElementById(\'reviewbutton'.$auser->id.'\').setAttribute(\'onmouseover\',\'';
					for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
						$seedoreviews .= 'buttonHighlight=document.getElementById("rev'.$reviewsOfThisStudent[$i]->review.'"); if(buttonHighlight) buttonHighlight.style.background="#ff9999";';
					}
					$seedoreviews .= '\');';
					$seedoreviews .= 'document.getElementById(\'reviewbutton'.$auser->id.'\').setAttribute(\'onmouseout\',\'';
					for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
						$seedoreviews .= 'buttonHighlight=document.getElementById("rev'.$reviewsOfThisStudent[$i]->review.'"); if(buttonHighlight) buttonHighlight.style.background="transparent";';
					}
					$seedoreviews .= '\');';
					$seedoreviews .= '</script>';

					// Suggest mark
					$suggestedmark = '<div style="text-align:center;" id="su'.$auser->id.'">';
					$suggestedMarkToDisplay = $this->get_marks($reviewsOfThisStudent,$criteriaList,$numberOfReviewsByThisStudent,$this->assignment->var1);
					$suggestedmark .= '<input type="text" size="4" id="gvalue'.$auser->id.'" value="'.$suggestedMarkToDisplay.'" />';
					$suggestedmark .= '<input type="button" value="'.get_string('set','assignment_peerreview').'" onclick="mark=parseInt(document.getElementById(\'gvalue'.$auser->id.'\').value); if(isNaN(mark)) {alert(\''.get_string('gradenotanumber','assignment_peerreview').'\'); return false;} else {popup_url=\'/mod/assignment/type/peerreview/'.self::SET_MARK_FILE.'?id='.$this->cm->id.'&amp;a='.$this->assignment->id.'&amp;sesskey='.sesskey().'&amp;userid='.$auser->id.'&amp;mark=\'+mark; return openpopup(popup_url, \'grade5\', \'menubar=0,location=0,scrollbars,resizable,width=400,height=300\', 0);}" />';

					$suggestedmark .= '</div>';

					// Final grade
                    if ($auser->timemarked > 0) {
                        // if ($final_grade->locked or $final_grade->overridden) {
                            // $grade = '<div id="g'.$auser->id.'">'.$final_grade->str_grade.'</div>';
                        // }
						// else {
                            $grade = '<div id="g'.$auser->id.'">'.$this->display_grade($auser->grade).'</div>';
                        // }
                    }
					else {
						$grade = '<div id="g'.$auser->id.'">'.get_string('notset','assignment_peerreview').'</div>';
                    }
				}

				// No submission made yet
				else {
                    $submitted     = '<div id="tt'.$auser->id.'">&nbsp;</div>';
                    $reviews       = '<div id="re'.$auser->id.'">&nbsp;</div>';
                    $status        = '<div id="st'.$auser->id.'">&nbsp;</div>';
                    $seedoreviews  = '<div id="se'.$auser->id.'">&nbsp;</div>';
                    $suggestedmark = '<div id="su'.$auser->id.'">&nbsp;</div>';
					$grade         = '<div id="g'.$auser->id.'">-</div>';
                }

				$moderationCountSQL = 'SELECT count(r.id) FROM '.$CFG->prefix.'assignment a, '.$CFG->prefix.'assignment_review r WHERE a.course='.$course->id.' AND a.id=r.assignment AND r.teacherreview=1 AND r.reviewee=\''.$auser->id.'\'';
				$moderationCount = count_records_sql($moderationCountSQL);
				$moderations   = '<div id="mo'.$auser->id.'" style="text-align:center;">'.($moderationCount<$moderationtarget?'<span class="errorStatus">'.$moderationCount.'</span>':$moderationCount).'</div>';

                // $finalgrade = '<span id="finalgrade_'.$auser->id.'">'.$final_grade->str_grade.'</span>';

				// Add the row to the table
                $row = array($picture, $studentName, $submitted, $reviews, $moderations, $status, $seedoreviews, $suggestedmark, $grade);
                $table->add_data($row);
                $offset++;
            }
        }

        /// Print quickgrade form around the table
        $table->print_html();  /// Print the whole table

        /// Mini form for setting user preference
        echo '<div style="margin:5px 10px;">';
        echo '<table id="optiontable" align="right">';
        echo '<tr><td colspan="2" align="right">';
        echo '<form id="options" action="type/peerreview/'.self::MASS_MARK_FILE.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&sesskey='.sesskey().'" method="post">';

        echo '<input type="submit" value="'.get_string('massmark','assignment_peerreview').'" />';
		helpbutton('massmark',get_string('massmark','assignment_peerreview'),'assignment/type/peerreview/');
        echo '</form>';
        echo '<br />';
		echo '</td></tr>';
        echo '<form id="options" action="submissions.php?id='.$this->cm->id.'" method="post">';
        echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
        echo '<tr align="right"><td>';
        echo '<label for="perpage">'.get_string('pagesize','assignment').'</label>';
        echo ':</td>';
        echo '<td>';
        echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
        helpbutton('pagesize', get_string('pagesize','assignment'), 'assignment');
        echo '</td></tr>';
        echo '<tr align="right"><td>';
        echo '<label for="moderationtarget">'.get_string('moderationtarget','assignment_peerreview').'</label>';
        echo ':</td>';
        echo '<td>';
        echo '<input type="text" id="moderationtarget" name="moderationtarget" size="1" value="'.$moderationtarget.'" />';
        helpbutton('moderationtarget', get_string('moderationtargetwhy','assignment_peerreview'), 'assignment/type/peerreview');
        echo '</td></tr>';
        echo '<tr>';
        echo '<td colspan="2" align="right">';
        echo '<input type="submit" value="'.get_string('savepreferences').'" />';
        echo '</form>';
        echo '</td></tr></table>';
        echo '</div>';
        ///End of mini form
        print_footer($this->course);
    }

    //--------------------------------------------------------------------------
    // A single submission shown in a pop-up marking window
	function display_submission($extra_javascript = '') {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/tablelib.php');

        $CFG->stylesheets[] = $CFG->wwwroot . '/mod/assignment/type/peerreview/'.self::STYLES_FILE;

        $userid = required_param('userid', PARAM_INT);
        $offset = required_param('offset', PARAM_INT);//offset for where to start looking for student.

        if (!$user = get_record('user', 'id', $userid)) {
            error('No such user!');
        }

        if (!$submission = $this->get_submission($user->id)) {
            $submission = $this->prepare_new_submission($userid);
        }
        if ($submission->timemodified > $submission->timemarked) {
            $subtype = 'assignmentnew';
        } else {
            $subtype = 'assignmentold';
        }

 		// Get the criteria
		$criteriaList = get_records_list('assignment_criteria','assignment',$this->assignment->id,'ordernumber');
		$numberOfCriteria = 0;
		if(is_array($criteriaList)) {
            $criteriaList = array_values($criteriaList);
			$numberOfCriteria = count($criteriaList);
        }

		$reviews = $this->get_reviews_of_student($user->id);
		$numberOfReviewsOfThisStudent = 0;
		if(is_array($reviews)) {
			$numberOfReviewsOfThisStudent = count($reviews);
		}
        $reviewStats = $this->get_review_statistics();

        $grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id, array($user->id));
        $disabled = $grading_info->items[0]->grades[$userid]->locked || $grading_info->items[0]->grades[$userid]->overridden;

        $course     = $this->course;
        $assignment = $this->assignment;
        $cm         = $this->cm;
        $context    = get_context_instance(CONTEXT_MODULE, $cm->id);

        /// Get all ppl that can submit assignments
        $currentgroup = groups_get_activity_group($cm);
        if ($users = get_users_by_capability($context, 'mod/assignment:submit', 'u.id', '', '', '', $currentgroup, '', false)) {
            $users = array_keys($users);
        }

        // if groupmembersonly used, remove users who are not in any group
        if ($users and !empty($CFG->enablegroupings) and $cm->groupmembersonly) {
            if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                $users = array_intersect($users, array_keys($groupingusers));
            }
        }

        $nextid = 0;

        if ($users && $offset>=0) {
            $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt,
                              s.id AS submissionid, s.grade, s.submissioncomment,
                              s.timecreated as submitted, s.timemarked as timemarked ';
            $sql = 'FROM '.$CFG->prefix.'user u '.
                   'LEFT JOIN '.$CFG->prefix.'assignment_submissions s ON u.id = s.userid
                                                                      AND s.assignment = '.$this->assignment->id.' '.
                   'WHERE u.id IN ('.implode(',', $users).') ';

            if ($sort = flexible_table::get_sql_sort('mod-assignment-peerreview-submissions')) {
                $sort = ' ORDER BY '.$sort;
                $sort = str_replace('submitted', 'COALESCE(submitted,2147483647)', $sort);
            }
            else {
                $sort = 'ORDER BY COALESCE(submitted,2147483647) ASC, submissionid ASC, u.lastname ASC';
            }

            // Find the next user who has submitted
            if (($auser = get_records_sql($select.$sql.$sort, $offset+1)) !== false) {
            
                $nextuser = array_shift($auser);
                $offset++;
                
                while($nextuser && !$nextuser->submitted) {
                    $nextuser = array_shift($auser);
                    $offset++;
                }

                // Calculate user status
                if($nextuser && $nextuser->submitted) {
                    $nextuser->status = ($nextuser->timemarked > 0);
                    $nextid = $nextuser->id;
                }
            }
        }

        print_header(get_string('feedback', 'assignment').':'.fullname($user, true).':'.format_string($this->assignment->name));

        // Print any extra javascript needed for saveandnext
        print $extra_javascript;

        // Some javascript to help with setting up >.>
        echo '<script type="text/javascript">'."\n";
        echo 'function setNext(){'."\n";
        echo '    document.getElementById(\'submitform\').mode.value=\'next\';'."\n";
        echo '    document.getElementById(\'submitform\').userid.value="'.$nextid.'";'."\n";
        echo '}'."\n";

        echo 'function saveNext(){'."\n";
        echo '    document.getElementById(\'submitform\').mode.value=\'saveandnext\';'."\n";
        echo '    document.getElementById(\'submitform\').userid.value="'.$nextid.'";'."\n";
        echo '    document.getElementById(\'submitform\').saveuserid.value="'.$userid.'";'."\n";
//        echo 'document.getElementById(\'submitform\').menuindex.value = document.getElementById(\'submitform\').grade.selectedIndex;'."\n";
        echo '}'."\n";

        echo 'function setSavePrev(){'."\n";
        echo '    document.getElementById(\'submitform\').savePrev.value=\'1\';'."\n";
        echo '}'."\n";

        echo 'function allowSavePrev(){'."\n";
        echo '    document.getElementById(\'savepreexistingonly\').disabled=false;';
        echo '}'."\n";

        echo 'function allowSaveNew(){'."\n";
        echo '    document.getElementById(\'savenew\').disabled=false;';
        echo '    if(document.getElementById(\'saveandnext\')) {'."\n";
        echo '        document.getElementById(\'saveandnext\').disabled=false;'."\n";
        echo '    }'."\n";
        echo '}'."\n";

        echo '</script>'."\n";
        echo '<table cellspacing="0" class="feedback '.$subtype.'" style="width:99%;">';

        // Start of student info row
        echo '<tr>';
        echo '<td class="picture user">';
        print_user_picture($user, $this->course->id, $user->picture);
        echo '</td>';
        echo '<td class="topic">';
        echo '<div class="from">';
        echo '<div class="fullname">'.fullname($user, true).'</div>';
        if ($submission->timemodified) {
            echo '<div class="time">'.get_string('submitted','assignment_peerreview').': '.userdate($submission->timecreated).
                                     $this->display_lateness($submission->timecreated).'</div>';
        }
        echo '<div class="reviewDetailsRow">';
		$moderationCountSQL = 'SELECT count(r.id) FROM '.$CFG->prefix.'assignment a, '.$CFG->prefix.'assignment_review r WHERE a.course='.$course->id.' AND a.id=r.assignment AND r.teacherreview=1 AND r.reviewee=\''.$user->id.'\'';
		$moderationCount = count_records_sql($moderationCountSQL);
        $moderationtarget = get_user_preferences('assignment_moderationtarget', 0);

		echo get_string('moderations','assignment_peerreview').': ';
        if($moderationCount<$moderationtarget) {
            echo '<span class="errorStatus">'.$moderationCount.' ('.get_string('moderationtargetnotmet','assignment_peerreview').')</span>';
        }
        else {
            echo $moderationCount;
        }
        echo '</div>';
        echo '<div class="reviewDetailsRow">';
		echo get_string('status').': ';
		$statusCode = $this->get_status($reviews,$numberOfCriteria);
		$this->print_status($statusCode);
		echo $statusCode<=3?' ('.get_string('moderationrequired','assignment_peerreview').')':'';
        echo '</div>';
        echo '</div>';

		if (isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
			$url = '/mod/assignment/type/peerreview/'.self::VIEW_ONLINE_TEXT.'?id='.$this->cm->id.'&a='.$this->assignment->id.'&userid='.$user->id.'&sesskey='.sesskey().'&view=moderation';
			echo '<div class="files"><a href="'.$CFG->wwwroot.$url.'" target="_blank" onclick="return openpopup(\''.$url.'\',\'submission\',\'menubar=0,location=0,scrollbars,resizable,width=500,height=400\');"><img src="'.$CFG->pixpath.'/f/html.gif" />'.get_string('submission','assignment_peerreview').'</a></div>';
		}
		else {
			$this->print_user_files($user->id);
		}
        echo '</td>';
        echo '</tr>';

        ///Start of marking row
        echo '<tr>';
        echo '<td colspan="2" style="padding:2px;">';
        echo '<form id="submitform" action="submissions.php" method="post">';
        echo '<div>'; // xhtml compatibility - invisiblefieldset was breaking layout here
        echo '<input type="hidden" name="offset" value="'.($offset).'" />';
        echo '<input type="hidden" name="userid" value="'.$userid.'" />';
        echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
        echo '<input type="hidden" name="timeLoaded" value="'.time().'" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="hidden" name="mode" value="grade" />';
//        echo '<input type="hidden" name="menuindex" value="0" />';//selected menu index
        echo '<input type="hidden" name="saveuserid" value="-1" />';
        echo '<input type="hidden" name="savePrev" value="0" />';

		echo '<table width="99%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">';
		echo '<tr>';
		for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
			echo '<td class="criteriaCheckboxColumn" style="text-align:center;vertical-align:middle;background:'.$this->REVIEW_COLOURS[$i%$this->NUMBER_OF_COLOURS].'">';
            $timeTakenReviewing = $reviews[$i]->timecompleted - $reviews[$i]->timedownloaded;
            $commentLength = strlen($reviews[$i]->reviewcomment);
            if(
                $reviews[$i]->teacherreview==0 &&
                ($timeTakenReviewing < self::MINIMAL_REVIEW_TIME || $timeTakenReviewing < $reviewStats->reviewTimeOutlierLowerBoundary) &&
                ($commentLength < self::MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength < $reviewStats->commentLengthOutlierLowerBoundary)
            ) {
                echo '<span style="color:#ff0000;font-weight:bold">!</span> ';
            }
            else {
                echo '&nbsp;';
            }
            echo '</td>';
		}
		echo '<td colspan="2" class="reviewStatus"><span style="padding-left:5px;font-weight:bold;">';
		echo get_string('newreview','assignment_peerreview');
		echo '</span>&nbsp;(';
        helpbutton('whatdostudentssee', get_string('whatdostudentssee','assignment_peerreview'), 'assignment/type/peerreview', true,true);
        echo ')</td></tr>';
		$options = new object;
		$options->para = false;
		for($i=0; $i<$numberOfCriteria; $i++) {
			echo '<tr class="criteriaDisplayRow">';
			for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
				echo '<td class="criteriaCheckboxColumn" style="background:'.$this->REVIEW_COLOURS[$j%$this->NUMBER_OF_COLOURS].'"><input type="checkbox" name="checked'.$reviews[$j]->review.'crit'.$i.'" '.($reviews[$j]->{'checked'.$i}==1?' checked':'').' onchange="allowSavePrev();" /></td>';
			}
			echo '<td class="criteriaCheckboxColumn"><input type="checkbox" name="newChecked'.$i.'" id="newChecked'.$i.'" onchange="allowSaveNew();" /></td>';
			echo '<td class="criteriaDisplayColumn"><label for="newChecked'.$i.'">'.format_text($criteriaList[$i]->textshownatreview!=''?$criteriaList[$i]->textshownatreview:$criteriaList[$i]->textshownwithinstructions,FORMAT_MOODLE,$options).'</label></td>';
			echo '</tr>';
		}
		echo '<tr>';
		for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
			echo '<td class="criteriaCheckboxColumn" style="background:'.$this->REVIEW_COLOURS[$i%$this->NUMBER_OF_COLOURS].';">&nbsp;</td>';

		}
		echo '<td colspan="2" style="padding:5px;">';
		echo '<table width="100%" cellspacing="2">';
		echo '<tr>';
		echo '<td style="vertical-align:top;" width="50%">'.get_string('comment','assignment_peerreview').'<br /><textarea name="newComment" rows="10" style="width:99%;" onkeypress="allowSaveNew();"></textarea><td>';
		echo '<td style="vertical-align:top;">'.get_string('savedcomments','assignment_peerreview').' (<a href="#null" onclick="commentForm=document.getElementById(\'commentsForm\'); commentForm.comments.value=document.getElementById(\'savedcomments\').value; window.open(\'\', \'savedcommentsWindow\', \'height=300,width=400\'); commentForm.target=\'savedcommentsWindow\'; commentForm.submit();" />'.get_string('savecomments','assignment_peerreview').'</a>)<br /><textarea rows="10" style="width:99%;" id="savedcomments" >'.($this->assignment->savedcomments?format_string(stripslashes($this->assignment->savedcomments)):'').'</textarea><td>';
		echo '</tr>';
		echo '</table>';
		echo '</td>';
		echo '</tr>';
		$studentCount = 1;
		for($i=0; $i<$numberOfReviewsOfThisStudent; $i++) {
			echo '<tr>';
			for($j=0; $j<$numberOfReviewsOfThisStudent; $j++) {
				echo '<td class="criteriaCheckboxColumn" style="background:'.($j>$numberOfReviewsOfThisStudent-$i-1?$this->REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$this->NUMBER_OF_COLOURS]:$this->REVIEW_COLOURS[$j%$this->NUMBER_OF_COLOURS]).';">&nbsp;</td>';

			}
			echo '<td colspan="2" class="reviewCommentRow" style="background:'.$this->REVIEW_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%$this->NUMBER_OF_COLOURS].';">';

			echo '<table width="99%" cellpadding="0" cellspacing="0" border="0">';
			echo '<tr class="reviewDetailsRow">';
			echo '<td><em>'.get_string('conductedby','assignment_peerreview').': '.$reviews[$numberOfReviewsOfThisStudent-$i-1]->firstname.' '.$reviews[$numberOfReviewsOfThisStudent-$i-1]->lastname.' ('.($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview==1?$this->course->teacher:$this->course->student).')</em></td>';
			echo '<td class="reviewDateColumn"><em>';
            if($reviews[$numberOfReviewsOfThisStudent-$i-1]->teacherreview==0) {
                $timeTakenReviewing = $reviews[$numberOfReviewsOfThisStudent-$i-1]->timecompleted - $reviews[$numberOfReviewsOfThisStudent-$i-1]->timedownloaded;
                echo (int)($timeTakenReviewing/60).get_string('minutes','assignment_peerreview').' '.($timeTakenReviewing%60).get_string('seconds','assignment_peerreview').' ';
                if($timeTakenReviewing < self::MINIMAL_REVIEW_TIME || $timeTakenReviewing < $reviewStats->reviewTimeOutlierLowerBoundary) {
                    echo '<span style="color:#ff0000;font-weight:bold;">'.get_string('quick','assignment_peerreview').'!</span>';
                }
                echo ', ';
                $commentLength = strlen($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment);
                if($commentLength < self::MINIMAL_REVIEW_COMMENT_LENGTH || $commentLength < $reviewStats->commentLengthOutlierLowerBoundary) {
                    echo '<span style="color:#ff0000;font-weight:bold;">'.get_string('short','assignment_peerreview').'!</span>, ';
                }
            }
            echo userdate($reviews[$numberOfReviewsOfThisStudent-$i-1]->timemodified,get_string('strftimedatetime')).'</em></td>';
			echo '</tr>';
			echo '<tr><td colspan="2"><textarea name="preExistingComment'.($reviews[$numberOfReviewsOfThisStudent-$i-1]->review).'" rows="3" class="commentTextBox" onkeypress="allowSavePrev()" style="background:'.$this->REVIEW_COMMENT_COLOURS[($numberOfReviewsOfThisStudent-$i-1)%count($this->REVIEW_COMMENT_COLOURS)].';">'.format_string(stripslashes($reviews[$numberOfReviewsOfThisStudent-$i-1]->reviewcomment)).'</textarea></td></tr>';

			if($reviews[$numberOfReviewsOfThisStudent-$i-1]->flagged==1) {
				echo '<tr class="reviewDetailsRow" style="color:#ff0000;"><td colspan="2"><em>'.get_string('flagged','assignment_peerreview').'&nbsp;<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/flagRed.gif"></em></td></tr>';
			}
			echo '</table>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

        $lastmailinfo = get_user_preferences('assignment_mailinfo', 1) ? 'checked="checked"' : '';

        ///Print Buttons in Single View
        // echo '<input type="hidden" name="mailinfo" value="0" />';
        // echo '<input type="checkbox" id="mailinfo" name="mailinfo" value="1" '.$lastmailinfo.' /><label for="mailinfo">'.get_string('enableemailnotification','assignment_peerreview').'</label>';
        echo '<div class="buttons">';
		if($numberOfReviewsOfThisStudent>0) {
			echo '<input type="submit" id="savepreexistingonly" name="submit" value="'.get_string('savepreexistingonly','assignment_peerreview').'" onclick="setSavePrev();" />';
			echo '<script>document.getElementById(\'savepreexistingonly\').disabled=true;</script>';
        }
        echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />';
		echo '<input type="submit" id="savenew" name="submit" value="'.get_string('savenew','assignment_peerreview').'" />';
		echo '<script>document.getElementById(\'savenew\').disabled=true;</script>';
        //if there are more to be graded.
        if ($nextid) {
            echo '<input type="submit" id="saveandnext" name="saveandnext" value="'.get_string('saveandnext','assignment_peerreview').'" onclick="saveNext();" />';
			echo '<script>document.getElementById(\'saveandnext\').disabled=true;</script>';
            echo '<input type="submit" name="next" value="'.get_string('next').'" onclick="setNext();" />';
        }
        echo helpbutton('moderationbuttons', get_string('moderationbuttons','assignment_peerreview'), 'assignment/type/peerreview',true,false,'',true);
        echo '</div>';
        echo '</div></form>';
		echo '<form action="type/peerreview/'.self::SAVE_COMMENTS_FILE.'" id="commentsForm" method="post" target="_blank">';

        echo '<input type="hidden" name="id" value="'.$this->cm->id.'">';
        echo '<input type="hidden" name="a" value="'.$this->assignment->id.'">';
        echo '<input type="hidden" name="comments" id="comments" value="">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
        echo '</form>';

        $customfeedback = $this->custom_feedbackform($submission, true);
        if (!empty($customfeedback)) {
            echo $customfeedback;
        }
        echo '</td></tr>';
        echo '</table>';
        print_footer('none');
    }

    //--------------------------------------------------------------------------
    // Shows the upload form on the main view page
    function view_upload_form() {
        global $CFG;

		if(isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
			notify(get_string("singleuploadwarning","assignment_peerreview"));
			$mform = new mod_assignment_peerreview_edit_form($CFG->wwwroot.'/mod/assignment/upload.php',array('id'=>$this->cm->id));
			$mform->display();
		}
		else {
			require_once($CFG->libdir.'/filelib.php');
			$icon = mimeinfo('icon', 'xxx.'.$this->assignment->fileextension);
			$type = mimeinfo('type', 'xxx.'.$this->assignment->fileextension);
			$struploadafile = get_string("uploada","peerreview") . "&nbsp;" .
							  "<img align=\"middle\" src=\"".$CFG->pixpath."/f/".$icon."\" class=\"icon\" alt=\"".$icon."\" />" .
							  "<strong>" . $type . "</strong>&nbsp;" .
							  get_string("file","peerreview") . "&nbsp;" .
							  get_string("witha","peerreview") . "&nbsp;<strong>." .
							  $this->assignment->fileextension . "</strong>&nbsp;" .
							  get_string("extension","peerreview");
			$strmaxsize = get_string("maxsize", "", display_size($this->assignment->maxbytes));

			notify(get_string("singleuploadwarning","peerreview"));
			echo '<div style="text-align:center">';
			echo '<form enctype="multipart/form-data" method="post" '.
				 "action=\"$CFG->wwwroot/mod/peerreview/upload.php\">";
			echo '<fieldset class="invisiblefieldset">';
			echo "<p>$struploadafile ($strmaxsize)</p>";
			echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
			require_once($CFG->libdir.'/uploadlib.php');
			upload_print_form_fragment(1,array('newfile'),false,null,0,$this->assignment->maxbytes,false);
			echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
			echo '</fieldset>';
			echo '</form>';
			echo '</div>';
		}
    }

    //--------------------------------------------------------------------------
    // Creates a review object to be filled for use and DB storage
    function prepare_new_review($reviewer,$reviewee) {
        $review = new Object;
        $review->assignment     = $this->assignment->id;
        $review->reviewer       = $reviewer;
        $review->reviewee       = $reviewee;
        $review->timeallocated  = time();
        $review->timemodified   = $review->timeallocated;
		$review->downloaded     = 0;
		$review->timedownloaded = 0;
		$review->complete       = 0;
		$review->timecompleted  = 0;
		$review->teacherreview  = 0;
		$review->reviewcomment  = '';
        return $review;
    }

    //--------------------------------------------------------------------------
    // Upload and process a submission
    function upload() {
        global $CFG, $USER;

		$NUM_REVIEWS = 2;
		$POOL_SIZE = 2*$NUM_REVIEWS+1; // including current submitter
        require_capability('mod/assignment:submit', get_context_instance(CONTEXT_MODULE, $this->cm->id));
        $this->view_header(get_string('upload'));

        if ($this->isopen()) {
            if(!record_exists('assignment_submissions','assignment',$this->assignment->id,'userid',$USER->id)) {

				$newsubmission = NULL;

				// Process online text
				if(isset($this->assignment->var3) && $this->assignment->var3==self::ONLINE_TEXT) {
					$newsubmission = $this->prepare_new_submission($USER->id);
					$newsubmission->data1 = addslashes(required_param('text',PARAM_CLEANHTML));
					$sumbissionName = get_string('yoursubmission','assignment_peerreview');
					// echo '<pre>'.print_r($_POST,true).'</pre>';
				}

				// Process submitted document
				else {
					$dir = $this->file_area_name($USER->id);
					require_once($CFG->dirroot.'/lib/uploadlib.php');
					$um = new upload_manager('newfile',true,false,$this->course,false,$this->assignment->maxbytes);
					if($um->preprocess_files()) {

						//Check the file extension
						$submittedFilename = $um->get_original_filename();
						$extension = $this->assignment->fileextension;
						if(strtolower(substr($submittedFilename,strlen($submittedFilename)-strlen($extension))) != $extension) {
							notify(get_string("incorrectfileextension","assignment_peerreview",$extension));
						}
						else if ($um->save_files($dir)) {
							$sumbissionName = $um->get_new_filename();
							$newsubmission = $this->prepare_new_submission($USER->id);
							$newsubmission->numfiles = 1;
						}
					}
				}

				if($newsubmission) {

					// Enter submission into DB and log
					$newsubmission->timecreated = time();
					$newsubmission->timemodified = time();
					if (insert_record('assignment_submissions', $newsubmission)) {
						add_to_log($this->course->id, 'assignment', 'upload',
								'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
						// $this->email_teachers($newsubmission);
						print_heading(get_string('uploadedfile'));
						$submissionSuccess = true;
					}
					else {
						notify(get_string("uploadnotregistered", "assignment", $sumbissionName) );
					}

					// Allocate reviews
					$recentSubmissions = array();
					$numberOfRecentSubmissions = 0;
					if($submissionResult = get_records_sql('SELECT userid FROM '.$CFG->prefix.'assignment_submissions WHERE assignment=\''.$this->assignment->id.'\' ORDER BY timecreated DESC, id DESC', 0, ($POOL_SIZE+1))) {
						$recentSubmissions = array_values($submissionResult);
						$numberOfRecentSubmissions = count($recentSubmissions);
					}
					if($numberOfRecentSubmissions>=$POOL_SIZE) {
						for($i=2; $i<2*$NUM_REVIEWS+1; $i+=2) {
							if(!insert_record('assignment_review',$this->prepare_new_review($USER->id,$recentSubmissions[$i]->userid))) {
								notify(get_string("reviewsallocationerror", "assignment_peerreview"));
							}
						}
					}

					// If pool just got large enough, allocated reviews to previous submitters
					if($numberOfRecentSubmissions==$POOL_SIZE) {
						$recentSubmissions = array_reverse($recentSubmissions);
						for($i=0; $i<$POOL_SIZE-1; $i++) {
							for($j=1; $j<=$NUM_REVIEWS; $j++) {
								insert_record('assignment_review',$this->prepare_new_review($recentSubmissions[$i]->userid,$recentSubmissions[$i-2*$j+($i-2*$j>=0?0:$NUM_REVIEWS*2+1)]->userid));
							}

							// Send an email to student
							$subject = get_string('reviewsallocatedsubject','assignment_peerreview');
							$linkToReview = $CFG->wwwroot.'/mod/assignment/view.php?id='.$this->cm->id;
							$message = get_string('reviewsallocated','assignment_peerreview')."\n\n".get_string('assignmentname','assignment').': '.$this->assignment->name."\n".get_string('course').': '.$this->course->fullname."\n\n";
							$messageText = $message.$linkToReview;
							$messageHTML = nl2br($message).'<a href="'.$linkToReview.'" target="_blank">'.get_string('reviewsallocatedlinktext','assignment_peerreview').'</a>';
							$this->email_from_teacher($this->course->id, $recentSubmissions[$i]->userid, $subject, $messageText, $messageHTML);
						}
					}

					if($numberOfRecentSubmissions>=$POOL_SIZE) {
                        redirect('view.php?id='.$this->cm->id, get_string("reviewsallocated", "assignment_peerreview"),2);
					}
					else {
						notify(get_string("poolnotlargeenough", "assignment_peerreview"),'notifysuccess');
                        print_continue('view.php?id='.$this->cm->id);
					}
                }
            }
            else {
                notify(get_string("resubmit", "assignment_peerreview",$this->course->teacher)); // re-submitting not allowed
                print_continue('view.php?id='.$this->cm->id);
}
        }
		else {
            notify(get_string("closed", "assignment_peerreview")); // assignment closed
            print_continue('view.php?id='.$this->cm->id);
        }

        $this->view_footer();
    }

    //--------------------------------------------------------------------------
    // Type specific config elements added to assignment config page
    function setup_elements(&$mform) {
        global $CFG, $COURSE;

        // Get extra settings
        if($update = optional_param('update', 0, PARAM_INT)) {
            if (! $cm = get_record("course_modules", "id", $update)) {
                error("This course module doesn't exist");
            }
            $assignment_extra = get_record('assignment_peerreview', 'assignment', $cm->instance);
			if(record_exists('assignment_submissions','assignment',$cm->instance)) {
				$mform->addElement('html','<div style="color:#ff6600;background:#ffff00;margin:5px 20px;padding:5px;text-align:center;font-weight:bold;">'.get_string('settingschangewarning','assignment_peerreview').'</div>');
			}
        }


		// Submission format
		$submissionFormats = array();
		$submissionFormats[self::SUBMIT_DOCUMENT] = get_string('submissionformatdocument','assignment_peerreview');
		$submissionFormats[self::ONLINE_TEXT] = get_string('submissionformatonlinetext','assignment_peerreview');
		$mform->addElement('select', 'var3', get_string('submissionformat', 'assignment_peerreview'),$submissionFormats);
        $mform->setDefault('var3', self::SUBMIT_DOCUMENT);

		// Filesize restriction
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment_peerreview'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);
        $mform->disabledIf('maxbytes', 'var3', 'eq', self::ONLINE_TEXT);

		// Get the list of file extensions and mime types
        $fileExtensions = array();
		require_once("$CFG->dirroot/lib/filelib.php"); // for file types
		$mimeTypes = get_mimetypes_array();
		$longestExtension = max(array_map('strlen', array_keys($mimeTypes)));
		foreach($mimeTypes as $extension => $mimeTypeAndIcon) {
			if($extension != 'xxx') {
				$padding = '';
				for($i=0; $i<$longestExtension-strlen($extension); $i++) {
					$padding .= '&nbsp;';
				}
				$fileExtensions[$extension] = $extension.$padding.' ('.$mimeTypeAndIcon['type'].')';
			}
		}
		ksort($fileExtensions,SORT_STRING);

		// File type restrinction
		$attributes=array('style'=>'font-family:monospace;width:99%;');
		$mform->addElement('select', 'fileextension', get_string('fileextension', 'assignment_peerreview'), $fileExtensions, $attributes);
		$mform->setType('fileextension', PARAM_TEXT);
        $mform->setDefault('fileextension', isset($assignment_extra)?$assignment_extra->fileextension:'doc');
        $mform->disabledIf('fileextension', 'var3', 'eq', self::ONLINE_TEXT);

		// Value of each review
		$options = array();
        for($i = 0; $i <= 50; $i++) {
            $options[$i] = "$i";
        }
        $mform->addElement('select', 'var1', get_string('valueofreview', 'assignment_peerreview'), $options);
        $mform->setDefault('var1', '10');
		$mform->setHelpButton('var1', array('valueofreview', get_string('valueofreview', 'assignment_peerreview'), 'assignment/type/peerreview/'));

		// Practice Review
		// $mform->addElement('selectyesno', 'var2', get_string('practicereview', 'assignment_peerreview'));
        // $mform->setDefault('var2', 'no');
        // $mform->setAdvanced('var2');
    }

    function add_instance($assignment) {
        $assignment_extra = new Object();
        $assignment_extra->fileextension = $assignment->fileextension;
        $assignment_extra->savedcomments = '';
        unset($assignment->fileextension);
        unset($assignment->savedcomments);

        $newid = parent::add_instance($assignment);

        if ($newid) {
            $assignment_extra->assignment = $newid;
            insert_record('assignment_peerreview', $assignment_extra);

        }

        return $newid;
    }

    function update_instance($assignment) {
        $fileextension = $assignment->fileextension;
        unset($assignment->fileextension);

        $retval = parent::update_instance($assignment);

        if ($retval) {
            $assignment_extra = get_record('assignment_peerreview', 'assignment', $assignment->id);
            $assignment_extra->fileextension = $fileextension;
            $assignment_extra->savedcomments = clean_param($assignment_extra->savedcomments,PARAM_CLEAN);
            update_record('assignment_peerreview', $assignment_extra);
        }

        return $retval;
    }

    //--------------------------------------------------------------------------
    // Cleans up database entries for a deleted assignment
    function delete_instance($assignment) {
        global $CFG;
        $result = true;

        // Delete review criteria checked values
        if (! delete_records_select('assignment_review_criterion',
                  'review IN (
                     SELECT id
                     FROM ' . $CFG->prefix . 'assignment_review
                     WHERE assignment = ' . $assignment->id . '
                   )'
        )) {
            $result = false;
        }

        // Delete reviews
        if (! delete_records('assignment_review', 'assignment', $assignment->id)) {
            $result = false;
        }

        // Delete criteria settings
        if (! delete_records('assignment_criteria', 'assignment', $assignment->id)) {
            $result = false;
        }

        // Delete extra settings
        if (! delete_records('assignment_peerreview', 'assignment', $assignment->id)) {
            $result = false;
        }

        $retval = parent::delete_instance($assignment);

        return $retval && $result;
    }

    //--------------------------------------------------------------------------
    // Header shown at top of pages with breadcrumbs, etc.
    function view_header($subpage='') {
        global $CFG;

        if ($subpage) {
            $navigation = build_navigation($subpage, $this->cm);
        } else {
            $navigation = build_navigation('', $this->cm);
        }

        $CFG->stylesheets[] = $CFG->wwwroot . '/mod/assignment/type/peerreview/'.self::STYLES_FILE;

        print_header($this->pagetitle, $this->course->fullname, $navigation, '', '',
                     true, update_module_button($this->cm->id, $this->course->id, $this->strassignment),
                     navmenu($this->course, $this->cm));

        // groups_print_activity_menu($this->cm, $CFG->wwwroot . '/mod/assignment/view.php?id=' . $this->cm->id);

        // echo '<div class="reportlink" style="margin:0 10px 20px 0;">';
        if (has_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE,$this->cm->id))) {
			$this->print_peerreview_tabs('description');

            // Help on student view
            echo '<div style="text-align:center;margin:0 0 10px 0;">';
            helpbutton('whatdostudentsseeview', get_string('whatdostudentssee','assignment_peerreview'), 'assignment/type/peerreview', true,true);
            echo '</div>';

		}
    }

    //--------------------------------------------------------------------------
    // Process and save feedback from a teacher moderation
    function process_feedback() {
        global $CFG, $USER;
        require_once($CFG->libdir.'/gradelib.php');

        if (!$feedback = data_submitted() or !confirm_sesskey()) {      // No incoming data?
            return false;
        }

        ///For save and next, we need to know the userid to save, and the userid to go
        ///We use a new hidden field in the form, and set it to -1. If it's set, we use this
        ///as the userid to store
        if ((int)$feedback->saveuserid !== -1){
            $feedback->userid = $feedback->saveuserid;
        }

        if (!empty($feedback->cancel)) {          // User hit cancel button
            return false;
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id, $feedback->userid);
        $submission = $this->get_submission($feedback->userid, true);  // Get or make one
		$numberOfCriteria = count_records('assignment_criteria','assignment',$this->assignment->id);

        if (optional_param('timeLoaded') != get_user_preferences('assignment_lastSaved', -1) &&
            !$grading_info->items[0]->grades[$feedback->userid]->locked &&
            !$grading_info->items[0]->grades[$feedback->userid]->overridden) {

            // Set time to prevent re-save
            set_user_preference('assignment_lastSaved', optional_param('timeLoaded'));

			// Get pre-existing reviews for the student
			if(optional_param('savePrev',false,PARAM_BOOL)) {
				if($reviews = get_records_select('assignment_review','assignment=\''.$this->assignment->id.'\' AND reviewee=\''.$feedback->userid.'\' ORDER BY id ASC')){
					$numberOfReviews = count($reviews);

					// Update the pre-existing reviews
					foreach($reviews as $reviewID=>$review) {
						for($i=0; $i<$numberOfCriteria; $i++) {
							set_field('assignment_review_criterion','checked',optional_param('checked'.$reviewID.'crit'.$i,0,PARAM_BOOL),'review',$reviewID,'criterion',$i);
						}
						set_field('assignment_review','reviewcomment',clean_param(htmlspecialchars(optional_param('preExistingComment'.$reviewID,NULL,PARAM_RAW)),PARAM_CLEAN),'id',$reviewID);
					}
				}
			}

			// Add the new review
			else {
				$review = $this->prepare_new_review($USER->id,$feedback->userid);
				$review->downloaded = 1;
				$review->complete = 1;
				$review->teacherreview = 1;
				$review->reviewcomment = clean_param(htmlspecialchars(optional_param('newComment',NULL,PARAM_RAW)),PARAM_CLEAN);
				$newReviewID = insert_record('assignment_review',$review,true);
				for($i=0; $i<$numberOfCriteria; $i++) {
					$criterionToSave = new Object;
					$criterionToSave->review = $newReviewID;
					$criterionToSave->criterion = $i;
					$criterionToSave->checked = optional_param('newChecked'.$i,0,PARAM_BOOL);
					insert_record('assignment_review_criterion',$criterionToSave);
				}

				// Send an email to student
				$subject = get_string('teacherreviewreceivedsubject','assignment_peerreview',$this->course->teacher);
				$linkToReview = $CFG->wwwroot.'/mod/assignment/view.php?id='.$this->cm->id;
                $message = get_string('teacherreviewreceivedmessage','assignment_peerreview',$this->course->teacher)."\n\n".get_string('assignmentname','assignment').': '.$this->assignment->name."\n".get_string('course').': '.$this->course->fullname."\n\n";
                $messageText = $message.$linkToReview;
                $messageHTML = nl2br($message).'<a href="'.$linkToReview.'" target="_blank">'.get_string('teacherreviewreceivedlinktext','assignment_peerreview').'</a>';
				$this->email_from_teacher($this->course->id, $feedback->userid, $subject, $messageText, $messageHTML);
			}

			// set_field('assignment_submissions','timemodified',time(),'id',$submission->id);
            add_to_log($this->course->id, 'assignment', 'update grades',
                       'submissions.php?id='.$this->assignment->id.'&user='.$feedback->userid, $feedback->userid, $this->cm->id);
        }

        return $submission;
    }

    //--------------------------------------------------------------------------
    // Sets a grade for a student
	function set_grade($userid, $grade) {
		global $USER, $CFG, $DB;
        $peerreviewid = $this->assignment->id;
        // Set up submission object
        $submission = $DB->get_record('peerreview_submissions', ['peerreview' => $peerreviewid, 'userid' => $userid]);
        $submission->grade      = $grade;
        $submission->teacher    = $USER->id;
        $submission->timemarked = time();


        // Attempt to write to the database
        if (!$DB->update_record('peerreview_submissions', $submission)) {
            return NULL;
        }

        // Send an email to student
		$subject = get_string('gradesetsubject','assignment_peerreview');
		$linkToReview = $CFG->wwwroot.'/mod/assignment/view.php?id='.$this->cm->id;
        $message = get_string('gradesetmessage','assignment_peerreview',$this->course->teacher)."\n\n".get_string('assignmentname','assignment').': '.$this->assignment->name."\n".get_string('course').': '.$this->course->fullname."\n\n";
        $messageText = $message.$linkToReview;
        $messageHTML = nl2br($message).'<a href="'.$linkToReview.'" target="_blank">'.get_string('gradesetlink','assignment_peerreview').'</a>';
		$submission->mailed = $this->email_from_teacher($this->course->id, $userid, $subject, $messageText, $messageHTML);
        set_field('assignment_submissions','mailed',$submission->mailed,'id',$submission->id);

		add_to_log($this->course->id, 'assignment', 'update grades', 'submissions.php?id='.$this->assignment->id.'&user='.$userid, $userid, $this->cm->id);
		return $submission;
	}

    //--------------------------------------------------------------------------
    // Sets all unset calculatable grades
	function mass_grade() {
		global $CFG,$DB,$OUTPUT,$PAGE;

		require_once($CFG->dirroot.'/config.php');
		require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/tablelib.php');
        //$CFG->stylesheets[] = $CFG->wwwroot . '/mod/assignment/type/peerreview/'.self::STYLES_FILE;

       /* $navigation = build_navigation($this->strsubmissions, $this->cm);
        print_header_simple(format_string($this->assignment->name,true), "", $navigation,
                '', '', true, update_module_button($this->cm->id, $this->course->id, $this->strassignment), navmenu($this->course, $this->cm));
		print_heading(get_string('settingmarks','assignment_peerreview'),1);
		*/
		$attributes = array('peerreviewid' => $this->assignment->id, 'id' => $this->cm->id);	
		$PAGE->set_url('/mod/peerreview/massMark.php', $attributes);
		$PAGE->set_title(format_string($this->assignment->name));
		$PAGE->set_heading(format_string($this->course->fullname));
		$PAGE->set_context($this->context);

		echo $OUTPUT->header();
		echo $OUTPUT->heading(format_string($this->assignment->name));

		echo '<div style="text-align:center;margin:0 0 10px 0;">';

        // Get submissions with reviews so that grades can be set
        $criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($this->assignment->id),null,'ordernumber, value');
		if($criteriaList && count($criteriaList)>0) {
            $criteriaList = array_values($criteriaList);
            $query = 'SELECT a.userid, SUM(r.completed) as reviewscomplete, a.timemarked, a.timecreated, a.id, u.firstname, u.lastname '.
                     'FROM '.$CFG->prefix.'peerreview_submissions a, '.$CFG->prefix.'peerreview_review r, '.$CFG->prefix.'user u '.
                     'WHERE a.peerreview='.$this->assignment->id.' '.
                     'AND r.peerreview='.$this->assignment->id.' '.
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

                // Build table of submissions as they are marked
                for($i=0; $i<$numberOfSubmissions; $i++) {
                    $name = $submissions[$i]->firstname . ' ' . $submissions[$i]->lastname;
                    if ($submissions[$i]->timemarked==0) {

                        $reviews = $this->get_reviews_of_student($submissions[$i]->userid);
                        $grade = $this->get_marks($reviews,$criteriaList,$submissions[$i]->reviewscomplete,$this->assignment->reviewreward);
                        if($grade!='???') {
                            if($submission = $this->set_grade($submissions[$i]->userid,$grade)) {
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

        echo $OUTPUT->continue_button($CFG->wwwroot.'/mod/peerreview/submissions.php?id='.$this->cm->id . '&peerreviewid='. $this->assignment->id);
        echo '</div>';
        echo $OUTPUT->footer();
	}

    //------------------------------------------------------------------------------
    // Calculates a mark from peer and teacher reviews
    function get_marks($reviews,$criteriaList,$numberOfReviewsByStudent,$reviewReward) {
        $numberOfReviewsOfThisStudent = is_array($reviews)?count($reviews):0;
        $numberOfCriteria = is_array($criteriaList)?count($criteriaList):0;
        $teacherReviewIndex = $numberOfReviewsOfThisStudent-1;
        $marks = 0;
        $statusCode = $this->get_status($reviews,$numberOfCriteria);

        if($statusCode<=3) {
            return '???';
        }

        if($statusCode == self::OVERRIDDEN) {

            while($reviews[$teacherReviewIndex]->teacherreview!=1) {
                $teacherReviewIndex--;
            }
            for($i=0; $i<$numberOfCriteria; $i++) {
                if($reviews[$teacherReviewIndex]->{'checked'.$i} == 1) {
                    $marks += $criteriaList[$i]->value;
                }
            }
        }

        if($statusCode == self::CONCENSUS) {

            for($i=0; $i<$numberOfCriteria; $i++) {
                if($reviews[0]->{'checked'.$i} == 1) {
                    $marks += $criteriaList[$i]->value;
                }
            }
        }
        return $marks + $numberOfReviewsByStudent*$reviewReward;
    }

    function print_status($status,$return=false) {
        $progressMessage = '';
        switch($status) {
            case self::FLAGGED:               $progressMessage = '<span class="errorStatus">'.get_string('flagged','assignment_peerreview').'</span>'; break;
            case self::CONFLICTING:           $progressMessage = '<span class="errorStatus">'.get_string('conflicting','assignment_peerreview').'</span>'; break;
            case self::FLAGGEDANDCONFLICTING: $progressMessage = '<span class="errorStatus">'.get_string('conflicting','assignment_peerreview').', '.get_string('flagged','assignment_peerreview').'</span>'; break;
            case self::LESSTHANTWOREVIEWS:    $progressMessage = '<span class="errorStatus">'.get_string('lessthantworeviews','assignment_peerreview').'</span>'; break;
            case self::CONCENSUS:             $progressMessage = '<span class="goodStatus">'. get_string('concensus','assignment_peerreview').'</span>'; break;
            case self::OVERRIDDEN:            $progressMessage = '<span class="goodStatus">'. get_string('overridden','assignment_peerreview').'</span>'; break;

        }
        if($return) {
            return $progressMessage;
        }
        else {
            echo $progressMessage;
        }
    }

    //------------------------------------------------------------------------------
    // Finds the status of the submission and if moderation is needed
    function get_status($reviews,$numberOfCriteria) {
        $numberOfReviewsOfThisStudent = is_array($reviews)?count($reviews):0;
        $flagged = false;
        $conflicting = false;
        $overridden = false;

        for($i=0; $i<$numberOfReviewsOfThisStudent && !$overridden; $i++) {
            $overridden = $reviews[$i]->teacherreview==1;
            $flagged = $flagged || $reviews[$i]->flagged==1;
        }

        if($overridden) {
            return self::OVERRIDDEN;
        }
        if($numberOfReviewsOfThisStudent<2) {
            return self::LESSTHANTWOREVIEWS;
        }

        for($i=0; $i<$numberOfCriteria && !$conflicting; $i++) {
            for($j=0; $j<$numberOfReviewsOfThisStudent-1 && !$conflicting; $j++) {
                $conflicting = $reviews[$j]->{'checked'.$i} != $reviews[$j+1]->{'checked'.$i};
            }
        }

        if($flagged && $conflicting) {
            return self::FLAGGEDANDCONFLICTING;
        }
        if($flagged) {
            return self::FLAGGED;
        }
        if($conflicting) {
            return self::CONFLICTING;
        }
        return self::CONCENSUS;
    }

    //------------------------------------------------------------------------------
    // Prints a progress status box
    function print_progress_box($class='redProgressBox', $number='1', $title='', $message='') {
        global $CFG;

        if($class=='end') {
            echo '<div style="clear:both;height:10px;margin:0;padding:0"></div>';
        }
        else {
            echo '<div class="progressBox">';
            echo '<table class="'.$class.'">';
            echo '<tr>';
            echo '<td rowspan="2" class="progressNumber">'.$number.'</td>';
            echo '<td class="progressTitle">'.$title.'</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="progressMessage">'.$message.'</td>';
            echo '</tr>';
            echo '</table>';
            echo '</div>';
        }
    }

    //------------------------------------------------------------------------------
    // Commonly used query (very complicated) query to get reviews of a student
    function get_reviews_of_student($reviewee) {
        global $CFG, $DB;

        // Query for finding the reviews for this student (the query of many joins)
        $feedbackQuery = 'SELECT r.id as review, r.timemodified as timemodified, r.timecompleted as timecompleted, r.timedownloaded as timedownloaded, r.teacherreview as teacherreview, r.reviewcomment as reviewcomment, r.flagged as flagged, r.timefirstviewedbyreviewee as timefirstviewedbyreviewee, r.timelastviewedbyreviewee as timelastviewedbyreviewee, r.timesviewedbyreviewee as timesviewedbyreviewee, u.firstname as firstname, u.lastname as lastname';
        $feedbackQuery .= ' FROM '.$CFG->prefix.'peerreview_review r, '.$CFG->prefix.'user u';
        $feedbackQuery .= ' WHERE r.peerreview=\''.$this->assignment->id.'\' AND r.reviewee=\''.$reviewee.'\' AND r.completed=1';
        $feedbackQuery .= ' AND r.reviewer=u.id ';
        $feedbackQuery .= ' ORDER BY r.id ASC';
        $reviews = $DB->get_records_sql($feedbackQuery);
        if($reviews) {
            $reviews = array_values($reviews);
            $numberOfReviews = count($reviews);
            for($i=0; $i<$numberOfReviews; $i++) {
                $criteriaList = $DB->get_records('peerreview_review_criterion',['review' => $reviews[$i]->review]);
                foreach($criteriaList as $id=>$criterion) {
                    $reviews[$i]->{'checked'.$criterion->criterion} = $criterion->checked;
                }
            }
            return $reviews;
        }
        return NULL;
    }

    //------------------------------------------------------------------------------
    // Handy function for sending an anonymous email from the course teacher
    function email_from_teacher($courseID, $studentID, $subject, $messageText, $messageHTML='') {
        $course = get_record("course", "id", $courseID);
        $student = get_record('user','id',$studentID);
        return email_to_user($student, $course->teacher.': '.$course->shortname, $course->shortname.': '.$subject, $messageText, $messageHTML);
    }


    //------------------------------------------------------------------------------
    // Backup assignment config not in assignment table
    function backup_one_mod($bf, $preferences, $assignment) {
        if (!$extras = get_record('assignment_peerreview', 'assignment', $assignment->id)) {
            debugging("something wrong in assignment/type/peerreview backup_one_mod - couldn't find extra data");
            return false;
        }
        if (!$criteria = get_records('assignment_criteria', 'assignment', $assignment->id)) {
            debugging("something wrong in assignment/type/peerreview backup_one_mod - couldn't find criteria");
            return false;
        }
        fwrite ($bf,full_tag("SAVEDCOMMENTS",4,false,$extras->savedcomments));
        fwrite ($bf,full_tag("FILEEXTENSION",4,false,$extras->fileextension));
        fwrite ($bf,start_tag("CRITERIA",4,true));
        foreach ($criteria as $criterion) {
            fwrite ($bf,start_tag("CRITERION",5,true));
            fwrite ($bf,full_tag("ORDERNUMBER",6,false,$criterion->ordernumber));
            fwrite ($bf,full_tag("TEXTSHOWNWITHINSTRUCTIONS",6,false,$criterion->textshownwithinstructions));
            fwrite ($bf,full_tag("TEXTSHOWNATREVIEW",6,false,$criterion->textshownatreview));
            fwrite ($bf,full_tag("VALUE",6,false,$criterion->value));
            fwrite ($bf,end_tag("CRITERION",5,true));
        }
        fwrite ($bf,end_tag("CRITERIA",4,true));
        return true;
    }

    //------------------------------------------------------------------------------
    // Backup submission info not in assignment_submissions
    function backup_one_submission($bf, $preferences, $assignment, $submission) {
        if ($reviews = get_records_select('assignment_review', 'assignment='.$assignment->id.' AND reviewee='.$submission->userid,'id')) {
            foreach ($reviews as $review) {
                fwrite ($bf,start_tag("REVIEW",6,true));
                fwrite ($bf,full_tag("ID",7,false,$review->id));
                fwrite ($bf,full_tag("REVIEWER",7,false,$review->reviewer));
                fwrite ($bf,full_tag("REVIEWEE",7,false,$review->reviewee));
                fwrite ($bf,full_tag("TIMEALLOCATED",7,false,$review->timeallocated));
                fwrite ($bf,full_tag("TIMEMODIFIED",7,false,$review->timemodified));
                fwrite ($bf,full_tag("DOWNLOADED",7,false,$review->downloaded));
                fwrite ($bf,full_tag("TIMEDOWNLOADED",7,false,$review->timedownloaded));
                fwrite ($bf,full_tag("COMPLETE",7,false,$review->complete));
                fwrite ($bf,full_tag("TIMECOMPLETED",7,false,$review->timecompleted));
                fwrite ($bf,full_tag("TEACHERREVIEW",7,false,$review->teacherreview));
                fwrite ($bf,full_tag("FLAGGED",7,false,$review->flagged));
                fwrite ($bf,full_tag("REVIEWCOMMENT",7,false,$review->reviewcomment));
                fwrite ($bf,full_tag("TIMEFIRSTVIEWEDBYREVIEWEE",7,false,$review->timefirstviewedbyreviewee));
                fwrite ($bf,full_tag("TIMELASTVIEWEDBYREVIEWEE",7,false,$review->timelastviewedbyreviewee));
                fwrite ($bf,full_tag("TIMESVIEWEDBYREVIEWEE",7,false,$review->timesviewedbyreviewee));
                if($reviewCriteria = get_records('assignment_review_criterion','review',$review->id,'review')) {
                    foreach ($reviewCriteria as $reviewCriterion) {
                        fwrite ($bf,start_tag("REVIEWCRITERION",7,true));
                        fwrite ($bf,full_tag("REVIEW",8,false,$reviewCriterion->review));
                        fwrite ($bf,full_tag("CRITERION",8,false,$reviewCriterion->criterion));
                        fwrite ($bf,full_tag("CHECKED",8,false,$reviewCriterion->checked));
                        fwrite ($bf,end_tag("REVIEWCRITERION",8,true));
                    }
                }
                fwrite ($bf,end_tag("REVIEW",6,true));
            }
        }
        return true;
    }

    //------------------------------------------------------------------------------
    // Restore assignment config not in assignment table
    function restore_one_mod($info, $restore, $assignment) {

        // Save extra assignment information
        $newassid = backup_getid($restore->backup_unique_code, 'assignment', backup_todb($info['MOD']['#']['ID']['0']['#']));
        $extra = new stdclass;
        $extra->assignment = $newassid->new_id;
        $extra->savedcomments = backup_todb($info['MOD']['#']['SAVEDCOMMENTS']['0']['#']);
        $extra->fileextension = backup_todb($info['MOD']['#']['FILEEXTENSION']['0']['#']);
        insert_record('assignment_peerreview', $extra);

        // Find criteria if set and add to database
        if (@isset($info['MOD']['#']['CRITERIA']['0']['#']['CRITERION'])) {
            $criteria = $info['MOD']['#']['CRITERIA']['0']['#']['CRITERION'];

            foreach($criteria as $criterion) {
                $critRecord = new stdclass;
                $critRecord->assignment                = $newassid->new_id;
                $critRecord->ordernumber               = backup_todb($criterion['#']['ORDERNUMBER']['0']['#']);
                $critRecord->textshownwithinstructions = backup_todb($criterion['#']['TEXTSHOWNWITHINSTRUCTIONS']['0']['#']);
                $critRecord->textshownatreview         = backup_todb($criterion['#']['TEXTSHOWNATREVIEW']['0']['#']);
                $critRecord->value                     = backup_todb($criterion['#']['VALUE']['0']['#']);
                insert_record('assignment_criteria', $critRecord);
            }
        }
        return true;
    }

    //------------------------------------------------------------------------------
    // Restore submission info not in assignment_submissions
    function restore_one_submission($info, $restore, $assignment, $submission) {

        // Find the submissions if present
        if (@isset($info['#']['REVIEW'])) {
            $reviews = $info['#']['REVIEW'];

            foreach ($reviews as $review) {
                $revRecord = new stdclass;
                $revRecord->assignment                 = $submission->assignment;
                $revRecord->reviewer                   = backup_todb($review['#']['REVIEWER']['0']['#']);
                if ($user = backup_getid($restore->backup_unique_code, 'user', $revRecord->reviewer)) {
                     $revRecord->reviewer = $user->new_id;
                }
                $revRecord->reviewee                   = backup_todb($review['#']['REVIEWEE']['0']['#']);
                if ($user = backup_getid($restore->backup_unique_code, 'user', $revRecord->reviewee)) {
                    $revRecord->reviewee = $user->new_id;
                }
                $revRecord->timeallocated              = backup_todb($review['#']['TIMEALLOCATED']['0']['#']);
                $revRecord->timemodified               = backup_todb($review['#']['TIMEMODIFIED']['0']['#']);
                $revRecord->downloaded                 = backup_todb($review['#']['DOWNLOADED']['0']['#']);
                $revRecord->timedownloaded             = backup_todb($review['#']['TIMEDOWNLOADED']['0']['#']);
                $revRecord->complete                   = backup_todb($review['#']['COMPLETE']['0']['#']);
                $revRecord->timecompleted              = backup_todb($review['#']['TIMECOMPLETED']['0']['#']);
                $revRecord->teacherreview              = backup_todb($review['#']['TEACHERREVIEW']['0']['#']);
                $revRecord->flagged                    = backup_todb($review['#']['FLAGGED']['0']['#']);
                $revRecord->reviewcomment              = backup_todb($review['#']['REVIEWCOMMENT']['0']['#']);
                $revRecord->timefirstviewedbyreviewee  = backup_todb($review['#']['TIMEFIRSTVIEWEDBYREVIEWEE']['0']['#']);
                $revRecord->timelastviewedbyreviewee   = backup_todb($review['#']['TIMELASTVIEWEDBYREVIEWEE']['0']['#']);
                $revRecord->timesviewedbyreviewee      = backup_todb($review['#']['TIMESVIEWEDBYREVIEWEE']['0']['#']);
                $revID = insert_record('assignment_review', $revRecord, true);
                if(@isset($review['#']['REVIEWCRITERION'])) {
                    foreach ($review['#']['REVIEWCRITERION'] as $reviewCriterion) {
                        $revCritRecord = new stdclass;
                        $revCritRecord->review    = $revID;
                        $revCritRecord->criterion = backup_todb($reviewCriterion['#']['CRITERION']['0']['#']);
                        $revCritRecord->checked   = backup_todb($reviewCriterion['#']['CHECKED']['0']['#']);
                        insert_record('assignment_review_criterion', $revCritRecord);
                    }
                }
            }
        }
        return true;
    }

    //------------------------------------------------------------------------------
    // Calculates statistics relating to reviewing
    function get_review_statistics() {
        $stats = new Object;
        $stats->numberOfSubmissions = 0;
        $stats->numberOfReviews = 0;
        $stats->reviewRate = 0;
        $stats->numberOfModerations = 0;
        $stats->moderationRate = 0;
        $stats->totalReviewTime = 0; // seconds
        $stats->averageReviewTime = 0; // seconds
        $stats->normalisedAverageReviewTime = 0; // seconds
        $stats->stdDevReviewTime = 0; // seconds
        $stats->minReviewTime = PHP_INT_MAX; // seconds
        $stats->maxReviewTime = 0; // seconds
        $stats->totalCommentLength = 0; // characters
        $stats->averageCommentLength = 0; // characters
        $stats->normalisedAverageCommentLength = 0; // characters
        $stats->stdDevCommentLength = 0; // characters
        $stats->reviewTimeOutlierLowerBoundary = 0; // seconds
        $stats->reviewTimeOutlierUpperBoundary = 0; // seconds
        $stats->commentLengthOutlierLowerBoundary = 0;
        $stats->commentLengthOutlierUpperBoundary = 0;
        $stats->minCommentLength = PHP_INT_MAX; // characters
        $stats->maxCommentLength = 0; // characters
        $stats->flags = 0;
        $stats->flagRate = 0;
        $stats->numberOfReviewsViewed = 0;
        $stats->reviewAttentionRate = 0;
        $stats->numberOfReviewViews = 0;
        $stats->averageViewRate = 0;
        $stats->totalPeriodBetweenReviewAndView = 0; // seconds
        $stats->averagePeriodBewtweenReviewAndView = 0; // seconds
        $stats->medianPeriodBewtweenReviewAndView = 0; // seconds
        $commentLengths = array();
        $reviewTimes = array();
        $waitTimes = array();

        // Get submission and moderation stats
        $stats->numberOfSubmissions = count_records('assignment_submissions','assignment',$this->assignment->id);
        $stats->numberOfModerations = count_records_select('assignment_review', 'assignment=\''.$this->assignment->id.'\' AND teacherreview=\'1\' AND complete=\'1\'');
        $stats->moderationRate = $stats->numberOfSubmissions==0?0:$stats->numberOfModerations/$stats->numberOfSubmissions;

        // Calculate review stats
        $reviews = get_records_select('assignment_review', 'assignment=\''.$this->assignment->id.'\' AND teacherreview=\'0\' AND complete=\'1\'','id');
        $stats->numberOfReviews = count($reviews);
		if(is_array($reviews) && $stats->numberOfReviews >= self::REVIEW_FEEDBACK_MIN) {
            $stats->reviewRate = $stats->numberOfSubmissions==0?0:$stats->numberOfReviews/2/$stats->numberOfSubmissions;

            // Collect review times and lengths for normalisation
            foreach($reviews as $id=>$review) {

                // Review times
                $reviewTime = $review->timecompleted - $review->timedownloaded;
                $reviewTimes[] = $reviewTime;
                $stats->totalReviewTime += $reviewTime;
                if($reviewTime > $stats->maxReviewTime) {
                    $stats->maxReviewTime = $reviewTime;
                }
                if($reviewTime < $stats->minReviewTime) {
                    $stats->minReviewTime = $reviewTime;
                }

                // Comment lengths
                $commentLength = strlen($review->reviewcomment);
                $commentLengths[] = $commentLength;
                $stats->totalCommentLength += $commentLength;
                if($commentLength > $stats->maxCommentLength) {
                    $stats->maxCommentLength = $commentLength;
                }
                if($commentLength < $stats->minCommentLength) {
                    $stats->minCommentLength = $commentLength;
                }

                // Count flags
                if($review->flagged == 1) {
                    $stats->flags++;
                }

                // Feedback attention stats
                if($review->timefirstviewedbyreviewee > 0) {
                    $stats->numberOfReviewsViewed++;
                    $stats->numberOfReviewViews += $review->timesviewedbyreviewee;
                    $waitTime = $review->timefirstviewedbyreviewee-$review->timecompleted;
                    $waitTimes[] = $waitTime;
                    $stats->totalPeriodBetweenReviewAndView += $waitTime;
                }
            }

            // Normalise the stats before calculating averages
            sort($reviewTimes);
            sort($commentLengths);
            $reviewTimeLowerQuartileBound = $reviewTimes[floor($stats->numberOfReviews*0.25)];
            $reviewTimeThirdQuartileBound = $reviewTimes[floor($stats->numberOfReviews*0.75)];
            $commentLengthLowerQuartileBound = $commentLengths[(int)$stats->numberOfReviews*0.25];
            $commentLengthThirdQuartileBound = $commentLengths[(int)$stats->numberOfReviews*0.75];
            $interQuartileDistanceTime = $reviewTimeThirdQuartileBound - $reviewTimeLowerQuartileBound;
            $interQuartileDistanceLength = $commentLengthThirdQuartileBound - $commentLengthLowerQuartileBound;
            $stats->reviewTimeOutlierLowerBoundary = $reviewTimeLowerQuartileBound - 1.5*$interQuartileDistanceTime;
            $stats->reviewTimeOutlierUpperBoundary = $reviewTimeThirdQuartileBound + 1.5*$interQuartileDistanceTime;
            $stats->commentLengthOutlierLowerBoundary = $commentLengthLowerQuartileBound - 1.5*$interQuartileDistanceTime;
            $stats->commentLengthOutlierUpperBoundary = $commentLengthThirdQuartileBound + 1.5*$interQuartileDistanceTime;

            // Remove outliers from the ends of the arrays
            $numberOfNormalisedReviewTimes = $stats->numberOfReviews;
            while($reviewTimes[$numberOfNormalisedReviewTimes-1] > $stats->reviewTimeOutlierUpperBoundary) {
                $numberOfNormalisedReviewTimes--;
                array_pop($reviewTimes);
            }
            while($reviewTimes[0] < $stats->reviewTimeOutlierLowerBoundary) {
                $numberOfNormalisedReviewTimes--;
                array_shift($reviewTimes);
            }
            $numberOfNormalisedCommentLengths = $stats->numberOfReviews;
            while($commentLengths[$numberOfNormalisedCommentLengths-1] > $stats->commentLengthOutlierUpperBoundary) {
                $numberOfNormalisedCommentLengths--;
                array_pop($commentLengths);
            }
            while($commentLengths[0] > $stats->commentLengthOutlierUpperBoundary) {
                $numberOfNormalisedCommentLengths--;
                array_shift($commentLengths);
            }

            // Normalised average summing
            $normalisedReviewTimeSum = 0;
            foreach($reviewTimes as $reviewTime) {
                $normalisedReviewTimeSum += $reviewTime;
            }
            $normalisedCommentLengthSum = 0;
            foreach($commentLengths as $commentLength) {
                $normalisedCommentLengthSum += $commentLength;
            }

            // Average calculations
            $stats->averageReviewTime = $stats->totalReviewTime/$stats->numberOfReviews;
            $stats->normalisedAverageReviewTime = $normalisedReviewTimeSum / $numberOfNormalisedReviewTimes;
            $stats->averageCommentLength = $stats->totalCommentLength/$stats->numberOfReviews;
            $stats->normalisedAverageCommentLength = $normalisedCommentLengthSum / $numberOfNormalisedCommentLengths;
            $stats->flagRate = $stats->flags/$stats->numberOfReviews;
            $stats->reviewAttentionRate = $stats->numberOfReviewsViewed/$stats->numberOfReviews;
            $stats->averageViewRate = $stats->numberOfReviewViews/$stats->numberOfReviews;
            if($stats->numberOfReviewsViewed>0) {
                $stats->averagePeriodBewtweenReviewAndView = $stats->totalPeriodBetweenReviewAndView/$stats->numberOfReviews;
                sort($waitTimes);
                $stats->medianPeriodBewtweenReviewAndView = $waitTimes[(int)($stats->numberOfReviewsViewed/2)];
            }

            // Standard deviation calculations
            $sumOfTimeDifferences = 0;
            $sumOfCommentDifferences = 0;
            foreach($reviews as $id=>$review) {
                $sumOfTimeDifferences += pow(($review->timecompleted - $review->timedownloaded) - $stats->averageReviewTime, 2);
                $sumOfCommentDifferences += pow(strlen($review->reviewcomment) - $stats->averageCommentLength, 2);
            }
            $stats->stdDevReviewTime = sqrt($sumOfTimeDifferences/$stats->numberOfReviews);
            $stats->stdDevCommentLength = sqrt($sumOfCommentDifferences/$stats->numberOfReviews);
        }
        else {
            $stats->minReviewTime = 0;
            $stats->minCommentLength = 0;
        }

        return $stats;
    }

    //------------------------------------------------------------------------------
    // Calculates statistics relating to criteria
    function get_criteria_statistics() {
        $stats = array();

        // Get the criteria
		$criteria = get_records_list('assignment_criteria','assignment',$this->assignment->id,'ordernumber');
		$numberOfCriteria = 0;
        if(is_array($criteria)) {
            $criteria = array_values($criteria);
			$numberOfCriteria = count($criteria);
        }
        for($i=0; $i<$numberOfCriteria; $i++) {
            $stats[$i] = new Object;
            $stats[$i]->count = 0;
            $stats[$i]->rate = 0;
            $stats[$i]->textshownatreview = $criteria[$i]->textshownatreview;
            $stats[$i]->textshownwithinstructions = $criteria[$i]->textshownwithinstructions;
        }

        // Review checked stats
        $numberOfReviews = 0;
        if($reviews = get_records_select('assignment_review', 'assignment=\''.$this->assignment->id.'\' AND teacherreview=\'0\' AND complete=\'1\'','id')) {
            $numberOfReviews = count($reviews);
            foreach($reviews as $id=>$review) {
                $reviewCriteria = get_records('assignment_review_criterion','review',$id);
                foreach($reviewCriteria as $criterionID=>$reviewCriterion) {
                    if($reviewCriterion->checked) {
                        $stats[$reviewCriterion->criterion]->count++;
                    }
                }
            }
        }

        // Review checked rate calculations
        for($i=0; $i<$numberOfCriteria; $i++) {
            $stats[$i]->rate = $numberOfReviews==0 ? 0 : ($stats[$i]->count / $numberOfReviews);
        }

        return $stats;
    }

    //------------------------------------------------------------------------------
    // Calculates statistics relating to submissions
    function get_submission_statistics() {
        global $CFG;

        $stats = new Object;
        $stats->numberOfStudents = 0;
        $stats->numberOfSubmissions = 0;
        $stats->submissionRate = 0;
        $stats->totalWaitForFeedback = 0; // seconds
        $stats->averageWaitForFeedback = 0; // seconds
        $stats->medianWaitForFeedback = 0; // seconds

        // Get all ppl that are allowed to submit assignments
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        if ($users = get_users_by_capability($context, 'mod/assignment:submit', 'u.id')) {
            $users = array_keys($users);
            if ($teachers = get_users_by_capability($context, 'mod/assignment:grade', 'u.id')) {
                $users = array_diff($users, array_keys($teachers));
                $stats->numberOfStudents = count($users);
            }
        }

        // Get the number of submissions and rate
        $stats->numberOfSubmissions = count_records('assignment_submissions','assignment',$this->assignment->id);
        $stats->submissionRate = $stats->numberOfSubmissions==0?0:$stats->numberOfSubmissions/$stats->numberOfStudents;

        $sql = 'SELECT userid, timecreated, timecompleted '.
               'FROM '.$CFG->prefix.'assignment_submissions s, '.$CFG->prefix.'assignment_review r '.
               'WHERE s.assignment=\''.$this->assignment->id.'\' '.
               'AND s.assignment=r.assignment '.
               'AND s.userid=r.reviewee '.
               'AND r.teacherreview=\'0\' '.
               'AND r.complete=\'1\' '.
               'ORDER BY userid ASC, timecompleted DESC';
        $waitStats = get_records_sql($sql);
        $waitTimes = array();
        if(isset($waitStats) && is_array($waitStats) && count($waitStats)>0) {
            $stats->numberOfSubmissionsWithFeedback = count($waitStats);
            foreach($waitStats as $userid=>$stat) {
                $waitTime = $stat->timecompleted - $stat->timecreated;
                $waitTimes[] = $waitTime;
                $stats->totalWaitForFeedback += $waitTime;
            }
            if($stats->numberOfSubmissionsWithFeedback>0) {
                $stats->averageWaitForFeedback = $stats->totalWaitForFeedback/$stats->numberOfSubmissionsWithFeedback;
                sort($waitTimes);
                $stats->medianWaitForFeedback = $waitTimes[(int)($stats->numberOfSubmissionsWithFeedback/2)];
            }
        }
        return $stats;
    }

    //------------------------------------------------------------------------------
    // Print tabs at top of page
    function print_peerreview_tabs($current='description') {
        global $CFG;

		$tabs = array();
		$row  = array();
		$row[] = new tabobject('description', $CFG->wwwroot.'/mod/assignment/'.self::VIEW_FILE."?id=".$this->cm->id, get_string('description'));
		$row[] = new tabobject('criteria', $CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::CRITERIA_FILE."?id=".$this->cm->id."&a=".$this->assignment->id, get_string('criteria', 'assignment_peerreview'));
		$row[] = new tabobject('submissions', $CFG->wwwroot.'/mod/assignment/'.self::SUBMISSIONS_FILE."?id=".$this->cm->id, get_string('submissions', 'assignment_peerreview').' ('.$this->count_real_submissions(0).')');
		$row[] = new tabobject('analysis', $CFG->wwwroot.'/mod/assignment/type/peerreview/'.self::ANALYSIS_FILE."?id=".$this->cm->id."&a=".$this->assignment->id, get_string('analysis', 'assignment_peerreview'));
		$tabs[] = $row;
		print_tabs($tabs, $current);
    }

    //--------------------------------------------------------------------------
    // Sets all unset calculatable grades
	function print_analysis() {
		global $CFG;

        require_once($CFG->libdir.'/tablelib.php');
        $CFG->stylesheets[] = $CFG->wwwroot . '/mod/assignment/type/peerreview/'.self::STYLES_FILE;

        $navigation = build_navigation(get_string('analysis','assignment_peerreview'), $this->cm);
        print_header_simple(format_string($this->assignment->name,true), "", $navigation,
                '', '', true, update_module_button($this->cm->id, $this->course->id, $this->strassignment), navmenu($this->course, $this->cm));
        $this->print_peerreview_tabs('analysis');

		// Help on peer review benefits
		echo '<div style="text-align:center;margin:0 0 10px 0;">';
		helpbutton('benefitsofpeerreview', get_string('benefitsofpeerreview','assignment_peerreview'), 'assignment/type/peerreview', true,true);

        // Submission stats table
        $table = new flexible_table('mod-assignment-peerreview-submission-stats');
        $tablecolumns = array('statistic', 'value');
        $table->define_columns($tablecolumns);
        $tableheaders = array(
            get_string('statistic','assignment_peerreview'),
            get_string('value','assignment_peerreview')
        );
        $table->define_headers($tableheaders);
        $table->sortable(false);
        $table->pageable(false);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->set_attribute('class', 'generalbox');
        $table->column_style_all('padding', '5px 10px');
        $table->column_style_all('text-align','left');
        $table->column_style_all('vertical-align','middle');
        $table->setup();

        $submissionStats = $this->get_submission_statistics();
        print_heading(get_string('analysislabelsubmissions','assignment_peerreview'),"center",2);

        $label = get_string('analysislabelnumberofstudents','assignment_peerreview');
        $value = $submissionStats->numberOfStudents;
        $table->add_data(array($label,$value));

        $label = get_string('analysislabelnumberofsubmissions','assignment_peerreview');
        $value = $submissionStats->numberOfSubmissions;
        $table->add_data(array($label,$value));

        $label = get_string('analysislabelsubmissionrate','assignment_peerreview');
        $value = (int)($submissionStats->submissionRate*100).'%';
        $table->add_data(array($label,$value));

        $label = get_string('analysislabelaveragewait','assignment_peerreview');
        $value = $submissionStats->numberOfSubmissions>0?'':format_time((int)$submissionStats->averageWaitForFeedback);
        $table->add_data(array($label,$value));

        $label = get_string('analysislabelmedianwait','assignment_peerreview');
        $value = $submissionStats->numberOfSubmissions>0?'':format_time($submissionStats->medianWaitForFeedback);
        $table->add_data(array($label,$value));

        $table->print_html();


        // Review stats table
        $table = new flexible_table('mod-assignment-peerreview-review-stats');
        $tablecolumns = array('statistic', 'value', 'hints');
        $table->define_columns($tablecolumns);
        $tableheaders = array(
            get_string('statistic','assignment_peerreview'),
            get_string('value','assignment_peerreview'),
            get_string('advice','assignment_peerreview'),
        );
        $table->define_headers($tableheaders);
        $table->sortable(false);
        $table->pageable(false);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->set_attribute('class', 'generalbox');
        $table->column_style_all('padding', '5px 10px');
        $table->column_style_all('text-align','left');
        $table->column_style_all('vertical-align','middle');
        $table->setup();

        $reviewStats = $this->get_review_statistics();
        print_heading(get_string('analysislabelreviewing','assignment_peerreview'),"center",2);

        $label = get_string('analysislabelreviewscompleted','assignment_peerreview');
        $value = $reviewStats->numberOfReviews;
        $hints = '';
        if($reviewStats->numberOfReviews < self::REVIEW_FEEDBACK_MIN) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicereviewmin','assignment_peerreview', self::REVIEW_FEEDBACK_MIN);
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelreviewrate','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':(int)($reviewStats->reviewRate*100).'%';
        $hints = '';
        if($reviewStats->numberOfReviews>=self::REVIEW_FEEDBACK_MIN && $reviewStats->reviewRate < self::ACCEPTABLE_REVIEW_RATE) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicereviewrate','assignment_peerreview');
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelnormalisedaveragereviewtime','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time((int)$reviewStats->normalisedAverageReviewTime);
        $hints = '';
        if($reviewStats->numberOfReviews>=self::REVIEW_FEEDBACK_MIN && $reviewStats->normalisedAverageReviewTime < self::ACCEPTABLE_REVIEW_TIME) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicereviewtime','assignment_peerreview');
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelaveragereviewtime','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time((int)$reviewStats->averageReviewTime);
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelstddevreviewtime','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time((int)$reviewStats->stdDevReviewTime);
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelminreview','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time($reviewStats->minReviewTime);
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelmaxreview','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time($reviewStats->maxReviewTime);
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelnormalisedaveragecomment','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':(int)$reviewStats->normalisedAverageCommentLength.' '.get_string('characters','assignment_peerreview');
        $hints = '';
        if($reviewStats->numberOfReviews>=self::REVIEW_FEEDBACK_MIN && $reviewStats->normalisedAverageCommentLength < self::ACCEPTABLE_COMMENT_LENGTH) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicecommentlength','assignment_peerreview');
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelaveragecomment','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':(int)$reviewStats->averageCommentLength.' '.get_string('characters','assignment_peerreview');
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelstddevcomment','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':(int)$reviewStats->stdDevCommentLength.' '.get_string('characters','assignment_peerreview');
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelmincomment','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':$reviewStats->minCommentLength.' '.get_string('characters','assignment_peerreview');
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelmaxcomment','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':$reviewStats->maxCommentLength.' '.get_string('characters','assignment_peerreview');
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelflags','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':$reviewStats->flags;
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelflagrate','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':(int)($reviewStats->flagRate*100).'%';
        $hints = '';
        if($reviewStats->numberOfReviews>=self::REVIEW_FEEDBACK_MIN && $reviewStats->flagRate > self::ACCEPTABLE_FLAG_RATE) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('adviceflagrate','assignment_peerreview');
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelmoderations','assignment_peerreview');
        $value = $reviewStats->numberOfModerations;
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('analysislabelmoderationrate','assignment_peerreview');
        $value = (int)($reviewStats->moderationRate*100).'%';
        $hints = '';
        if($reviewStats->moderationRate > self::ACCEPTABLE_MODERATION_RATE) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicemoderationrate','assignment_peerreview');
        }
        $table->add_data(array($label,$value,$hints));

        $table->print_html();

        // Feedback Attention table
        print_heading(get_string('analysislabelfeedbackattention','assignment_peerreview'),"center",2);
        $table = new flexible_table('mod-assignment-peerreview-feedback-attention-stats');
        $tablecolumns = array('statistic', 'value', 'hints');
        $table->define_columns($tablecolumns);
        $tableheaders = array(
            get_string('statistic','assignment_peerreview'),
            get_string('value','assignment_peerreview'),
            get_string('advice','assignment_peerreview'),
        );
        $table->define_headers($tableheaders);
        $table->sortable(false);
        $table->pageable(false);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->set_attribute('class', 'generalbox');
        $table->column_style_all('padding', '5px 10px');
        $table->column_style_all('text-align','left');
        $table->column_style_all('vertical-align','middle');
        $table->setup();

        $label = get_string('numberofreviewsviewed','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':$reviewStats->numberOfReviewsViewed;
        $hints = '';
        if($reviewStats->numberOfReviews < self::REVIEW_FEEDBACK_MIN) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicereviewmin','assignment_peerreview', self::REVIEW_FEEDBACK_MIN);
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('reviewattentionrate','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':(int)($reviewStats->reviewAttentionRate*100).'%';
        $hints = '';
        if($reviewStats->numberOfReviews>=self::REVIEW_FEEDBACK_MIN && $reviewStats->reviewAttentionRate < self::ACCEPTABLE_REVIEW_RATE) {
            $hints .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
            $hints .= get_string('advicereviewattentionrate','assignment_peerreview');
        }
        $table->add_data(array($label,$value,$hints));

        $label = get_string('numberofreviewviews','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':$reviewStats->numberOfReviewViews;
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('averageviewrate','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':$reviewStats->averageViewRate;
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('averageperiodbewtweenreviewandview','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time((int)$reviewStats->averagePeriodBewtweenReviewAndView);
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $label = get_string('medianperiodbewtweenreviewandview','assignment_peerreview');
        $value = $reviewStats->numberOfReviews<self::REVIEW_FEEDBACK_MIN?'':format_time($reviewStats->medianPeriodBewtweenReviewAndView);
        $hints = '';
        $table->add_data(array($label,$value,$hints));

        $table->print_html();

        // Criteria stats table
        $table = new flexible_table('mod-assignment-peerreview-criteria-stats');
        $tablecolumns = array('criterion', 'checkedcount', 'checkedrate','advice');
        $table->define_columns($tablecolumns);
        $tableheaders = array(
            get_string('criterion','assignment_peerreview'),
            get_string('analysislabelcheckedcount','assignment_peerreview'),
            get_string('analysislabelcheckedrate','assignment_peerreview'),
            get_string('advice','assignment_peerreview')
        );
        $table->define_headers($tableheaders);
        $table->sortable(false);
        $table->pageable(false);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->set_attribute('class', 'generalbox');
        $table->column_style_all('padding', '5px 10px');
        $table->column_style_all('vertical-align','middle');
        $table->column_style('criterion','text-align','left');
        $table->column_style('checkedcount','text-align','center');
        $table->column_style('checkedrate','text-align','center');
        $table->column_style('advice','text-align','left');
        $table->setup();

        $criteriaStats = $this->get_criteria_statistics();

        print_heading(get_string('analysislabelcriteria','assignment_peerreview'),"center",2);
        $options = new object;
        $options->para = false;
        foreach($criteriaStats as $criterion) {
            $criterionText = format_text(($criterion->textshownatreview!=''?$criterion->textshownatreview:$criterion->textshownwithinstructions),FORMAT_MOODLE,$options);
            $criterionCount = $criterion->count;
            $criterionRate = (int)($criterion->rate*100).'%';
            $hint = '';
            if($criterion->rate < self::ACCEPTABLE_CHECKED_RATE) {
                $hint .= '<img src="'.$CFG->wwwroot.'/mod/assignment/type/peerreview/images/alert.gif" style="vertical-align:middle;" />&nbsp;';
                $hint .= get_string('advicecheckedrate','assignment_peerreview');
            }
            $table->add_data(array($criterionText, $criterionCount, $criterionRate, $hint));
        }

        $table->print_html();

		echo '</div>';
		$this->view_footer();
	}
}

require_once($CFG->libdir.'/formslib.php');

class mod_assignment_peerreview_edit_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        // visible elements
        $mform->addElement('htmleditor', 'text', get_string('submission', 'assignment'), array('cols'=>60, 'rows'=>30));
        $mform->setType('text', PARAM_RAW); // to be cleaned before display
        $mform->addRule('text', get_string('required'), 'required', null, 'client');

		// hidden params
		$mform->addElement('hidden', 'id', $this->_customdata['id']);
		$mform->setType('id', PARAM_INT);
		if(array_key_exists('a',$this->_customdata)) {
			$mform->addElement('hidden', 'a', $this->_customdata['a']);
			$mform->setType('a', PARAM_INT);
		}
		if(array_key_exists('userid',$this->_customdata)) {
			$mform->addElement('hidden', 'userid', $this->_customdata['userid']);
			$mform->setType('userid', PARAM_INT);
		}
		$mform->addElement('hidden', 'save', 'true');
		$mform->setType('save', PARAM_TEXT);

		// submit button
		if(array_key_exists('userid',$this->_customdata)) {
			$warning = null;
		}
		else {
			$warning = array('onclick'=>'return confirm("'.get_string('singleuploadwarning','assignment_peerreview').' '.get_string('singleuploadquestion','assignment_peerreview').'");');
		}
        $mform->addElement('submit', 'submitbutton', get_string('submit'),$warning);
    }
}
