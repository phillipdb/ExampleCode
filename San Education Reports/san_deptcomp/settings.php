<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Admin settings for department completion report
 *
 * @package    report
 * @subpackage san_deptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_san_deptcomp',
    get_string('pluginname', 'report_san_deptcomp'),
    "$CFG->wwwroot/report/san_deptcomp/index.php", 'report/san_deptcomp:canview'
    ));

    $settings = new admin_settingpage('report_san_deptcomp', get_string('settingspagetitle', 'report_san_deptcomp'));

    $choicesq = $DB->get_records_select_menu('user_info_field', null, null, 'shortname ASC', 'id, shortname');
    $choices = array();
    foreach ($choicesq as $k => $v) {
        $choices[$v] = $v;
    }
    $settings->add(new admin_setting_configselect('report_san_deptcomp/report_san_deptcomp_jobtype', get_string('custom_settingsjobtypetitle', 'report_san_deptcomp'),
                                                get_string('custom_settingsjobtypedescription', 'report_san_deptcomp'), get_string('custom_jobtype', 'report_san_deptcomp'),
                                                 $choices));