<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class prompt_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('textarea', 'prompt', get_string('prompt', 'local_chatgpt'), 'wrap="virtual" rows="10" cols="50"');
        $mform->setType('prompt', PARAM_TEXT);
        $mform->addRule('prompt', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submit', 'local_chatgpt'));
    }
}