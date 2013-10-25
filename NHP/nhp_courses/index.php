<?php
/**
 * NHP
 *
 *
 * @package    report
 * @subpackage nhp_courses
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 Pukunui Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

require_once(dirname(__FILE__).'/index_form.php');
require_once(dirname(__FILE__).'/lib.php');


$PAGE->set_context(context_system::instance());
$PAGE->set_url('/moodle/report/nhp_courses/index.php');
$PAGE->set_title('NHP Completed Courses Report');
$PAGE->set_heading('NHP Completed Courses Report');
$PAGE->set_pagelayout('report');

$submit = optional_param('submitbutton', 0, PARAM_NOTAGS);

require_login();

$context = get_system_context();
$capability = 'report/nhp_courses:nhp_view';
if (!has_capability($capability, $context)) {
    print_error("Sorry you do not have permissions to view this page.");
}

$mform = new nhp_coursesform();
if ($fromform = $mform->get_data()) { // Form submitted.
	nhp_export_grades();
	//exit;
} else {
	echo $OUTPUT->header();
	echo $mform->display();
	echo $OUTPUT->footer();
}