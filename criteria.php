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
 * Peer review criteria setting page
 *
 * @package    mod
 * @subpackage peerreview
 * @copyright  2012 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Extends the moodleform class to define the criteria page
 *
 */
class peerreview_criteria_form extends moodleform {

    // Form definition
    function definition() {
        global $CFG, $DB, $OUTPUT;

        // Create form object
        $mform =& $this->_form;

        // Pass on module ID and assignment ID
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'peerreviewid', $this->_customdata['peerreviewid']);
        $mform->setType('peerreviewid', PARAM_INT);

// Help on review process
        $myhelp =  '<div style="text-align:center;margin-left:100px;">'
            . get_string('criteriawriting','peerreview');
        $myhelp = $myhelp . $OUTPUT->help_icon('criteriawriting', 'peerreview', true);
        $myhelp = $myhelp .'</div>';
        $mform->addElement('static', '', '', $myhelp);

        // Define criteria and repeat
        $repeatarray=array();
        $repeatarray[] = $mform->createElement('header', '', get_string('criterion', 'peerreview').' {no}');
        $description = get_string('citerionwithdescription', 'peerreview');
        $repeatarray[] = $mform->createElement('text', 'criterionDescription', $description, array('size' => '80', 'class' => 'criteriaField'));
        $review = get_string('citerionatreview', 'peerreview');
        $repeatarray[] = $mform->createElement('text', 'criterionReview', $review, array('size' => '80', 'class' => 'criteriaField'));
        $valuestring = get_string('valueofcriterion', 'peerreview');
        $valueparameters = array('size' => '3', 'onkeyup' => 'M.peerreview.updateTotal();',
                                 'onchange' => 'M.peerreview.updateTotal();');
        $repeatarray[] = $mform->createElement('text', 'value', $valuestring, $valueparameters);
        $repeatno = $DB->count_records('peerreview_criteria', array('peerreview'=>$this->_customdata['peerreviewid']));
        $repeatno = $repeatno==0?3:$repeatno+2;
        $repeateloptions = array();
        $repeateloptions['criterionReview']['disabledif'] = array('criterionDescription','eq','');
        $morestring = get_string('addtwomorecriteria', 'peerreview');
        $this->repeat_elements($repeatarray, $repeatno,$repeateloptions, 'option_repeats', 'option_add_fields', 2, $morestring);
        $mform->setType('criterionDescription', PARAM_RAW);
        $mform->setType('criterionReview', PARAM_RAW);
        $mform->setType('value', PARAM_INT);

        // Show mark summary
        $mform->addElement('header', 'marksummary', get_string('marksummary', 'peerreview'));
        $mform->addHelpButton('marksummary', 'marksummary', 'peerreview');
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded('marksummary');
        }
        $label = get_string('valueofcriteria', 'peerreview');
        $placeholder = HTML_WRITER::tag('div', '&nbsp;', array('id' => 'totalOfValues', 'class' => 'markSummaryValue'));
        $mform->addElement('static', '', $label, $placeholder);

        $label = get_string('rewardforreviews', 'peerreview', $this->_customdata['reviewreward']);
        $placeholder = HTML_WRITER::tag('div', 2*$this->_customdata['reviewreward'], array('id' => 'rewardCell', 'class' => 'markSummaryValue'));
        $mform->addElement('static', '', $label, $placeholder);
        $mform->addElement('hidden', 'reviewreward', $this->_customdata['reviewreward']);
        $mform->setType('reviewreward', PARAM_INT);

        $label = get_string('totalmarksabove', 'peerreview');
        $placeholder = HTML_WRITER::tag('div', '&nbsp;', array('id' => 'totalOfMarksAbove', 'class' => 'markSummaryValue'));
        $mform->addElement('static', '', $label, $placeholder);

        $label = get_string('totalmarksforgrade', 'peerreview');
        $placeholder = HTML_WRITER::tag('div', $this->_customdata['grade'], array('class' => 'markSummaryValue'));
        $mform->addElement('static', '', $label, $placeholder);
        $mform->addElement('hidden', 'grade', $this->_customdata['grade']);
        $mform->setType('grade', PARAM_INT);

        $label = get_string('difference', 'peerreview');
        $placeholder = HTML_WRITER::tag('div', '&nbsp;', array('id' => 'peerReviewDifference', 'class' => 'markSummaryValue'));
        $mform->addElement('static', 'difference', $label, $placeholder);

        // Buttons for submit, reset and cancel
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('saveanddisplay','peerreview'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    //--------------------------------------------------------------------------
    // Form validation after submission
    function validation($data, $files) {
        $errors = array();
        $sum = 0;
        foreach($data['value'] as $value) {
            $sum += (int)$value;
        }
        $sum += 2*(int)($data['reviewreward']);
        if($sum != $data['grade']) {
            $errors['difference'] = get_string('marksdontaddup','peerreview');
        }

        return $errors;
    }
}

require_once($CFG->dirroot.'/mod/peerreview/lib.php');
require_once($CFG->dirroot.'/mod/peerreview/locallib.php');

// Get course ID and assignment ID
$cmid = required_param('id', PARAM_INT);
$peerreviewid  = required_param('peerreviewid', PARAM_INT);
$cm = get_coursemodule_from_id('peerreview', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
if (! $peerreview = $DB->get_record("peerreview", array('id' => $peerreviewid))) {
    print_error('invalidid', 'assignment');
}

// Check user is logged in and capable of grading
require_login($course, false, $cm);
require_capability('mod/assignment:grade', $context);

// Set up the page
$attributes = array('peerreviewid' => $peerreview->id, 'id' => $cm->id);
$PAGE->set_url('/mod/peerreview/upload.php', $attributes);
$PAGE->set_title(format_string($peerreview->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Get form description and create form
$formparameters = array(
    'id'           => $cm->id,
    'peerreviewid' => $peerreviewid,
    'grade'        => $peerreview->grade,
    'reviewreward' => $peerreview->reviewreward
);
$mform = new peerreview_criteria_form(null, $formparameters);

// Redirect if form was cancelled
if ($mform->is_cancelled()){
    $viewurl = new moodle_url('/mod/peerreview/view.php', array('id' => $cm->id));
    redirect($viewurl, get_string('updatecancelled', 'peerreview'), 0);
}

// Gather and store gathered data
else if ($fromform = $mform->get_data()) {

    // Translate form into database record object
    for($i=0; $i < $fromform->option_repeats; $i++) {
        if($fromform->criterionDescription[$i] != '') {
            $criterion = new stdClass();
            $criterion->peerreview = $peerreviewid;
            $criterion->ordernumber = $i;
            $criterion->textshownwithinstructions = $fromform->criterionDescription[$i];
            $criterion->textshownatreview = $fromform->criterionReview[$i];
            $criterion->value = $fromform->value[$i];

            // Insert/Update record in database
            if($existingRecord = $DB->get_record('peerreview_criteria',array('peerreview'=>$peerreviewid,'ordernumber'=>$i))) {
                $criterion->id = $existingRecord->id;
                $DB->update_record('peerreview_criteria',$criterion);
            }
            else {
                $DB->insert_record('peerreview_criteria',$criterion);
            }
        }
    }

    // Remove any unneeded criteria in database
    $deleteparams = array('peerreviewid' => $peerreviewid, 'numcriteria' => $criterion->ordernumber);
    $DB->delete_records_select('peerreview_criteria','peerreview=:peerreviewid AND ordernumber>:numcriteria', $deleteparams);

    // Redirect to criteria below description
    redirect($CFG->wwwroot.'/mod/peerreview/view.php?id='.$cm->id.'#criteria', get_string('criteriaupdated', 'peerreview'), 3);
}

// Show form (possibly new form, updated form or form containing invalid data)
else {

    // Output starts here
    echo $OUTPUT->header();
    echo peerreview_tabs($cm->id, $peerreview->id, 'criteria');

    if(optional_param('updated',false,PARAM_BOOL)) {
        echo $OUTPUT->notification(get_string('criteriaupdated','peerreview'), 'notifysuccess');
    }

    if($DB->record_exists('peerreview_review', array('peerreview' => $peerreview->id, 'completed' => '1'))) {
        echo $OUTPUT->notification(get_string('criteriachangewarning','peerreview'), 'notifyproblem');
    }

    // Get criteria from database
    if($criteriaList = $DB->get_records_list('peerreview_criteria','peerreview',array($peerreviewid),'ordernumber')) {

        // Fill form with data
        $toform = new stdClass();
        $toform->criterionDescription = array();
        $toform->criterionReview = array();
        $toform->value = array();
        foreach($criteriaList as $i=>$criterion) {
            $toform->criterionDescription[] = $criterion->textshownwithinstructions;
            $toform->criterionReview[] = $criterion->textshownatreview;
            $toform->value[] = (int)($criterion->value);
        }
        $mform->set_data($toform);

    }
    else {
        echo $OUTPUT->notification(get_string('mustentercriteria','peerreview'), 'notifyproblem');
    }

    // Show form
    $mform->display();
    $jsmodule = array(
        'name' => 'peerreview',
        'fullpath' => '/mod/peerreview/module.js',
        'requires' => array(),
        'strings' => array(),
    );
    $arguments = array($peerreview->grade, $peerreview->reviewreward);
    $PAGE->requires->js_init_call('M.peerreview.initMarkSummary',$arguments,false,$jsmodule);
}

echo $OUTPUT->footer();