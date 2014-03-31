<?php

/**
 * File: groups.class.php
 * Encoding: UTF-8
 * @package: Moodle Drillster
 *
 * @Version: 1.0.0
 * @Since 27-mrt-2012
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterGroups extends DrillsterApi
{

    function __construct()
    {
        parent::__construct(MOD_DRILLSTER_API_URL . 'groups.json', true);
    }

    /**
     * Retrieves the list of groups managed by the current user
     * @param string $userName optional
     * @return object or false
     */
    public
            function get($userName = '')
    {
        if (!empty($userName))
        {
            $this->setStrUrl(MOD_DRILLSTER_API_URL . 'groups/' . $userName . '.json');
        }

        return $this->doRequest(array(), 'GET');
    }

}