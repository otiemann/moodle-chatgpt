<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$prompt = required_param('prompt', PARAM_TEXT);
$module_name = optional_param('module_name', '', PARAM_TEXT);

$course = get_course($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/chatgpt/process_prompt.php', array('id' => $courseid)));
$PAGE->set_title('Prompt Ergebnis');
$PAGE->set_heading('Prompt Ergebnis im Kurs: ' . $course->fullname);

// API-Aufruf an ChatGPT
$response = call_chatgpt_api(get_config('local_chatgpt', 'apikey'), $prompt);

// Erstellen einer neuen Textseite im Kurs
$moduleinfo = new stdClass();
$moduleinfo->modulename = 'page';
$moduleinfo->module = $DB->get_field('modules', 'id', array('name' => 'page'));
$moduleinfo->section = 0; // Abschnitt 0 ist der allgemeine Bereich
$moduleinfo->visible = 1;
$moduleinfo->visibleoncoursepage = 1;
$moduleinfo->course = $course->id;
$moduleinfo->name = empty($module_name) ? substr($prompt, 0, 50) : $module_name; // Verwende den eingegebenen Namen oder die ersten 50 Zeichen des Prompts
$moduleinfo->intro = $prompt;
$moduleinfo->introformat = FORMAT_HTML;
$moduleinfo->content = $response; // Hier wird die Response von ChatGPT gespeichert
$moduleinfo->contentformat = FORMAT_HTML;
$moduleinfo->printintro = 1; // oder 0, je nach Bedarf
$moduleinfo->printlastmodified = 1; // oder 0, je nach Bedarf

$moduleinfo = add_moduleinfo($moduleinfo, $course);

echo $OUTPUT->header();
echo $OUTPUT->heading('Prompt Ergebnis wurde gespeichert.');
echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $courseid)));
echo $OUTPUT->footer();

function call_chatgpt_api($apikey, $prompt) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = array(
        'model' => 'gpt-4o',
        'messages' => array(
            array('role' => 'user', 'content' => $prompt)
        ),
        'max_tokens' => 1000,
    );

    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . $apikey . "\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'timeout' => 60, // Timeout von 60 Sekunden hinzugefügt
        ),
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        // Fehlerbehandlung
        return "Fehler bei der API-Anfrage oder Timeout erreicht";
    }

    $response = json_decode($result, true);
    return $response['choices'][0]['message']['content'];
}
