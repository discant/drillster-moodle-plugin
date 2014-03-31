<?php

/**
 * File: user.class
 * Encoding: UTF-8
 * @package: Moodle Drillster
 *
 * @Version: 1.0.0
 * @Since 27-mrt-2012
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterUser extends DrillsterApi
{

    function __construct()
    {
        parent::__construct(MOD_DRILLSTER_API_URL . 'user.json', true);
    }

    /**
     * get user details
     * @param string $code sample 96iUsV2aR-iP84BRT5edCg
     * @return array or false
     */
    public
            function get($drillsterId = '')
    {
        if (empty($drillsterId))
        {
            return $this->doRequest(array(), 'GET');
        }
        else
        {
            $this->setStrUrl(MOD_DRILLSTER_API_URL . 'user/' . $drillsterId . '/.json');
            return $this->doRequest(array(), 'GET');
        }
    }

    /**
     * Delete a user
     * @param string $drillsterId  sample 96iUsV2aR-iP84BRT5edCg
     */
    public
            function delete($drillsterId = '')
    {
        //we can delete????
    }

    /**
     * Create a user
     * @global object $CFG
     * @global moodle_database $DB
     * @param object $moodleUser
     * @return array or false
     * @throws Exception on fatal error
     */
    public
            function createUser($moodleUser = '')
    {
        global $CFG, $DB;

        if (!empty($moodleUser) && is_object($moodleUser))
        {
            //validate if it's valid moodle object
            if (isset($moodleUser->username))
            {
                if ($moodleUser->deleted == 0)
                {
                    //TODO add avatar
                    $objData = new stdClass();
                    //TODO maybe add this to get string but this will fail in CRON?
                    $objData->biography = 'I was created by Moodle [' . $CFG->wwwroot . ']';
                    $objData->emailAddress = $moodleUser->email;
                    $objData->realName = fullname($moodleUser);
                    $objData->language = $moodleUser->lang;

                    $return = $this->getUserPicture($moodleUser);

                    if ($return)
                    {
                        $objData->avatar = $return;
                    }

                    $array = array();
                    $array['request']['user'] = $objData;

                    $json = array('json' => json_encode($array));

                    $response = $this->doRequest($json, 'PUT');

                    if (!empty($response->response->user))
                    {
                        //check if user not exists already known
                        $result = $DB->get_record('drillster_user', array('moodle_user_id' => $moodleUser->id));

                        if (!$result)
                        {
                            //ADD User to local database
                            $object = new stdClass();
                            $object->drillster_id = $response->response->user->id;
                            $object->username = $response->response->user->realName;
                            $object->moodle_user_id = $moodleUser->id;
                            $object->email = $moodleUser->email; //we add the email so we know the correct email account where drillster is connected to

                            $DB->insert_record('drillster_user', $object);
                        }
                    }
                    return $response;
                }
            }
            else
            {
                throw new Exception('Wrong userdata');
            }
        }
        else
        {
            throw new Exception('Error: user data empty');
        }
    }

    /**
     * get the avatar form the user
     * @param object $moodleUser
     * @return object or false
     */
    private
            function getUserPicture($moodleUser)
    {
        $context = context_user::instance($moodleUser->id, IGNORE_MISSING);
        $fs = get_file_storage();

        $hasuploadedfilePng = $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f1/.png');
        $hasuploadedfileJpg = $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f1/.jpg');

        if ($hasuploadedfilePng || $hasuploadedfileJpg)
        {
            if ($moodleUser->picture == 1)
            {
                // Set the image URL to the URL for the uploaded file.
                $imageurl = moodle_url::make_pluginfile_url($context->id, 'user', 'icon', NULL, '/', 'f1');
                $image = $imageurl->out(false);

                $image = file_get_contents($image);

                $object = new stdClass();
                $object->attachment->content = base64_encode($image); //need some decoding
                $object->attachment->mime = ($hasuploadedfilePng) ? 'image/png' : 'image/jpeg';

                return $object;
            }
        }

        return false;
    }

}