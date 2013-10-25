<?php
/**
 * SAN Education Individual Completion Status Report
 *
 * Access
 *
 * @package    report
 * @subpackage san_indivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'report/san_indivcomp:canview' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'user' => CAP_ALLOW
            )
            )
        );