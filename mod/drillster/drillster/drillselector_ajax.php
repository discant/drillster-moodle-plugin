<?php

define('AJAX_SCRIPT', true);

require '../../config.php';
require_once $CFG->libdir.'/adminlib.php';
require_once $CFG->dirroot.'/mod/drillster/lib.php';

$PAGE->set_context(context_system::instance());

require_login();

if (isguestuser()) print_error('noguest');

require_sesskey();

$action  = required_param('action', PARAM_ALPHA);

$user_context = context_user::instance($USER->id);

echo $OUTPUT->header();

switch ($action) {
    case 'drill':

        $drillid = required_param('drillid', PARAM_TEXT);
        
        $api = mod_drillster_api::getInstance();
        $oResponse = $api->get('drill/'.$drillid);
        
        echo $oResponse->jsonEncode();
        die;                
        
    break;
    case 'drills':
        
        $view  = required_param('view', PARAM_ALPHA);
        $searchquery = required_param('searchquery', PARAM_TEXT);
        
        $params = array();
        if(!empty($searchquery)) $params['query'] = $searchquery;
        
        switch($view){
            case "myrepertoire":
                
                $api = mod_drillster_api::getInstance();
                $oResponse = $api->get('repertoire', $params);
                
                echo $oResponse->jsonEncode();
                die;
                
            break;
            case "drillstore":
                
                $query = required_param('searchquery', PARAM_TEXT);
                
                $api = mod_drillster_api::getInstance();
                $oResponse = $api->get('store', $params);

                echo $oResponse->jsonEncode();
                die;
                
            break;
        }
    break;
}