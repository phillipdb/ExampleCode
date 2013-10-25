<?php
/**
 * API Completion Report
 *
 * Settings page
 *
 * @package    report
 * @subpackage api_completion
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_api_completion',
    get_string('pluginname', 'report_api_completion'),
    "$CFG->wwwroot/report/api_completion/index.php", 'report/api_completion:canview'
    ));

$settings = new admin_settingpage('report_api_completion', get_string('settingspagetitle', 'report_api_completion'));

$settings->add(new admin_setting_configtext('report_api_completion/report_api_completion_grade',
                                                get_string('custom_settingsgradetitle', 'report_api_completion'),
                                                get_string('custom_settingsgradedescription', 'report_api_completion'),
                                                '100',
                                                PARAM_RAW, 10));