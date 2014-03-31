<?php

/**
 * File: group.class.php
 * Encoding: UTF-8
 * @package: Moodle Drillster
 *
 * @Version: 1.0.0
 * @Since 27-mrt-2012
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterGroupMembers extends DrillsterApi
{

    function __construct()
    {
        parent::__construct(MOD_DRILLSTER_API_URL . 'group.json', true);
    }

    /**
     * get details of a group
     * @param string $groupcode
     */
    public function get($groupcode = '')
    {
        if (strlen($groupcode) == 0)
        {
            return $this->doRequest(array(), 'GET');
        }
        else
        {
            $this->setStrUrl(MOD_DRILLSTER_API_URL . 'group/' . $groupcode . '.json');
            return $this->doRequest(array(), 'GET');
        }
    }

    public function update()
    {
        //POST request
        //TODO not supported for now
        throw new Exception('This function is a TODO');
    }

    /**
     * add a group to drillster. This also add the group to the moodle DB
     * @param array $array the data to create the drillster group
     * @param integer $cmId
     * @return mixed
     * @throws Exception
     */
    public function add(array $array = array(), $cmId = '')
    {
        // $array exists of:
        // - array drills (drillcodes)
        // - array members (user ids)
        if (!is_numeric($cmId))
        {
            throw new Exception('Please set a valid Cm id');
        }


        if (!empty($array))
        {
            //PUT request
            $objData = new stdClass();
            $objData->description = !empty($array['description']) ? $array['description'] : ''; // Already set at drillster.class.php
            $objData->name = !empty($array['name']) ? $array['name'] : ''; // Already set at drillster.class.php

            if (!empty($array['drills']) && is_array($array['drills']))
            {
                // Adds the last drill code to the object, for some reason by looping over them
                $drillList = array();
                foreach ($array['drills'] as $drillCode)
                {
                    if (!empty($drillCode))
                    {
                        $drillList['code'] = $drillCode;
                    }
                    else
                    {
                        throw new Exception('This not a valid drillcode');
                    }
                }
                $objData->drills->drill = $drillList;
            }
            else
            {
                throw new Exception('Please add drills to this group');
            }

            //adding drillster members // we do a check if we have already add them to drillster else we do so
            if (!empty($array['members']) && is_array($array['members']))
            {
                // Get drillster user ID of the logged in user
                $ids = $this->getUserIds($array);

                if (!empty($ids))
                {
                    $objData->members->user = $ids;
                }
            }

            $aRequest = array();
            $aRequest['request']['group'] = $objData;

            $json = array('json' => json_encode($aRequest));

            $response = $this->doRequest($json, 'PUT');

            //if this ok ,we must add the link to use and the group
            if (!empty($response->response->group->code))
            {
                //we add the group to moodle Database
                $groupID = $this->addMoodleGroup($response->response->group->code, $array, $cmId);
                if ($groupID)
                {
                    //link the users
                    $this->addUserGroupLink($groupID, $ids, $cmId);
                }
                else
                {
                    throw new Exception('Failed to add the group to moodle');
                }
                //we add the users
            }
            return $response;
        }
        else
        {
            throw new Exception('There is no data added');
        }
    }

    private function addMoodleGroup($code, array $array = array(), $cmId = '')
    {
        global $DB;

        //we asssum that all data provide is correct and is filled
        if (empty($code))
        {
            throw new Exception('Please provide a valid code');
        }

        if (!empty($array) && is_numeric($cmId))
        {
            $dataobject = new stdClass();
            $dataobject->name = $array['name'];
            $dataobject->module_id = $cmId;
            $dataobject->code = $code;
            $dataobject->description = $array['description'];

            return $DB->insert_record('drillster_group', $dataobject);
        }

        //default response
        return false;
    }

    /**
     * Get drill user ids / created missing users in drillster
     * @global moodle_database $DB
     * @param array $array
     * @return array|boolean
     * @throws Exception
     */
    private function getUserIds(array $array = array())
    {
        global $DB;
        $aUserIds = array();
        $aNoDrillUsers = array();
        foreach ($array['members'] as $moodleUserId) // Always only 1 member...
        {
            //check
            if (!is_numeric($moodleUserId)) // This CAN'T be non-numeric...
            {
                throw new Exception('Please provide us a valid moodle user ID');
            }

            // Lets check if we've got a drillster user with the currently logged in userID (which is stored at $moodleUserId)
            $result = $DB->get_record('drillster_user', array('moodle_user_id' => $moodleUserId));

            // Create user if none exists
            if (empty($result))
            {
                //we get this later
                $aNoDrillUsers[] = $moodleUserId;
            }
            else
            {
                // Otherwise we'll add the drillster user id to the result
                array_push($aUserIds, array('id' => $result->drillster_id));
            }
        }
        //creating drill users and add them to username list
        if (!empty($aNoDrillUsers)) // Same .. only 1 member
        {
            try
            {
                $rUserDrill = new DrillsterUser();

                foreach ($aNoDrillUsers as $moodleUserId)
                {
                    $user = $DB->get_record('user', array('id' => $moodleUserId)); //get moodle user
                    if (!empty($user))
                    {
                        //we can create
                        $response = $rUserDrill->createUser($user);
                        if (!empty($response->response->user))
                        {
                            array_push($aUserIds, array('id' => $response->response->user->id));
                        }
                    }
                }
            }
            catch (Exception $ex)
            {
                throw $ex;
            }
        }
        if (!empty($aUserIds))
        {
            return $aUserIds;
        }
        return false;
    }

    /**
     * Adding a drill user to a group
     * @param stdClass $drillGroup
     * @param stdClass $USER
     * @param integer $cmId
     */
    public function addUserToGroup(stdClass $drillGroup, stdClass $drillUser, $cmId = '')
    {
        global $DB;

        if (empty($drillGroup) || empty($drillUser))
        {
            throw new Exception('Please add valid drillUser or drillGroup object');
        }

        $this->setStrUrl(MOD_DRILLSTER_API_URL . 'group/' . $drillGroup->code . '/members/' . $drillUser->drillster_id . '.json');
        $aRequest = array();

        $json = array('json' => json_encode($aRequest));

        $response = $this->doRequest($json, 'PUT');

        if (!empty($response->response->group->code))
        {
            //echo 'We link you to a group';
            $this->addUserGroupLink($drillGroup->id, array('drillster_user_id' => $drillUser->drillster_id), $cmId);
        }
        //add link
        return $response;
    }

    /**
     * Delete a drill user from a group
     * @param stdClass $drillGroup
     * @param stdClass $USER
     * @param integer $cmId
     */
    public function deleteUserFromGroup(stdClass $drillGroup, stdClass $drillUser, $cmId = '')
    {
        global $DB;

        if (empty($drillGroup) || empty($drillUser))
        {
            throw new Exception('Please add valid drillUser or drillGroup object');
        }
        $this->setStrUrl(MOD_DRILLSTER_API_URL . 'group/' . $drillGroup->code . '/members/' . $drillUser->drillster_id . '.json');
        $aRequest = array();

        $json = array('json' => json_encode($aRequest));

        $response = $this->doRequest($json, 'DELETE');

        if (!empty($response->response->group->code))
        {
            //echo 'We link you to a group';
            $this->delUserGroupLink($drillGroup->id, array('drillster_user_id' => $drillUser->drillster_id), $cmId);
        }
        //add link
        return $response;
    }

    /**
     * link a user to a group make sure its not already connected
     * @global moodle_database $DB
     * @param integer $groupID
     * @param array $ids
     * @param integer $cmId
     */
    private function addUserGroupLink($groupID = '', array $ids = array(), $cmId = '')
    {
        global $DB;

        if (!empty($ids))
        {
            //resolves this
            foreach ($ids as $drillUserId)
            {
                $user = $DB->get_record('drillster_user', array('drillster_id' => $drillUserId));
                if (!empty($user))
                {
                    $dataobject = new stdClass();
                    $dataobject->drillster_user_id = $user->id;
                    $dataobject->module_id = $cmId;
                    $dataobject->drillster_group_id = $groupID;
                    $DB->insert_record('drillster_link', $dataobject);
                }
                else
                {
                    //sorry we cant find this user
                    throw new Exception('Failed to find the user in [drillster_user][' . $drillUserId . '] strange.. this must already be here');
                }
            }
        }
    }

    /**
     * link a user to a group make sure its not already connected
     * @global moodle_database $DB
     * @param integer $groupID
     * @param array $ids
     * @param integer $cmId
     */
    private function delUserGroupLink($groupID = '', array $ids = array(), $cmId = '')
    {
        global $DB;

        if (!empty($ids))
        {
            //resolves this
            foreach ($ids as $drillUserId)
            {
                $user = $DB->get_record('drillster_user', array('drillster_id' => $drillUserId));
                if (!empty($user))
                {
                    $dataobject = new stdClass();
                    $dataobject->drillster_user_id = $user->id;
                    $dataobject->module_id = $cmId;
                    $dataobject->drillster_group_id = $groupID;
                    $DB->delete_records('drillster_link', $dataobject);
                }
                else
                {
                    //sorry we cant find this user
                    throw new Exception('Failed to find the user in [drillster_user][' . $drillUserId . '] strange.. this must already be here');
                }
            }
        }
    }

}