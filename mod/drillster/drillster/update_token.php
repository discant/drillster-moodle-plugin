<?php

require '../../config.php';

defined('MOODLE_INTERNAL') || die();

$reset = optional_param('reset', 0, PARAM_INT);

if ($reset == 1){
    set_config('drillster_token', '', 'drillster');
    redirect($CFG->wwwroot . '/admin/settings.php?section=modsettingdrillster');
}

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/mod/drillster/update_token.php', array('code' => $code));

$code = optional_param('code', '', PARAM_RAW);
$client_id = $CFG->drillster_client_id;
$client_secret = $CFG->drillster_client_secret;

//check for code
if(!empty($code)){
        
    //do a validation test of the settings
    $api = mod_drillster_api::getInstance();
    $response = $api->post('token', array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code
    ));

    if($response->getStatusCode() == 200){
        
        $api->updateToken($response);
        redirect($CFG->wwwroot . '/admin/settings.php?section=modsettingdrillster');
        
    } else {
        echo $response->getErrorMessage();
    }
}