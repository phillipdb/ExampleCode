<?php
/**
 * API Completion Report
 *
 * Access page.
 *
 * @package    report
 * @subpackage api_completion
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'report/api_completion:canview' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                )
            )
        );