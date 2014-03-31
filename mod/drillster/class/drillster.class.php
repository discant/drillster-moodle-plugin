<?php

defined('MOODLE_INTERNAL') || die;

/**
 * File: drillster.class.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
class Drillster
{

    //debug mode
    private $isDebug = false;

    function __construct()
    {
        global $CFG;

        //check if we want to debug

        if (!empty($CFG->drillster_debug))
        {
            $this->isDebug = true;
        }
    }

    public function getToken($code = '')
    {
        global $CFG;

        if (($token = get_config('drillster', 'drillster_token')) !== '' && empty($code))
        {
            //TODO check if this token works

            return $token;
        }

        if (!empty($code))
        {
            try
            {
                $api = new DrillsterApi(MOD_DRILLSTER_TOKEN_URL);
                $arrParam = array();
                $arrParam['client_id'] = trim($CFG->drillster_client_id);
                $arrParam['client_secret'] = trim($CFG->drillster_client_secret);
                $arrParam['grant_type'] = 'authorization_code';
                $arrParam['scope'] = 'non-expiring';
                $arrParam['code'] = $code;

                $return = $api->doRequest($arrParam);

                if (!empty($return->access_token))
                {
                    set_config('drillster_token', $return->access_token, 'drillster');
                    return $return->access_token;
                }
            }
            catch (Exception $exc)
            {
                //throw a error
                $this->isError($exc);
            }
        }

        return false;
    }

    /**
     * getMyRepertoire
     * @return list
     */
    public function getMyRepertoire($type = 'all')
    {
        $repertoire = new DrillsterRepertoire();
        $response = $repertoire->get();

        if ($type == 'dropdown')
        {
            $array = array();
            if (!empty($response->response->repertoire->drill))
            {
                foreach ($response->response->repertoire->drill as $drill)
                {
                    if (isset($drill->code) && isset($drill->name))
                    {
                        $array[$drill->code] = $drill->name;
                    }
                }
                if (count($array) == 0)
                {
                    // There is only 1 drill available.
                    foreach ($response->response->repertoire as $drill)
                    {
                        if (isset($drill->code) && isset($drill->name))
                        {
                            $array[$drill->code] = $drill->name;
                        }
                    }
                }

                return $array;
            }
            else
            {
                $array[''] = get_string('error:no_repertoire', 'drillster');
                return $array;
            }
        }

        return false;
    }

    public function createGroup($cmId = '')
    {
        //courseid.cmid.moodlegroep
    }

    /**
     * create a user in Drillster if not exits
     * @param object $user
     * @return boolean true if succes
     */
    public function createUser($user)
    {
        global $COURSE;
        $class = new DrillsterUser();
        $response = $class->createUser($user); //this also created the user in the database

        if (!empty($response->response->error))
        {
            add_to_log($COURSE->id, 'drillster', 'error', "createUser", serialize($response->response->error));
            //failed
            return false;
        }
        else
        {
            //we have create the user in drillster
            return $response;
        }
    }

    private function isError($exception = '', $isFatal = 0)
    {
        if ($this->isDebug)
        {
            echo '<b>' . $exception->getMessage() . '</b><br/>';
            echo '<pre>';
            echo $exception->getTraceAsString();
            echo '</pre>';
        }
        else
        {
            //only kill if FATAL
            if (!empty($isFatal))
            {
                print_error($exception->getMessage());
            }
        }
    }

    /**
     * Enrol a user to a drill
     * created group if necessary
     *
     * @param object $cm module instance
     * @param object $drill the drillster record
     * @param moodle_user $user moodle object
     */
    public function enrol($cm, $drill, $user)
    {
        global $DB, $COURSE;

        if (is_object($user) && !empty($drill->id))
        {
            //check if we have ever create this user in drillster
            $dUser = $DB->get_record('drillster_user', array('moodle_user_id' => $user->id));

            if (!$dUser)
            {
                # creating the user
                $response = $this->createUser($user);
                $username = $response->response->user->realName;
                $dUser = $DB->get_record('drillster_user', array('moodle_user_id' => $user->id));

                if (empty($dUser))
                {
                    throw new Exception('Failed to created the user in the moodle drill');
                }
            }
            else
            {
                $username = $dUser->username;
            }

            if (empty($username))
            {
                throw new Exception('We are not able to get your correct username from moodle');
            }
            $rGroup = new DrillsterGroupMembers();
            $objGroup = $this->getMyGroup($COURSE, $user);

            //check if use groups is on also check if there are groups
            if ($cm->groupmode != 0 && !empty($objGroup))
            {
                $name = get_string('call:name_group', 'drillster') . $COURSE->id . '-' . $cm->id . '-' . $objGroup->id;
                if (!empty($objGroup))
                {
                    $drillsterGroup = $DB->get_record('drillster_group', array('module_id' => $cm->id, 'name' => $name));
                    if (empty($drillsterGroup))
                    {
                        //we must add a new group
                        $array = array();
                        $array['name'] = $name;
                        $array['description'] = get_string('call:general_desc_group', 'drillster');
                        //we must add use to this group
                        $array['members'][] = $user->id;
                        // we also must add the drillcode
                        $array['drills'][] = $drill->drill_id;

                        $response = $rGroup->add($array, $cm->id); //we created the group so we can access the drill
                        //check if there are no strange errors so we can continue
                        if (!empty($response->response->error->description))
                        {
                            print_error($response->response->error->description);
                        }
                    }
                    else
                    {
                        $linked = $DB->get_record('drillster_link', array('drillster_group_id' => $drillsterGroup->id, 'drillster_user_id' => $dUser->id));

                        if (empty($linked))
                        {
                            $rGroup->addUserToGroup($drillsterGroup, $dUser, $cm->id);
                        }
                        else
                        {
                            //YES you already in this group
                        }
                    }
                }
            }
            else
            {
                //first we need to check if we have created the default group for this
                $drillsterGroup = $DB->get_record('drillster_group', array('module_id' => $cm->id));

                if (!$drillsterGroup)
                {

                    //we must add a new default group
                    $array = array();
                    $array['name'] = get_string('call:general_name_group', 'drillster') . '-' . $cm->id;
                    $array['description'] = get_string('call:general_desc_group', 'drillster');

                    //we must add use to this group
                    $array['members'][] = $user->id;

                    // we also must add the drillcode
                    $array['drills'][] = $drill->drill_id;

                    $response = $rGroup->add($array, $cm->id); //we created the group so we can access the drill
                    //check if there are no strange errors so we can continue
                    if (!empty($response->response->error->description))
                    {
                        print_error($response->response->error->description);
                    }
                }
                else
                {
                    // $DB->delete_records('drillster_link', array('drillster_group_id'=>$drillsterGroup->id, 'drillster_user_id'=>$dUser->id));
                    // If we've got no group membership add it
                    $linked = $DB->get_record('drillster_link', array('drillster_group_id' => $drillsterGroup->id, 'drillster_user_id' => $dUser->id));

                    if (empty($linked))
                    {
                        $rGroup->addUserToGroup($drillsterGroup, $dUser, $cm->id);
                    }
                    else
                    {
                        //YES you already in this group
                    }
                }
            }
            return true;
        }

        return false;
    }

    /**
     * doAccessCall get token
     *
     * @global moodle_database $DB
     * @param moodle_user $user
     */
    public function getAccessCode($user)
    {
        global $DB;
        $dUser = $DB->get_record('drillster_user', array('moodle_user_id' => $user->id));

        if ($dUser)
        {
            $access = new DrillsterAccess();
            $response = $access->get($dUser->email); //we use the email known by drillster

            if (!empty($response->response->access->url))
            {
                //litle tweak to remove url data
                $array = explode('?oauth_token=', $response->response->access->url);
                return $array[1];
            }
        }
        else
        {
            throw new Exception('This user is not created by moodle in drillster');
        }
        return false;
    }

    /**
     * get my moodle group
     * important we return the first we found
     * @param stdClass $course
     * @param stdClass $user
     * @return stdClass|false
     */
    private function getMyGroup(stdClass $course, stdClass $user)
    {
        $groups = groups_get_all_groups($course->id);
        // $roles = array();
        if (!empty($groups))
        {
            foreach ($groups as $group)
            {
                if ($groupmemberroles = groups_get_members_by_role($group->id, $course->id, 'u.id,u.firstname,u.lastname'))
                {
                    foreach ($groupmemberroles as $roleid => $roledata)
                    {
                        //$shortroledata = new stdClass();
                        //$shortroledata->id = $group->id;
                        //$shortroledata->name = $roledata->name;
                        //$shortroledata->users = array();
                        foreach ($roledata->users as $member)
                        {
                            if ($user->id == $member->id)
                            {
                                return $group;
                            }
                            // $shortroledata->users[$member->id] = fullname($member);
                        }
                        // $roles[$group->id] = $shortroledata;
                    }
                }
            }
        }
        return false;
    }

    /**
     * getDrill details
     * @param object $drillModule
     * @return object or false
     */
    public function getDrill($drillModule = '')
    {
        if (!empty($drillModule->drill_id))
        {
            $drill = new DrillsterDrill();
            $response = $drill->get($drillModule->drill_id);

            if (!empty($response->response->drill))
            {
                return $response->response->drill;
            }
        }
        return false;
    }

}