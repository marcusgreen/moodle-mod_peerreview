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
 * Library of interface functions and constants for module peerreview
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the peerreview specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2013 Michael de Raadt (michaeld@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('peerreview_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function peerreview_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_ADVANCED_GRADING:        return false;
        default:                              return null;
    }
}

/**
 * Saves a new instance of the peerreview into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $peerreview An object from the form in mod_form.php
 * @param mod_peerreview_mod_form $mform
 * @return int The id of the newly inserted peerreview record
 */
function peerreview_add_instance(stdClass $peerreview, mod_peerreview_mod_form $mform = null) {
    global $DB,$CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');
    $peerreview->timecreated = time();

    # You may have to add extra stuff in here

    $ret = $DB->insert_record('peerreview', $peerreview);

    //MMPR-6
    //add grade item
    if ($ret){
        $peerreview->id = $ret;
        peerreview_grade_item_update($peerreview);
    }

    //creates events in the calendar
    $event = new stdClass();
    $event->type        = CALENDAR_EVENT_TYPE_ACTION;
    $event->name        = $peerreview->name;
    $event->description = format_module_intro('peerreview', $peerreview, $peerreview->coursemodule);
    $event->courseid    = $peerreview->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'peerreview';
    $event->instance    = $ret;
    $event->eventtype   = PR_EVENT_TYPE_DO;
    $event->timestart   = $peerreview->duedate;
    $event->timesort    = $peerreview->duedate;
    $event->timeduration = 0;

    calendar_event::create($event);


    return $ret;
}

/**
 * Updates an instance of the peerreview in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $peerreview An object from the form in mod_form.php
 * @param mod_peerreview_mod_form $mform
 * @return boolean Success/Fail
 */
function peerreview_update_instance(stdClass $peerreview, mod_peerreview_mod_form $mform = null) {
    global $DB;

    $peerreview->timemodified = time();
    $peerreview->id = $peerreview->instance;

    # You may have to add extra stuff in here #

    return $DB->update_record('peerreview', $peerreview);
}

/**
 * Removes an instance of the peerreview from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function peerreview_delete_instance($id) {
    global $DB;

    if (! $peerreview = $DB->get_record('peerreview', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records('peerreview', array('id' => $peerreview->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function peerreview_user_outline($course, $user, $mod, $peerreview) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $peerreview the module instance record
 * @return void, is supposed to echp directly
 */
function peerreview_user_complete($course, $user, $mod, $peerreview) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in peerreview activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function peerreview_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link peerreview_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function peerreview_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see peerreview_get_recent_mod_activity()}
 *
 * @return void
 */
function peerreview_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function peerreview_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function peerreview_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of peerreview?
 *
 * This function returns if a scale is being used by one peerreview
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $peerreviewid ID of an instance of this module
 * @return bool true if the scale is used by the given peerreview instance
 */
function peerreview_scale_used($peerreviewid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of peerreview.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any peerreview instance
 */
function peerreview_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Creates or updates grade item for the give peerreview instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $peerreview instance object with extra cmidnumber and modname property
 * @return void
 */
function peerreview_grade_item_update(stdClass $peerreview,$grades=NULL) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($peerreview->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $peerreview->grade;
    $item['grademin']  = 0;

    grade_update('mod/peerreview', $peerreview->course, 'mod', 'peerreview', $peerreview->id, 0, $grades, $item);

}

/**
 * Update peerreview grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $peerreview instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function peerreview_update_grades(stdClass $peerreview, $userid = 0,$nullifnone=true) {
     global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
   // $grades = array(); // populate array of grade objects indexed by userid
     //mod MMPR-6
     $grade = new stdClass();
     $grade->userid   = $userid;
     $grade->rawgrade = $peerreview->grade;
     $grade->finalgrade = $peerreview->grade;

    //passo anche item grade
    $item = array();
    $item['itemname'] = clean_param($peerreview->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = 100;
    $item['grademin']  = 0;
 
    grade_update('mod/peerreview', $peerreview->course, 'mod', 'peerreview', $peerreview->id, 0, $grade,$item);
}


////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function peerreview_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for peerreview file areas
 *
 * @package mod_peerreview
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function peerreview_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the peerreview file areas
 *
 * @package mod_peerreview
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the peerreview's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function peerreview_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $USER, $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    // Check the activity exists.
    if (!$peerreview = $DB->get_record('peerreview', array('id'=>$cm->instance))) {
        return false;
    }

    // Check that the request is for a submission.
    if ($filearea !== 'submission') {
        return false;
    }

    // Gather the file information
    $submissionid = (int)array_shift($args);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_peerreview', 'submission', $submissionid, "timemodified", false);
    $file = array_pop($files);
    $filename = $file->get_filename();
    if ($file->is_directory()) {
        return false;
    }
    $options['filename'] = $filename;

    // If the file is the user's submission, send that file.
    require_once($CFG->dirroot.'/mod/peerreview/locallib.php');
    $submission = get_submission($peerreview->id);
    if($submission->id == $submissionid) {
        send_stored_file($file, 0, 0, true, $options);
    }

    // If the file is one the user is supposed to review, send that.
    $review = get_next_review($peerreview);
    $submission = get_submission($peerreview->id, $review->reviewee);

    // Set the file status to downloaded.
    if($review->reviewer == $USER->id) {
        $review->downloaded = 1;
        $review->timedownloaded = time();
        $review->timemodified = $review->timedownloaded;
        $DB->update_record('peerreview_review', $review);
    }

    send_stored_file($file, 0, 0, true, $options);


}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding peerreview nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the peerreview module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function peerreview_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the peerreview settings
 *
 * This function is called when the context for the page is a peerreview module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $peerreviewnode {@link navigation_node}
 */
function peerreview_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $peerreviewnode=null) {
}

//DEPRECATEDfrom 3.3
function peerreview_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;
    require_once($CFG->dirroot.'/mod/peerreview/locallib.php');
    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$peerreviews = get_all_instances_in_courses('peerreview', $courses)) {
        return;
    }

    $strpeerreview = get_string('modulename', 'peerreview');
    foreach ($peerreviews as $peerreview) {
        $cmid = $peerreview->coursemodule;
        $cm         = get_coursemodule_from_id('peerreview', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $cangrade = has_capability('mod/peerreview:grade', $context);
        $criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($peerreview->id),'ordernumber');
        $numberOfCriteria = 0;
        if(is_array($criteriaList)) {
            $criteriaList = array_values($criteriaList);
            $numberOfCriteria = count($criteriaList);
        }
        if ($cangrade){
            $submissions = $DB->get_records('peerreview_submissions', array('peerreview'=>$peerreview->id, 'grade'=> '-1'));
            if (count($submissions) > 0) {
                // MUST CHECK
                $str = '<div class="peerreview overview"><div class="name">'.
                    $strpeerreview.': <a '.($peerreview->visible ? '' : ' class="dimmed"').
                    ' href="'.$CFG->wwwroot.'/mod/peerreview/view.php?id='.$peerreview->coursemodule.'">'.
                    $peerreview->name.'</a></div></div>';

                if (empty($htmlarray[$peerreview->course]['peerreview'])) {
                    $htmlarray[$peerreview->course]['peerreview'] = $str;
                } else {
                    $htmlarray[$peerreview->course]['peerreview'] .= $str;
                }
            }

        } else {
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
            if(!$submission && isopen($peerreview)) {
                // MUST SUBMIT
                $str = '<div class="peerreview overview"><div class="name">'.
                    $strpeerreview.': <a '.($peerreview->visible ? '' : ' class="dimmed"').
                    ' href="'.$CFG->wwwroot.'/mod/peerreview/view.php?id='.$peerreview->coursemodule.'">'.
                    $peerreview->name.'</a></div>';

                if (empty($htmlarray[$peerreview->course]['peerreview'])) {
                    $htmlarray[$peerreview->course]['peerreview'] = $str;
                } else {
                    $htmlarray[$peerreview->course]['peerreview'] .= $str;
                }
            } else if ($numberOfReviewsCompleted < 2 && $numberOfReviewsAllocated > 0) {
                // MUST REVIEW
                $str = '<div class="peerreview overview"><div class="name">'.
                    $strpeerreview.': <a '.($peerreview->visible ? '' : ' class="dimmed"').
                    ' href="'.$CFG->wwwroot.'/mod/peerreview/view.php?id='.$peerreview->coursemodule.'">'.
                    $peerreview->name.'</a></div>';

                if (empty($htmlarray[$peerreview->course]['peerreview'])) {
                    $htmlarray[$peerreview->course]['peerreview'] = $str;
                } else {
                    $htmlarray[$peerreview->course]['peerreview'] .= $str;
                }
            }
        }
    }
}


//MOD MMPR-6
//return true or false or type depending from the completion status.
//completion statustrue + submitted + reviews done
function peerreview_get_completion_state($course,$cm,$userid,$type){
    global $CFG, $DB;

    //get peerreview
    if (!$peerreview = $DB->get_record('peerreview', array('id' => $cm->instance))) {
        return false;
    }

    require_once ("locallib.php");
    //se il flag è up
    if ($peerreview->completionsubmit) {

      //  return checkActionTodo($peerreview,$isTodo,$msg,$itemcount);

        //get peeerreviw
        $submission = get_submission($peerreview->id);
        //get review allcoated for the user
        $reviewsAllocated = get_reviews_allocated_to_student($peerreview->id, $userid);
        //init counter
        $numberOfReviewsAllocated  = 0;
        $numberOfReviewsDownloaded = 0;
        $numberOfReviewsCompleted  = 0;
        //count the review alocated, completed etc..
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
        if(!$submission && isopen($peerreview)) {
            // MUST SUBMIT
            return false;
        } else if ($numberOfReviewsCompleted < 2 && $numberOfReviewsAllocated > 0) {
            // MUST REVIEW
            return false;
        }else{
            //COMPLETED
            return true;
        }
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }

}


/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_peerreview_get_completion_active_rule_descriptions($cm) {
    global $DB;
    //get the peerreview
    $peerreview = $DB->get_record('peerreview',['id' => $cm->instance]);

    //if completionsubmit is enabled return the description
    if ($peerreview->completionsubmit==1){
        $descriptions[] = get_string('completionsubmit', 'peerreview');
        return $descriptions;
    }
    return [];
    
}

//check the visibility of the event in the dashboard
function mod_peerreview_core_calendar_is_event_visible(calendar_event $event) {
    global $USER, $DB; $USER;

    if ($event->modulename != 'peerreview'){
        return false;
    }
    //gets peerreview
    $peerreview = $DB->get_record('peerreview',['id' => $event->instance]);
    checkActionTodo($peerreview,$isTodo,$msglink,$itemcount);
    return $isTodo;
}

//builds the div to showin the dashboard.
function mod_peerreview_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory) {
    global $USER, $DB; $USER;

    if ($event->modulename != 'peerreview'){
        return false;
    }
    //gets peerreview
    $peerreview = $DB->get_record('peerreview',['id' => $event->instance]);

    $msglink= 'todo';
    $itemcount=1;
    checkActionTodo($peerreview,$isTodo,$msglink,$itemcount);
    $isopen = isopen($peerreview);
    $cm = get_fast_modinfo($event->courseid)->instances['peerreview'][$event->instance];
    //creates the entry on dashboard
    return $factory->create_instance(
        $msglink,
        new \moodle_url('/mod/peerreview/view.php', array('id' => $cm->id)),
        $itemcount,
        $isopen
    );
}

/**
 * Callback function that determines whether an action event should be showing its item count
 * based on the event type and the item count.
 *
 * @param calendar_event $event The calendar event.
 * @param int $itemcount The item count associated with the action event.
 * @return bool
 */
function mod_peerreview_core_calendar_event_action_shows_item_count(calendar_event $event, $itemcount = 0) {
    // List of event types where the action event's item count should be shown.
    $eventtypesshowingitemcount = [
        PR_EVENT_TYPE_DO
    ];
    // For mod_assign, item count should be shown if the event type is 'PR_EVENT_TYPE_DO' and there is one or more item count.
    return in_array($event->eventtype, $eventtypesshowingitemcount) && $itemcount > 0;
}

//auxiliar function. Check the action to do for the event caldendar, for the user
function checkActionTodo($peerreview, &$isTodo, &$msgAction,&$itemcount){
    global  $DB,$USER;

    require_once ("locallib.php");

    $isTodo=false;
    //check on action to perform
    $cm = get_coursemodule_from_instance('peerreview', $peerreview->id);
    $context = context_module::instance($cm->id);
    $cangrade = has_capability('mod/peerreview:grade', $context);
    if ($cangrade){
        $submissions = $DB->get_records('peerreview_submissions', array('peerreview'=>$peerreview->id, 'grade'=> '-1'));
        $itemcount = count($submissions);
        if ( $itemcount > 0) {
            // MUST CHECK
            $isTodo=true;
            $msgAction = get_string('mustcheck', 'peerreview');
        }

    } else {
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
        if(!$submission ) {
            // MUST SUBMIT
            $isTodo=true;
            $itemcount =1;
            $msgAction = get_string('mustsubmit', 'peerreview');
            return true;
        } else if ($numberOfReviewsCompleted < 2 && $numberOfReviewsAllocated > 0) {
            // MUST REVIEW
            $isTodo=true;
            $itemcount=2-$numberOfReviewsCompleted;
            $msgAction = get_string('mustreview', 'peerreview');
            return true;
        }

        return false;
    }
}


