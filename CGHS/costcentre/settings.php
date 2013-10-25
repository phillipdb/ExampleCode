<?php
/**
 * CGHS Cost Centre Screen
 *
 * Settings Page
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$ADMIN->add('root', new admin_category('report_costcentre', get_string('rootfolder', 'report_costcentre')));

$ADMIN->add('report_costcentre', new admin_externalpage('costcentre_courses', get_string('courses_pluginname', 'report_costcentre'),
    $CFG->wwwroot."/report/costcentre/index.php?formid=courses",
    'report/cghs_courses:view'));

$ADMIN->add('report_costcentre', new admin_externalpage('costcentre_centremanager', get_string('centremanager_pluginname', 'report_costcentre'),
    $CFG->wwwroot."/report/costcentre/index.php?formid=centremanager", 
    'report/cghs_centremanager:view'));

$ADMIN->add('report_costcentre', new admin_externalpage('costcentre_costcentre', get_string('costcentre_pluginname', 'report_costcentre'),
    $CFG->wwwroot."/report/costcentre/index.php?formid=costcentre",
    'report/cghs_costcentre:view'));
