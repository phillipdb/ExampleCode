<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Index Page
 *
 * @package    report
 * @subpackage san_deptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/index_form.php');
require_once($CFG->dirroot.'/report/san_deptcomp/lib.php');

$context = get_system_context();
$PAGE->set_context($context);
$PAGE->set_url('/moodle/report/san_deptcomp/index.php');
$PAGE->set_title(get_string('pagetitle', 'report_san_deptcomp'));
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pageheading', 'report_san_deptcomp'));

require_login();
require_capability('report/san_deptcomp:canview', $context);

$mform = new departmentcompletionform();

if ($mform->is_cancelled()) {

    redirect($CFG->wwwroot.'/');// Redirect to main page.

} else if ($data = $mform->get_data()) {

    if ($data->resetbutton) {

        redirect($CFG->wwwroot.'/report/san_deptcomp/index.php');// Redirect to main page.

    } else if ($data->exportbutton) { // If form generate button pressed.

        // Call function.
        san_deptcomp_export_grades($data->dateselectort, $data->dateselectorf, $data->idnumber, $data->surname, $data->jobtypeselect, $data->departmentselect, $data->internalselect, $data->coursesselect);

    }  else if ($data->submitbutton) {

        echo $OUTPUT->header();
        $mform->display();
        echo san_deptcomp_previewtable($data->dateselectorf, $data->dateselectort, $data->idnumber, $data->surname, $data->jobtypeselect, $data->departmentselect, $data->internalselect, $data->coursesselect);
        echo $OUTPUT->footer();

    }

} else {

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

}