<?php

/**
 * File: drill.class.php
 * Encoding: UTF-8
 * @package: Moodle Drillster
 *
 * @Version: 1.0.0
 * @Since 27-mrt-2012
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterDrill extends DrillsterApi
{

    function __construct()
    {
        parent::__construct(MOD_DRILLSTER_API_URL . 'drill.json', true);
    }

    /**
     * get details of a driil
     * @param string $drillcode
     */
    public
            function get($drillcode = '')
    {
        if (strlen($drillcode) == 0)
        {
            return $this->doRequest(array(), 'GET');
        }
        else
        {
            $this->setStrUrl(MOD_DRILLSTER_API_URL . 'drill/' . $drillcode . '.json');
            return $this->doRequest(array(), 'GET');
        }
    }

    public
            function update()
    {
        //POST request
        //TODO not supported for now
        throw new Exception('This function is a TODO');
    }

    public
            function add()
    {
        //PUT request
        //TODO not supported for now
        throw new Exception('This function is a TODO');
    }

    public
            function del()
    {
        //DELETE request
        //TODO not supported for now
        throw new Exception('This function is a TODO');
    }

}