<?php
/**
 * Access 
 *
 * @package    report
 * @subpackage nhp_courses
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 Pukunui Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();



$capabilities = array(
        'report/nhp_courses:nhp_view' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                )
            )
        );