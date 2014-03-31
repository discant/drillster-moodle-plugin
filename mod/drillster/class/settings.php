<?php

/**
 * File: settings.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;
//INIT
require_once 'drillster.class.php';
require_once 'api.class.php';
require_once 'drillster/access.class.php';
require_once 'drillster/answer.class.php';
require_once 'drillster/chart.class.php';
require_once 'drillster/course.class.php';
require_once 'drillster/courses.class.php';
require_once 'drillster/drill.class.php';
require_once 'drillster/group.class.php';
require_once 'drillster/groups.class.php';
require_once 'drillster/question.class.php';
require_once 'drillster/repertoire.class.php';
require_once 'drillster/user.class.php';
require_once 'drillster/groupMembers.class.php';

//DEFINES
define('MOD_DRILLSTER', 'mod_drillster');
define('MOD_DRILLSTER_AUTH_URL', 'https://www.drillster.com/oauth/authorize');
define('MOD_DRILLSTER_AUTH_IMG_URL', 'http://www.drillster.com/authenticate.png?oauth_token=');
define('MOD_DRILLSTER_API_URL', 'https://www.drillster.com/api/');
define('MOD_DRILLSTER_TOKEN_URL', MOD_DRILLSTER_API_URL . 'token.json');
define('MOD_DRILLSTER_IFRAME_URL', 'http://www.drillster.com/widget/drill/');
define('MOD_DRILLSTER_CSS', '/mod/drillster/styles.css');
define('MOD_DRILLSTER_ROOT', $CFG->dirroot . '/mod/drillster/');
define('MOD_DRILLSTER_DEFAULT_JS', '/mod/drillster/js/custom.js');