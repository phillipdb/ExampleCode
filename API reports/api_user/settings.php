<?php
/**
 * API User Report
 *
 * Settings page
 *
 * @package    report
 * @subpackage api_user
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_api_user',
    get_string('pluginname', 'report_api_user'),
    "$CFG->wwwroot/report/api_user/index.php", 'report/api_user:canview'
    ));

$settings = new admin_settingpage('report_api_user', get_string('settingspagetitle', 'report_api_user'));

$choicesq = $DB->get_records_select_menu('user_info_field', null, null, 'shortname ASC', 'id, shortname');
$choices = array();
foreach ($choicesq as $k => $v) {
    $choices[$v] = $v;
}
$settings->add(new admin_setting_configselect('report_api_user/report_api_user_state',
                                                get_string('custom_settingsstatetitle', 'report_api_user'),
                                                get_string('custom_settingsstatedescription', 'report_api_user'),
                                                get_string('custom_state', 'report_api_user'),
                                                $choices));