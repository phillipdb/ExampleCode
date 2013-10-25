<?php
/**
 * SAN Education Individual Completion Status Report
 *
 * Display a date selector and generate button
 * 
 * @package    report
 * @subpackage san_indivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/index_form.php');
require_once($CFG->dirroot.'/report/san_indivcomp/lib.php');

$context = get_system_context();
$PAGE->set_context($context);
$PAGE->set_url('/moodle/report/san_indivcomp/index.php');
$PAGE->set_title(get_string('pagetitle', 'report_san_indivcomp'));
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pageheading', 'report_san_indivcomp'));

require_login();
require_capability('report/san_indivcomp:canview', $context);

$mform = new individualcompletionform();

if ($mform->is_cancelled()) {

    redirect($CFG->wwwroot.'/');// Redirect to main page.

} else if ($data = $mform->get_data()) {

	if ($data->submitbutton == get_string('previewbutton', 'report_san_indivcomp')) { // If form generate button pressed.

    	echo $OUTPUT->header();
		$mform->display();
		echo san_indivcomp_programtable($data->dateselectorf, $data->dateselectort);
		echo $OUTPUT->footer();

	} else if ($data->exportbutton == get_string('exportbutton', 'report_san_indivcomp')) { // If form generate button pressed.

    	san_indivcomp_export_grades($data->dateselectort, $data->dateselectorf);

	}

} else {

	echo $OUTPUT->header();
	$mform->display();
	echo $OUTPUT->footer();

}