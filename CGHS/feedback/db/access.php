<?php
/**
 * CGHS FEED BACK REPORT
 *
 * Access page.
 *
 * @package    Report
 * @subpackage cghs_feedback
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'report/feedback:managerview' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                )
            ),
        'report/feedback:canview' => array(
            'riskbitmask' => RISK_PERSONAL | RISK_CONFIG,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
            	'user' => CAP_ALLOW
            )
            )
        );