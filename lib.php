<?php
function local_chatgpt_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/local/chatgpt/prompt_form.php', array('id' => $course->id));
        $navigation->add('Prompt Eingeben', $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/settings', ''));
    }
}

defined('MOODLE_INTERNAL') || die();

