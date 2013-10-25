<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * String definitions
 *
 * @package    report
 * @subpackage san_commdeptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/index_form.php');
require_once($CFG->dirroot.'/report/san_commdeptcomp/lib.php');
$context = get_system_context();
$PAGE->set_context($context);
$PAGE->set_url('/moodle/report/san_commdeptcomp/index.php');
$PAGE->set_title(get_string('pagetitle', 'report_san_commdeptcomp'));
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pageheading', 'report_san_commdeptcomp'));

require_login();
require_capability('report/san_commdeptcomp:canview', $context);

$mform = new commdepartmentcompletionform();

if ($mform->is_cancelled()) {

    redirect($CFG->wwwroot.'/');// Redirect to main page.

} else if ($data = $mform->get_data()) {

    if ($data->resetbutton) {

        redirect($CFG->wwwroot.'/report/san_commdeptcomp/index.php');// Redirect to main page.

    } else if ($data->exportbutton == get_string('exportbutton', 'report_san_commdeptcomp')) { // If form export button pressed.

        // Call export function.
        san_commdeptcomp_export_grades($data->dateselectorf, $data->dateselectort, $data->idnumber, $data->surname, $data->jobtypeselect, $data->departmentselect, $data->internalselect, $data->coursesselect);

    } else if ($data->submitbutton) {

        echo $OUTPUT->header();
        $mform->display();
        echo san_commdeptcomp_previewtable($data->dateselectorf, $data->dateselectort, $data->idnumber, $data->surname, $data->jobtypeselect, $data->departmentselect, $data->internalselect, $data->coursesselect);
        echo $OUTPUT->footer();

    }

} else {

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

}