<?php

defined('MOODLE_INTERNAL') || die();

function drillster_add_instance($instance, $mform){
        
    global $CFG, $DB;

    $data = new stdClass();
    $data->course = $instance->course;
    $data->name = $instance->name;
    $data->intro = $instance->intro;
    
    $data->drill_id = $instance->drillselector['drillid'];
    $data->view     = $instance->drillselector['view'];
    $data->query    = $instance->drillselector['query'];
    
    $data->introformat = $instance->introformat;
    $data->timemodified = time();

    $id = $DB->insert_record('drillster', $data);

    return $id;
}

function drillster_update_instance($instance, $mform){

    global $CFG, $DB;
    
    $data = new stdClass();
    $data->course = $instance->course;
    $data->id = $instance->instance;
    $data->name = $instance->name;
    $data->intro = $instance->intro;
    $data->introformat = $instance->introformat;
    $data->timemodified = time();
    
    $data->drill_id = $instance->drillselector['drillid'];
    $data->view     = $instance->drillselector['view'];
    $data->query    = $instance->drillselector['query'];

    $DB->update_record('drillster', $data);
    return true;
}

function drillster_delete_instance($id){
    
    global $DB;

    if (!$resource = $DB->get_record('drillster', array('id' => $id))){
        return false;
    }

    $DB->delete_records('drillster', array('id' => $resource->id));

    return true;
}

function drillster_get_account($USER){
    
    global $DB;
    return $DB->get_record('drillster_user', array('userid' => $USER->id));
}

function drillster_create_account($USER){

    global $DB;    
    if(!$account = $DB->get_record('drillster_user', array('userid' => $USER->id))){

        $api = mod_drillster_api::getInstance();

        $response = $api->post('users', array(
            'realName' => fullname($USER),
            'emailAddress' => $USER->email
        ));

        if($data = $response->getData()){
                
            $account = new stdClass();
            $account->userid                         = $USER->id;
            $account->drillster_external_userid      = $data->id;
            $account->username                       = fullname($USER);
            $account->email                          = $USER->email;
            
            $account->id                             = $DB->insert_record('drillster_user', $account); 
        
        } else {
            
            $data = $response->getErrorData();
            
            if($response->getStatusCode() == 400 && $data->id == 'account_exists'){
                
                // het account bestaat al, maar ontbreekt nog in onze database, dit kunnen we gelukkig rechtzetten.                
                $account = new stdClass();
                $account->userid                         = $USER->id;
                $account->drillster_external_userid      = $data->userId;
                $account->username                       = fullname($USER);
                $account->email                          = $USER->email;
                
                $account->id                             = $DB->insert_record('drillster_user', $account); 
                
            } else {
                print_error('create_account: ' . $response->getErrorMessage());    
            }
        }
    }
    
    return $account;
}

function drillster_get_access($account){
    
    $api = mod_drillster_api::getInstance();
    $response = $api->get('access/'.$account->drillster_external_userid);     

    if($data = $response->getData()) {
        return $data->token;
    } else {
        print_error('get_access: ' .$response->getErrorMessage());
    }
}

function drillster_get_group($name, $module){
    
    global $DB, $USER;
    
    return $DB->get_record('drillster_group', array(
        'name' => $name,
        'moduleid' => $module->id));
}

function drillster_create_group($name, $module){
    
    global $DB;
    
    $api = mod_drillster_api::getInstance();
    $response = $api->post('groups', array(
        'name' => $name,
        'description' => get_string('drillster::group_desc', 'drillster'),
        'autoAddMembers' => true
    ));
    
    if($data = $response->getData()){
        
        $group = new stdClass();
        $group->name                        = $data->name;
        $group->description                 = $data->description;
        $group->drillster_external_groupid  = $data->id;
        $group->moduleid                    = $module->id;
        
        $group->id                          = $DB->insert_record('drillster_group', $group); 
        
    } else {
        print_error('create_group: ' .$response->getErrorMessage());
    }
    
    return $group;
}

function drillster_get_groupname($course, $module, $USER){
    
    if(($group = drillster_get_usergroup($course, $USER)) && $module->groupmode != 0){
        return get_string('drillster::group_prefix', 'drillster').$course->id.'-'.$module->id.'-'.$group->id;
    } else {
        return get_string('drillster::group_prefix', 'drillster').$module->id;
    }
}

function drillster_get_usergroup($course, $USER){
    global $USER, $CFG;
    if($groups = groups_get_all_groups($course->id)) foreach($groups as $group){
        require_once $CFG->dirroot."/group/lib.php";            
        if($roles = groups_get_members_by_role($group->id, $course->id, 'u.id,u.firstname,u.lastname')){
            foreach($roles as $role){
                if(is_array($role->users)) foreach($role->users as $user) if($USER->id == $user->id) return $group;
            }
        }
    }
    return false;
}

function drillster_add_user_to_group($user, $group, $module){

    global $USER, $DB;
    $api = mod_drillster_api::getInstance();
        
    $link = $DB->get_record('drillster_user_group', array(
        'drillster_userid' => $user->id,
        'drillster_groupid' => $group->id
    ));
    
    if(!$link){
            
        $response = $api->put('group/'.$group->drillster_external_groupid.'/members/'.$user->drillster_external_userid, array(
            'setup' => true,
            'realName'  => fullname($USER)
        ));
        
        if($data = $response->getData()){
    
            $DB->insert_record('drillster_user_group' , array(
                'moduleid' => $module->id,
                'drillster_userid' => $user->id,
                'drillster_groupid' => $group->id
           ));
           
        } else {
            
            $data = $response->getErrorData();

            if($response->getStatusCode() == 400 && in_array($data->id, array('already_invited','already_member'))){
               
               // De gebruiker is al aan de groep verbonden, maar deze link ontbreekt nog in de database.
               $DB->insert_record('drillster_user_group' , array(
                    'moduleid' => $module->id,
                    'drillster_userid' => $user->id,
                    'drillster_groupid' => $group->id
               ));
               
            } else {
                
                print_error('add_user_to_group: ' .$response->getErrorMessage());                   
            }
        }
    }
}
         
function drillster_add_drill_to_group($drill, $group){
    $api = mod_drillster_api::getInstance();    
    $api->put('group/'.$group->drillster_external_groupid.'/drills/'.$drill->drill_id);    
}
    