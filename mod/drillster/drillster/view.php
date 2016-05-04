<?php

require_once('../../config.php');
require_once('lib.php');

/*
@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!

$CFG->debug = 32767;         // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = true;   // NOT FOR PRODUCTION SERVERS!
$CFG->debugusers = '2,15764';
*/

$id = required_param('id', PARAM_INT);
$api = mod_drillster_api::getInstance();

if (!$module = get_coursemodule_from_id('drillster', $id)) {
    print_error('Course Module ID was incorrect'); 
}

if (!$course = $DB->get_record('course', array('id'=> $module->course))) {
    print_error('course is misconfigured');  
}

if (!$drill = $DB->get_record('drillster', array('id'=> $module->instance))) {
    print_error('course module is incorrect'); 
}

require_course_login($course, true, $module);

// @TODO: toevoegen van een nieuwe gebruiker op een nieuw emailadres
if(!$account = drillster_get_account($USER)){
    $account = drillster_create_account($USER);
}

$groupname = drillster_get_groupname($course, $module, $USER);

if(!$group = drillster_get_group($groupname, $module)){    
    $group = drillster_create_group($groupname, $module);
}

drillster_add_user_to_group($account, $group, $module);
drillster_add_drill_to_group($drill, $group);

$token = drillster_get_access($account);

$drillster_drill = $api->get('drill/'.$drill->drill_id);        

$PAGE->requires->css('/mod/drillster/style/drill.css');

$context = context_module::instance($module->id);

$PAGE->set_url('/mod/drillster/view.php', array('id' => $module->id));
$PAGE->set_title(format_string($drill->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();
echo $OUTPUT->heading($module->name);

if($drill->intro){
    echo $OUTPUT->box(format_module_intro('drillster', $drill, $module->id), 'generalbox mod_introbox ', 'drillintro');
}

$iframe = html_writer::tag('iframe', '', array(
    'id' => 'widget',
    'src' => 'https://www.drillster.com/widget/drill/'.$drill->drill_id.'?oauth_token='.$token,
    'scrolling' => 'no',
    'frameborder' => '0',
));

echo $OUTPUT->box($iframe, 'generalbox mod_introbox', 'drillwidgetbox');

echo $OUTPUT->footer();