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
 * The main peerreview configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2013 Michael de Raadt (michaeld@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');

/**
 * Module instance settings form
 */
class mod_peerreview_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $COURSE,$DB;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('peerreviewname', 'peerreview'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'peerreviewname', 'peerreview');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        //MMPR-& add warnig message if there are submissionsfor this peerreview
        $submissions = $DB->get_records('peerreview_submissions', array('peerreview'=>$this->current->id));
        $itemcount = count($submissions);
        if ( $itemcount > 0) {
            $mform->addElement('html', '<div style="background-color: yellow; color:red; text-align: center;"><h4>' . get_string('warningalreadysubmitted', 'peerreview') .'</h4></div>');
        }
        //warning message end

        $name = get_string('allowsubmissionsfromdate', 'peerreview');
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name, array('optional'=>true));
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'peerreview');
        // $mform->setDefault('allowsubmissionsfromdate', time());
        $mform->setAdvanced('allowsubmissionsfromdate');

        $name = get_string('duedate', 'peerreview');
        $mform->addElement('date_time_selector', 'duedate', $name);
        $mform->addHelpButton('duedate', 'duedate', 'peerreview');
        $mform->setDefault('duedate', time()+7*24*3600);

        $name = get_string('cutoffdate', 'peerreview');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional'=>true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'peerreview');
        // $mform->setDefault('cutoffdate', time()+14*24*3600);
        $mform->setAdvanced('cutoffdate');

        // Future setting to send notifications of late submissions to teachers
        $mform->addElement('hidden', 'sendlatenotifications', 0);
        $mform->setType('sendlatenotifications', PARAM_BOOL);

        // Submission format
        $submissionFormats = array();
        $submissionFormats[SUBMIT_DOCUMENT] = get_string('submissionformatdocument','peerreview');
        $submissionFormats[ONLINE_TEXT] = get_string('submissionformatonlinetext','peerreview');
        $mform->addElement('select', 'submissionformat', get_string('submissionformat', 'peerreview'),$submissionFormats);
        $mform->setDefault('submissionformat', SUBMIT_DOCUMENT);

        // Get the list of file extensions and mime types
        $fileextensions = array();
        require_once("$CFG->dirroot/lib/filelib.php"); // for file types
        $mimetypes = get_mimetypes_array();
        $longestextension = max(array_map('strlen', array_keys($mimetypes)));
        foreach($mimetypes as $extension => $mimetypeandicon) {
            if($extension != 'xxx') {
                $padding = '';
                for($i=0; $i<$longestextension-strlen($extension); $i++) {
                    $padding .= '&nbsp;';
                }
                $mimetype = $mimetypeandicon['type'];
                if(strlen($mimetype) > 27) {
                    $mimetype = substr($mimetypeandicon['type'], 0, 27) . '...';
                }
                $fileextensions[$extension] = $extension.$padding.' ('.$mimetype.')';
            }
        }
        ksort($fileextensions, SORT_STRING);

        // File type restriction
        $attributes=array('style'=>'font-family:monospace;max-width:90%;');
        $mform->addElement('select', 'fileextension', get_string('fileextension', 'peerreview'), $fileextensions, $attributes);
        $mform->setType('fileextension', PARAM_TEXT);
        $mform->setDefault('fileextension', DEFAULT_FORMAT);
        $mform->disabledIf('fileextension', 'submissionformat', 'eq', ONLINE_TEXT);

        // Filesize restriction
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'peerreview'), $choices);
        // $mform->setDefault('maxbytes', $COURSE->maxbytes);
        $mform->disabledIf('maxbytes', 'submissionformat', 'eq', ONLINE_TEXT);

        // Allow submission of drafts
        $mform->addElement('hidden', 'submissiondrafts', 0);
        $mform->setType('submissiondrafts', PARAM_BOOL);
        // $mform->addElement('selectyesno', 'submissiondrafts', get_string('submissiondrafts', 'peerreview'));
        // $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'peerreview');
        // $mform->setDefault('submissiondrafts', 0);
        // $mform->setAdvanced('submissiondrafts';

        // Grades
        $grades = array();
        for($i=100; $i>0; $i--) {
            $grades[$i] = $i;
        }
        $mform->addElement('select', 'grade', get_string('grade'), $grades);
        $mform->setType('grade', PARAM_INT);
        $mform->setDefault('grade', 100);

        // Value of each review
        $rewards = array();
        for($i=50; $i>=0; $i--) {
            $rewards[$i] = "$i";
        }
        $mform->addElement('select', 'reviewreward', get_string('valueofreview', 'peerreview'), $rewards);
        $mform->setDefault('reviewreward', 10);
        $mform->addHelpButton('reviewreward', 'valueofreview', 'peerreview');

        // Currently Unsupported options
        $mform->addElement('hidden', 'selfreflection', 0);
        $mform->setType('selfreflection', PARAM_BOOL);
        $mform->addElement('hidden', 'blindreviews', 1);
        $mform->setType('blindreviews', PARAM_BOOL);
        $mform->addElement('hidden', 'blindmarking', 0);
        $mform->setType('blindmarking', PARAM_BOOL);


        // Start
        $mform->addElement('hidden', 'savedcomments', '');
        $mform->setType('savedcomments', PARAM_RAW);

        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['allowsubmissionsfromdate'] && $data['duedate']) {
            if ($data['allowsubmissionsfromdate'] >= $data['duedate']) {
                $errors['duedate'] = get_string('duedatevalidation', 'peerreview');
            }
        }
        if ($data['duedate'] && $data['cutoffdate']) {
            if ($data['duedate'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'peerreview');
            }
        }
        if ($data['allowsubmissionsfromdate'] && $data['cutoffdate']) {
            if ($data['allowsubmissionsfromdate'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'peerreview');
            }
        }

        if($data['grade'] && $data['reviewreward'] && $data['reviewreward']>$data['grade']/2) {
            $errors['reviewreward'] = get_string('rewardvalidation', 'peerreview');
        }

        return $errors;
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        // global $DB;

        // $ctx = null;
        // if ($this->current && $this->current->coursemodule) {
        //     $cm = get_coursemodule_from_instance('assign', $this->current->id, 0, false, MUST_EXIST);
        //     $ctx = context_module::instance($cm->id);
        // }
        // $assignment = new assign($ctx, null, null);
        // if ($this->current && $this->current->course) {
        //     if (!$ctx) {
        //         $ctx = context_course::instance($this->current->course);
        //     }
        //     $course = $DB->get_record('course', array('id'=>$this->current->course), '*', MUST_EXIST);
        //     $assignment->set_course($course);
        // }
        // $assignment->plugin_data_preprocessing($defaultvalues);
    }

    public function add_completion_rules() {
         $mform =& $this->_form;
         $mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'peerreview'));
         return array('completionsubmit');
        //return array();
    }

    public function completion_rule_enabled($data) {
         return !empty($data['completionsubmit']);
        //return false;
    }
}
