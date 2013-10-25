<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Admin settings for department completion report
 *
 * @package    report
 * @subpackage san_commdeptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_san_commdeptcomp',
    get_string('pluginname', 'report_san_commdeptcomp'),
    "$CFG->wwwroot/report/san_commdeptcomp/index.php", 'report/san_commdeptcomp:canview'
    ));

    $settings = new admin_settingpage('report_san_commdeptcomp', get_string('settingspagetitle', 'report_san_commdeptcomp'));

    $choicesq = $DB->get_records_select_menu('user_info_field', null, null, 'shortname ASC', 'id, shortname');
    $choices = array();
    foreach ($choicesq as $k => $v) {
        $choices[$v] = $v;
    }

    $settings->add(new admin_setting_configselect('report_san_commdeptcomp/report_san_commdeptcomp_department', get_string('settingsname_department', 'report_san_commdeptcomp'),
                                                get_string('settingsdescription_department', 'report_san_commdeptcomp'), get_string('departmentcustom', 'report_san_commdeptcomp'), //default value
                                                 $choices));
    

    $settings->add(new admin_setting_configselect('report_san_commdeptcomp/report_san_commdeptcomp_employment', get_string('settingsname_employment', 'report_san_commdeptcomp'),
                                                get_string('settingsdescription_employment', 'report_san_commdeptcomp'), get_string('custom_employmenttype', 'report_san_commdeptcomp'), //default value
                                                 $choices));

    $settings->add(new admin_setting_configselect('report_san_commdeptcomp/report_san_commdeptcomp_internal', get_string('settingsname_internal', 'report_san_commdeptcomp'),
                                                get_string('settingsdescription_internal', 'report_san_commdeptcomp'), get_string('internalcustom', 'report_san_commdeptcomp'), //default value
                                                 $choices));

    $settings->add(new admin_setting_configselect('report_san_commdeptcomp/report_san_commdeptcomp_position', get_string('settingsname_position', 'report_san_commdeptcomp'),
                                                get_string('settingsdescription_position', 'report_san_commdeptcomp'), get_string('custom_positiontitle', 'report_san_commdeptcomp'), //default value
                                                 $choices));

