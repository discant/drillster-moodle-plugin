<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
 
class mod_drillster_mod_form extends moodleform_mod {

    function definition() {
        
        global $CFG, $USER;
        
        $mform = $this->_form;
        
        MoodleQuickForm::registerElementType('drillselector', "$CFG->dirroot/mod/drillster/quickform_drillselector.php",
                'MoodleQuickForm_drillselector');
       
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)){
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('drillselector', 'drillselector', get_string('drill', 'mod_drillster'));
        $mform->setType('drillselector', PARAM_TEXT);
        $mform->addRule('drillselector', null, 'required', null, 'server');

        $mform->addElement('hidden', 'time', $time);
        $mform->addElement('hidden', 'usercontextid', $usercontext->id);
        
        $this->add_intro_editor();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function set_data($data){
        
        foreach($this->_form->_elements as $element) if($element instanceof MoodleQuickForm_drillselector) $element->setValue(
            array(
                'drillid' => $data->drill_id,
                'view' => $data->view,
                'query' => $data->query
            ));

        parent::set_data($data);   
    }
    
    function validation($data, $files) {
        
        $errors = array();
        if(empty($data['drillselector']['drillid']) || $data['drillselector']['drillid'] == 'false') $errors['drillselector'] = get_string('drill_empty', 'drillster');
        return $errors;
    }
}