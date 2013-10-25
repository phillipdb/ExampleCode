<?php
/**
 * Admin settings for CGHS feedback Report
 *
 * @package    Report
 * @subpackage feedback
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
                  'report_feedback',
                   get_string('pluginname', 'report_feedback'),
                    "$CFG->wwwroot/report/feedback/index.php",'report/feedback:canview' 
                    ));

$settings = new admin_settingpage('report_feedback', get_string('settingspagetitle', 'report_feedback'));

$choicesq = $DB->get_records_select_menu('user_info_field', null, null, 'shortname ASC', 'id, shortname');
$choices = array();
foreach ($choicesq as $k => $v) {
    $choices[$v] = $v;
}
$settings->add(new admin_setting_configselect('report_feedback/report_feedback_primary',
											get_string('settingsname_primary', 'report_feedback'),
                                            get_string('settingsdescription_primary', 'report_feedback'),
                                            get_string('costcentrefieldname', 'report_feedback'),
                                            $choices
                                           ));

$settings->add(new admin_setting_configselect('report_feedback/report_feedback_secondary',
											get_string('settingsname_secondary', 'report_feedback'),
                                            get_string('settingsdescription_secondary', 'report_feedback'),
                                            get_string('secondarycostcentrefieldname', 'report_feedback'),
                                            $choices
                                           ));