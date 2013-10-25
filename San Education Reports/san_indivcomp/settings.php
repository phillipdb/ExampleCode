<?php
/**
 * SAN Education Individual Completion Status Report
 *
 * Admin settings for program Completion Report
 *
 * @package    report
 * @subpackage san_indivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_san_indivcomp',
    get_string('pluginname', 'report_san_indivcomp'),
    "$CFG->wwwroot/report/san_indivcomp/index.php", 'report/san_indivcomp:canview'
    ));

    $settings = new admin_settingpage('report_san_indivcomp', get_string('settingspagetitle', 'report_san_indivcomp'));

    $choicesq = $DB->get_records_select_menu('user_info_field', null, null, 'shortname ASC', 'id, shortname');
    $choices = array();
    foreach ($choicesq as $k => $v) {
        $choices[$v] = $v;
    }
    $settings->add(new admin_setting_configselect('report_san_indivcomp/report_san_indivcomp_employment', get_string('settingsname_employment', 'report_san_indivcomp'),
                                                get_string('settingsdescription_employment', 'report_san_indivcomp'), get_string('customfield', 'report_san_indivcomp'), //default value
                                                 $choices));

