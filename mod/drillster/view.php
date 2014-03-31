<?php

/**
 * File: view.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/class/settings.php';
require_once $CFG->dirroot . '/group/lib.php';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // newmodule instance ID - it should be named as the first character of the module

if ($id)
{
    $cm = get_coursemodule_from_id('drillster', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $drillsterModule = $DB->get_record('drillster', array('id' => $cm->instance), '*', MUST_EXIST);
}
elseif ($n)
{
    $drillsterModule = $DB->get_record('drillster', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $drillsterModule->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('newmodule', $drillsterModule->id, $course->id, false, MUST_EXIST);
}
else
{
    error('You must specify a course_module ID or an instance ID');
}
require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

has_capability('mod/drillster:view', $context);

$drillster = new Drillster();

//enrol me in drillster and add me to the correct group and add me to drillster if needed
$drillster->enrol($cm, $drillsterModule, $USER);

#ACCESCODE
$accesscode = $drillster->getAccessCode($USER);

#GET DRILLDATA
//echo '<pre>';print_r($drillsterModule);echo '</pre>';die();
$drilldata = $drillster->getDrill($drillsterModule);

if ($drilldata == false)
{
    print_error(get_string('error:wrong_drillcode', MOD_DRILLSTER));
}

add_to_log($course->id, 'drillster', 'view', "view.php?id={$cm->id}", $drillsterModule->name, $cm->id);

$PAGE->set_url('/mod/drillster/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($drillsterModule->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

$PAGE->requires->css(MOD_DRILLSTER_CSS);
$PAGE->requires->js(MOD_DRILLSTER_DEFAULT_JS);

// Output starts here
echo $OUTPUT->header();
echo $OUTPUT->heading($cm->name);
if ($drillsterModule->intro)
{ // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('drillster', $drillsterModule, $cm->id), 'generalbox mod_introbox ', 'drillintro');
}

if (!empty($accesscode))
{
    $widget = '<img id="drillAuth" src="' . MOD_DRILLSTER_AUTH_IMG_URL . $accesscode . '" />';
    $widget .= '<div id="drillHolder" rel="' . MOD_DRILLSTER_IFRAME_URL . $drilldata->code . '">
                    <div id="drillLoading"><img src="' . $CFG->wwwroot . '/mod/drillster/pix/loading.gif" /></div>
                </div>';

    echo $OUTPUT->box($widget, 'generalbox mod_introbox', 'drillwidgetbox');
}
echo $OUTPUT->footer();