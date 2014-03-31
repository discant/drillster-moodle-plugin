<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
/**
 * File: update_token.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__) . '/class/api.class.php';
require_once dirname(__FILE__) . '/class/drillster.class.php';
require_once dirname(__FILE__) . '/class/settings.php';

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$code = optional_param('code', '', PARAM_RAW);
$reset = optional_param('reset', 0, PARAM_INT);
$PAGE->set_url('/mod/drillster/update_token.php', array('code' => $code));
//check for code
if (!empty($code))
{
//do a validation test of the settings
    $drillster = new Drillster();
    $return = $drillster->getToken($code);

    if (!empty($return))
    {
        //RESET button
        redirect($CFG->wwwroot . '/admin/settings.php?section=modsettingdrillster');
    }
    else
    {
        echo 'ERROR - Invalid request or token not given';
    }
}
elseif ($reset == 1)
{
    set_config('drillster_token', '', 'drillster');
    redirect($CFG->wwwroot . '/admin/settings.php?section=modsettingdrillster');
}