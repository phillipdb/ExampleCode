<?php
/**
 * API Payment Report
 *
 * Settings page
 *
 * @package    report
 * @subpackage api_payment
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_api_payment',
    get_string('pluginname', 'report_api_payment'),
    "$CFG->wwwroot/report/api_payment/index.php", 'report/api_payment:canview'
    ));