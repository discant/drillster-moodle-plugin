<?php

/**
 * File: mod_form.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once dirname(__FILE__) . '/class/settings.php';

class mod_drillster_mod_form extends moodleform_mod
{

    function definition()
    {
        global $CFG, $USER;
        $mform = & $this->_form;
        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
        $time = time();
        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        $mform->addElement('hidden', 'time', $time);
        $mform->addElement('hidden', 'usercontextid', $usercontext->id);
        if (!empty($CFG->formatstringstriptags))
        {
            $mform->setType('name', PARAM_TEXT);
        }
        else
        {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $drillster = new Drillster();
        $arrList = $drillster->getMyRepertoire('dropdown');

        $select = &$mform->addElement('select', 'drill_id', get_string('form:drills', MOD_DRILLSTER), $arrList);
        $select->setMultiple(false);

        $mform->addRule('name', null, 'required', null, 'client');
        $this->add_intro_editor();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

}
