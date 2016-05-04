<?php

defined('MOODLE_INTERNAL') || die();

$token = get_config('drillster', 'drillster_token');

if (empty($token)){
        
    $button = '<br><input type="button" onclick="window.location=\'https://www.drillster.com/oauth/authorize?client_id=' . $CFG->drillster_client_id . '&redirect_uri=' . $CFG->wwwroot . '/mod/drillster/update_token.php\'" value="' . get_string('settings_connect', 'mod_drillster') . '" name="allow" />';
    $settings->add(new admin_setting_heading('drillster_validation', '', $button));

} else {
        
    $button = '<br><input type="button" onclick="window.location=\'' . new moodle_url('/mod/drillster/update_token.php', array('reset' => 1)) . '\'" value="' . get_string('settings_disconnect', 'mod_drillster') . '" name="allow" />';
    $settings->add(new admin_setting_heading('drillster_validation', '', $button));
    
    $api = mod_drillster_api::getInstance();        
    $response = $api->get('user');
    
    if($data = $response->getData()){
       
        $html = '<b>' . $data->realName . '</b><br>';
        $html .= $data->memberSince . '<br>';
        $html .= $data->emailAddress . '<br>';
        $settings->add(new admin_setting_heading('drillster_user', get_string('settings_header', 'mod_drillster'), $html));

    }
}

$settings->add(new admin_setting_configtext('drillster_client_id', '', get_string('client_id', 'mod_drillster'), '', PARAM_RAW, 50));
$settings->add(new admin_setting_configtext('drillster_client_secret', '', get_string('client_secret', 'mod_drillster'), '', PARAM_RAW, 50));
