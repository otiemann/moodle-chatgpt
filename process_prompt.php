<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$prompt = required_param('prompt', PARAM_TEXT);
$module_name = optional_param('module_name', '', PARAM_TEXT);
$response_type = required_param('response_type', PARAM_ALPHA);

$course = get_course($courseid);
$context = context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/chatgpt/process_prompt.php', array('id' => $courseid)));
$PAGE->set_title('Prompt Ergebnis');
$PAGE->set_heading('Prompt Ergebnis im Kurs: ' . $course->fullname);

// API-Aufruf an ChatGPT
$response = call_chatgpt_api(get_config('local_chatgpt', 'apikey'), $prompt);

// HTML-Formatierung der Antwort
$html_response = '<div class="chatgpt-response" style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">' . $response . '</div>';

// Erstellen eines neuen Moduls im Kurs
$moduleinfo = new stdClass();
$moduleinfo->course = $course->id;
$moduleinfo->name = empty($module_name) ? substr($prompt, 0, 50) : $module_name;
$moduleinfo->section = 0;
$moduleinfo->visible = 1;
$moduleinfo->visibleoncoursepage = 1;

if ($response_type === 'page') {
    $moduleinfo->modulename = 'page';
    $moduleinfo->module = $DB->get_field('modules', 'id', array('name' => 'page'));
    $moduleinfo->intro = ''; // Prompt aus der Einleitung entfernt
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->content = $html_response;
    $moduleinfo->contentformat = FORMAT_HTML;
    $moduleinfo->printintro = 0; // Einleitung nicht anzeigen
    $moduleinfo->printlastmodified = 1;
} elseif ($response_type === 'label') {
    $moduleinfo->modulename = 'label';
    $moduleinfo->module = $DB->get_field('modules', 'id', array('name' => 'label'));
    $moduleinfo->intro = '<div class="chatgpt-label" style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #ddd;">' . $html_response . '</div>';
    $moduleinfo->introformat = FORMAT_HTML;
}

$moduleinfo = add_moduleinfo($moduleinfo, $course);

echo $OUTPUT->header();
echo $OUTPUT->heading('Prompt-Ergebnis wurde gespeichert.');
echo $html_response; // Nur die formatierte Antwort anzeigen
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
    $content = $response['choices'][0]['message']['content'];

    // Verbesserte Formatierung für Code-Blöcke mit sichtbarem Sprachnamen
    $content = preg_replace_callback('/```(\w+)?\s*([\s\S]*?)```/', function($matches) {
        $language = !empty($matches[1]) ? $matches[1] : 'plaintext';
        $code = trim($matches[2]);
        return "<div class='code-block' style='margin-top: 20px; margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;'>
                    <div class='language-label' style='background-color: #f0f0f0;
                    padding: 5px 10px;
                    font-weight: bold;
                    border-bottom: 1px solid #ddd;
                    display: inline-block;
                    margin-bottom: 10px;
                    border-radius: 0 0 4px 4px;
                }'>{$language}</div>
                    <pre style='margin: 0;
                    padding: 10px;
                    background-color: #f8f8f8;'><code class='language-{$language}'>{$code}</code></pre>
                </div>";
    }, $content);

    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);

    $content = preg_replace('/(\s+)-\s+([\w\s]+):/', "\n\n- $2:", $content);

    $content = preg_replace('/(\s*)(\d+\.)\s+([\w\s]+):/', "\n\n$2 $3:", $content);

    return $content;
}
