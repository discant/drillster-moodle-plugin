<?php

/**
 * File: settings.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die();
require_once dirname(__FILE__) . '/class/settings.php';

if ($ADMIN->fulltree)
{
    $token = get_config('drillster', 'drillster_token');
    if (empty($token))
    {
        $button = '<br><input type="button" onclick="window.location=\'' . MOD_DRILLSTER_AUTH_URL . '?client_id=' . $CFG->drillster_client_id . '&redirect_uri=' . $CFG->wwwroot . '/mod/drillster/update_token.php\'" value="' . get_string('link:allowdrillster', MOD_DRILLSTER) . '" name="allow" />';
        $settings->add(new admin_setting_heading('drillster_validation', '', $button));
        //get my profile detials
    }
    else
    {
        $button = '<br><input type="button" onclick="window.location=\'' . new moodle_url('/mod/drillster/update_token.php', array('reset' => 1)) . '\'" value="' . get_string('link:disconnect', MOD_DRILLSTER) . '" name="allow" />';
        $settings->add(new admin_setting_heading('drillster_validation', '', $button));
        $user = new DrillsterUser();
        $object = $user->get();

        if (isset($object->response->user))
        {
            //update userid from drillster so dont need to do a request for this
            $htmlReturn = '<b>' . $object->response->user->realName . '</b><br>';
            $htmlReturn .= $object->response->user->memberSince . '<br><br>';
            $htmlReturn .= $object->response->user->emailAddress . '<br>';
            $settings->add(new admin_setting_heading('drillster_user', get_string('setting:drillster_account', 'drillster'), $htmlReturn));
        }
    }

    $settings->add(new admin_setting_configtext('drillster_client_id', '', get_string("setting:client_id", MOD_DRILLSTER), ''));
    $settings->add(new admin_setting_configtext('drillster_client_secret', '', get_string("setting:client_secret", MOD_DRILLSTER), ''));
    $settings->add(new admin_setting_configcheckbox('drillster_debug', get_string('setting:debug', MOD_DRILLSTER), '', 0));
}