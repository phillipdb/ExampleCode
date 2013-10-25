<?php
/**
 * SAN Education Individual Completion Status Report
 *
 * Admin settings for program Completion Report
 *
 * @package    report
 * @subpackage san_commindivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_san_commindivcomp',
    get_string('pluginname', 'report_san_commindivcomp'),
    "$CFG->wwwroot/report/san_commindivcomp/index.php", 'report/san_commindivcomp:canview'
    ));

    $settings = new admin_settingpage('report_san_commindivcomp', get_string('settingspagetitle', 'report_san_commindivcomp'));

    $choicesq = $DB->get_records_select_menu('user_info_field', null, null, 'shortname ASC', 'id, shortname');
    $choices = array();
    foreach ($choicesq as $k => $v) {
        $choices[$v] = $v;
    }
    $settings->add(new admin_setting_configselect('report_san_commindivcomp/report_san_commindivcomp_employment', get_string('settingsname_employment', 'report_san_commindivcomp'),
                                                get_string('settingsdescription_employment', 'report_san_commindivcomp'), get_string('employment', 'report_san_commindivcomp'), //default value
                                                 $choices));

    $settings->add(new admin_setting_configselect('report_san_commindivcomp/report_san_commindivcomp_location', get_string('settingsname_location', 'report_san_commindivcomp'),
                                                get_string('settingsdescription_location', 'report_san_commindivcomp'), get_string('location', 'report_san_commindivcomp'), //default value
                                                 $choices));

    $settings->add(new admin_setting_configselect('report_san_commindivcomp/report_san_commindivcomp_position', get_string('settingsname_position', 'report_san_commindivcomp'),
                                                get_string('settingsdescription_position', 'report_san_commindivcomp'), get_string('position', 'report_san_commindivcomp'), //default value
                                                 $choices));