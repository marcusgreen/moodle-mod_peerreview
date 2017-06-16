<?php

require_once($CFG->libdir.'/formslib.php');

class mod_peerreview_edit_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        // hidden params
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'peerreviewid', $this->_customdata['peerreviewid']);
        $mform->setType('peerreviewid', PARAM_INT);
        if(array_key_exists('userid',$this->_customdata)) {
            $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
            $mform->setType('userid', PARAM_INT);
        }
        $mform->addElement('hidden', 'action', 'onlinetext');
        $mform->setType('action', PARAM_ALPHA);

        // visible elements
        $mform->addElement('editor', 'onlinetext', get_string('onlinesubmission', 'peerreview'), array('cols'=>100, 'rows'=>20));
        $mform->setType('onlinetext', PARAM_RAW); // to be cleaned before display
        $mform->setDefault('onlinetext', 'ciao');
        // submit button
        if(array_key_exists('userid',$this->_customdata)) {
            $this->add_action_buttons(true, get_string('submit').'...');
        }
        else {
            $mform->addElement('submit', 'submitbutton', get_string('submit').'...');
        }
    }
    function validation($data, $files) {
        $errors = array();
        if (empty($data['onlinetext']['text']) || trim(strip_tags($data['onlinetext']['text'])=='')) {
            $errors['onlinetext'] = get_string('required');
        }
        return $errors;
    }
}

class mod_peerrview_upload_form extends moodleform {
    function definition() {
        $mform = $this->_form;

        // hidden params
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'peerreviewid', $this->_customdata['peerreviewid']);
        $mform->setType('peerreviewid', PARAM_INT);
        if(array_key_exists('userid',$this->_customdata)) {
            $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
            $mform->setType('userid', PARAM_INT);
        }
        $mform->addElement('hidden', 'action', 'uploadfile');
        $mform->setType('action', PARAM_ALPHA);

        // TODO convert this to a filemanager
        // $data = new stdClass();
        // $submissionid = 0;
        // $data = file_prepare_standard_filemanager($data,
        //                                           'files',
        //                                           $this->_customdata['options'],
        //                                           $this->peerreview->get_context(),
        //                                           'peerreview_file',
        //                                           'submission_file',
        //                                           $submissionid);
        // $mform->addElement('filemanager', 'peerreview_file', get_string('uploadafile'), null, $this->_customdata['options']);
        if (array_key_exists('accepted_types', $this->_customdata['options'])) {
            $accepted_types = $this->_customdata['options']['accepted_types'];
        } else {
            $accepted_types = '';
        }
        $mform->addElement('filepicker', 'peerreview_file', get_string('uploadafile', 'peerreview', $accepted_types), null, $this->_customdata['options']);
        $mform->addRule('peerreview_file', get_string('uploadnofilefound'), 'required', null, 'client');

        // Submit button
        if(array_key_exists('userid',$this->_customdata)) {
            $this->add_action_buttons(true, get_string('submituploadedfile', 'peerreview'));
        }
        else {
            $mform->addElement('submit', 'submitbutton', get_string('submituploadedfile', 'peerreview'));
        }
    }

    function validation($data, $files) {
        $errors = array();
//        $mform = $this->_form;
//        $content = $mform->get_file_content('assignment_file');
//        if(empty($content)) {
//            $errors['assignment_file'] = get_string('required');
//        }
//        echo '<pre>'.print_r($content,true).'</pre>';
//        if (empty($files['assignment_file'])) {
//        }
//        echo '<pre>'.print_r($data,true).'</pre>';
//        echo '<pre>'.print_r($files,true).'</pre>';
        return $errors;
    }
}