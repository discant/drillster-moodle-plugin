<?php

function xmldb_drillster_upgrade($oldversion) {
        
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2014111401) {

        // xmldb_field($name, $type=null, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null) {

        // Changes to table: drillster
        $table = new xmldb_table('drillster');
        
        $field = new xmldb_field('view', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, false,'drill_id');
        if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
        
        $field = new xmldb_field('query', XMLDB_TYPE_CHAR, '100', null, false, null, '', 'view');
        if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
        
        // Changes to table: drillster_user
        $table = new xmldb_table('drillster_user');
        
        $field = new xmldb_field('moodle_user_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'id');
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'userid');
        
        $field = new xmldb_field('drillster_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, false, 'userid');
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'drillster_external_userid');
        
        // Changes to table drillster_group
        $table = new xmldb_table('drillster_group');
        
        $field = new xmldb_field('module_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'name');
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'moduleid');

        $field = new xmldb_field('code', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, false, 'moduleid');
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'drillster_external_groupid');
        
        // Changes to table: drillster_link > drillster_user_group
        $table = new xmldb_table('drillster_link');
        if($dbman->table_exists($table)) $dbman->rename_table($table, 'drillster_user_group');
        
        $table = new xmldb_table('drillster_user_group');
        
        $field = new xmldb_field('drillster_user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'drillster_userid');
        
        $field = new xmldb_field('module_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'drillster_userid');
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'moduleid');
        
        $field = new xmldb_field('drillster_group_id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'moduleid');
        if($dbman->field_exists($table, $field)) $dbman->rename_field($table, $field, 'drillster_groupid');

        upgrade_mod_savepoint(true, 2014111401, 'drillster');
    }

    return true;
}
