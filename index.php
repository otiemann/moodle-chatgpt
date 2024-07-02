<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/classes/form/prompt_form.php');

admin_externalpage_setup('local_chatgpt');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$mform = new prompt_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', array('section' => 'local_chatgpt')));
} else if ($data = $mform->get_data()) {
    $apikey = get_config('local_chatgpt', 'apikey');
    $prompt = $data->prompt;

    // API-Aufruf an ChatGPT
    $response = call_chatgpt_api($apikey, $prompt);

    // Textseite hinzufügen
    add_textpage_to_course($response, $COURSE->id);

    redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

function call_chatgpt_api($apikey, $prompt) {
    $url = 'https://api.openai.com/v1/models';
    $data = array(
        'model' => 'gpt-4o',
        'prompt' => $prompt,
        'max_tokens' => 150
    );

    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . $apikey . "\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ),
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        // Fehlerbehandlung
        return null;
    }

    $response = json_decode($result, true);
    return $response['choices'][0]['text'];
}

function add_textpage_to_course($response, $courseid) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->dirroot.'/course/modlib.php');

    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'page';
    $moduleinfo->course = $courseid;
    $moduleinfo->name = 'ChatGPT Response';
    $moduleinfo->intro = $response;
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->content = $response;
    $moduleinfo->contentformat = FORMAT_HTML;
    $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
    $moduleinfo->printheading = 1;
    $moduleinfo->printintro = 1;
    $moduleinfo->printlastmodified = 1;
    $moduleinfo->section = 0; // Add page to the first section
    $moduleinfo->visible = 1;
    $moduleinfo->visibleoncoursepage = 1;

    return add_moduleinfo($moduleinfo, get_course($courseid));
}
