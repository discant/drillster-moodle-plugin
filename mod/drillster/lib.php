<?php

/**
 * File: lib.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 *
 * ------------------------------------
 * Add resource instance.
 * @param object $data
 * @param object $mform
 * @return int new resoruce instance id
 */
defined('MOODLE_INTERNAL') || die;

function drillster_add_instance($data, $mform)
{
    global $CFG, $DB;

    $data2 = new stdClass();
    $data2->course = $data->course;
    $data2->name = $data->name;
    $data2->intro = $data->intro;
    $data2->drill_id = $data->drill_id;
    $data2->introformat = $data->introformat;
    $data2->timemodified = time();

    $id = $DB->insert_record('drillster', $data2);

    return $id;
}

/**
 * Update resource instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function drillster_update_instance($data, $mform)
{
    global $CFG, $DB;

    $data2 = new stdClass();
    $data2->course = $data->course;
    $data2->id = $data->instance;
    $data2->name = $data->name;
    $data2->intro = $data->intro;
    $data2->introformat = $data->introformat;
    $data2->timemodified = time();
    $data2->drill_id = $data->drill_id;

    $DB->update_record('drillster', $data2);
    return true;
}

/**
 * Delete resource instance.
 * @param int $id
 * @return bool true
 */
function drillster_delete_instance($id)
{
    global $DB;

    if (!$resource = $DB->get_record('drillster', array('id' => $id)))
    {
        return false;
    }

    $DB->delete_records('drillster', array('id' => $resource->id));

    return true;
}

/**
 * Display only in the resourses
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function drillster_supports($feature)
{
    switch ($feature)
    {
        case FEATURE_MOD_ARCHETYPE: return MOD_ARCHETYPE_ASSIGNMENT;
        case FEATURE_GROUPS: return true;
        case FEATURE_GROUPINGS: return true;
        case FEATURE_GROUPMEMBERSONLY: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_GRADE_OUTCOMES: return true;
        case FEATURE_BACKUP_MOODLE2: return false; //THIS is handy :)

        default: return null;
    }
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function drillster_user_outline($course, $user, $mod, $newmodule)
{

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}