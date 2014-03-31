<?php

/**
 * File: access.class.php
 * Encoding: UTF-8
 * @package: Moodle Drillster
 *
 * @Version: 1.0.0
 * @Since 27-mrt-2012
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterAccess extends DrillsterApi
{

    function __construct()
    {
        parent::__construct(MOD_DRILLSTER_API_URL . 'access.json', true);
    }

    public function get($email = '')
    {
        if (empty($email))
        {
            throw new Exception('This is a unknown username');
        }

        $objData = new stdClass();
        //TODO maybe add this to get string but this will fail in CRON?
        $objData->for = $email;
        //$objData->security = 'ssl';
        $array = array();
        $array['request']['access'] = $objData;

        $json = array('json' => json_encode($array));
        $result = $this->doRequest($json, 'POST');

        return $result;
    }

}