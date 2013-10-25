<?php
/**
 * CGHS FEED BACK REPORT
 *
 * Index Page.
 *
 * @package    Report
 * @subpackage feedback
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/index_form.php');
require_once(dirname(__FILE__).'/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/moodle/report/feedback/index.php');
$PAGE->set_title(get_string('reporttitle', 'report_feedback'));
$PAGE->set_heading(get_string('reporttitle', 'report_feedback'));
$PAGE->set_pagelayout('report');

require_login();
require_capability('report/feedback:canview', get_system_context());

$mform = new feedbackform();

if ($data = $mform->get_data()) {
	if (!$data->ccselect) { // If no ccselect comes through then they are a student.
		$staff = 'logged'; // User submitting.
		$datefrom = " ";
		$dateto = " ";
		$centre = " ";
	} else {
		$datefrom = $data->datefrom;
		$dateto = $data->dateto;
		$centre = $data->ccselect;
		$staff = '0'; // Manager submitting.
	}

	if ($data->onlyuser) { // If a manager only wants to see their own records.
		$onlyuser = $data->onlyuser;
	} else {
		$onlyuser = 0;
	}

	if ($data->displaybutton) {
		echo $OUTPUT->header();
		echo $mform->display();
        echo feedback_completiontable($staff, $data->courseselect, $centre, $datefrom, $dateto, $onlyuser);
        echo $OUTPUT->footer();
	} else if ($data->generatebutton) {
		feedback_export_grades($staff, $data->courseselect, $centre, $datefrom, $dateto, $onlyuser);
		die;
	} 
} else {
	echo $OUTPUT->header();
	echo $mform->display();
	echo $OUTPUT->footer();
}
