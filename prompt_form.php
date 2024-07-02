<?php
require_once('../../config.php');
require_login();

$courseid = required_param('id', PARAM_INT); // Kurs-ID aus der URL holen
$course = get_course($courseid); // Kursobjekt holen
$context = context_course::instance($courseid); // Kurskontext holen

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/chatgpt/prompt_form.php', array('id' => $courseid)));
$PAGE->set_title('Prompt Eingeben');
$PAGE->set_heading('Prompt Eingeben im Kurs: ' . $course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading('Geben Sie Ihren Prompt ein:');

echo '<form method="post" action="process_prompt.php">';
echo '<label for="module_name">Modulname (optional):</label><br>';
echo '<input type="text" id="module_name" name="module_name"><br><br>';
echo '<label for="prompt">Prompt:</label><br>';
echo '<textarea name="prompt" id="prompt" rows="10" cols="50"></textarea><br>';
echo '<input type="hidden" name="courseid" value="' . $courseid . '">'; // Kurs-ID im Formular übergeben
echo '<input type="submit" value="Absenden">';
echo '</form>';

echo $OUTPUT->footer();
