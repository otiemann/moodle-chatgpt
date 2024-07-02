<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_chatgpt', 'ChatGPT Plugin'));

    $settingspage = new admin_settingpage('local_chatgpt_settings', 'ChatGPT Einstellungen');

    $settingspage->add(new admin_setting_configtext(
        'local_chatgpt/apikey',
        'API Schlüssel',
        'Geben Sie hier Ihren API-Schlüssel für ChatGPT ein.',
        '',
        PARAM_TEXT
    ));

    $ADMIN->add('local_chatgpt', $settingspage);
}

// Neue Seite für die Eingabe des Prompts
$ADMIN->add('local_chatgpt', new admin_externalpage(
    'local_chatgpt_prompt',
    'ChatGPT-Integration',
    new moodle_url('/local/chatgpt/prompt.php')
));