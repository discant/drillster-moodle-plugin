<?php

/**
 * File: repertoire.class.php
 * Encoding: UTF-8
 * @package: Moodle Drillster
 *
 * @Version: 1.0.0
 * @Since 27-mrt-2012
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterRepertoire extends DrillsterApi
{

    function __construct()
    {
        parent::__construct(MOD_DRILLSTER_API_URL . 'repertoire.json', true);
    }

    /**
     * Retrieves the repertoire of the given user
     * @param string $userName optional
     * @return object or false
     */
    public
            function get($userName = '')
    {
        if (!empty($userName))
        {
            //get the default active user ID
            $this->setStrUrl(MOD_DRILLSTER_API_URL . 'repertoire/' . $userName . '.json');
        }
        return $this->doRequest(array(), 'GET');
    }

}