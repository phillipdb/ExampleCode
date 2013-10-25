<?php
/**
 * Admin settings for ACAT Upcoming Courses Report
 *
 * @package    report
 * @subpackage nhp_courses
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 Pukunui Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
                  'report_nhp_courses',
                   get_string('pluginname', 'report_nhp_courses'),
                    "$CFG->wwwroot/report/nhp_courses/index.php", 'report/nhp_courses:nhp_view'
                    ));
$settings = null;