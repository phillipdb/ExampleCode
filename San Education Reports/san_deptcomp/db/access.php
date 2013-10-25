<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Access
 *
 * @package    report
 * @subpackage san_deptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'report/san_deptcomp:canview' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'teacher' => CAP_ALLOW
                )
            )
        );