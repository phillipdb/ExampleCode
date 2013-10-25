<?php
/**
 * SAN Education Individual Completion Status Report
 *
 * Display a date selector and generate button
 * 
 * @package    report
 * @subpackage san_commindivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/index_form.php');
require_once($CFG->dirroot.'/report/san_commindivcomp/lib.php');

$context = get_system_context();
$PAGE->set_context($context);
$PAGE->set_url('/moodle/report/san_commindivcomp/index.php');
$PAGE->set_title(get_string('pagetitle', 'report_san_commindivcomp'));
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pageheading', 'report_san_commindivcomp'));

require_login();
require_capability('report/san_commindivcomp:canview', $context);

$mform = new commindividualcompletionform();
$data = $mform->get_data();

if ($mform->is_cancelled()) {

    redirect($CFG->wwwroot.'/');// Redirect to main page.

} if ($data) {

    if ($data->submitbutton == get_string('previewbutton', 'report_san_commindivcomp')) { // If preview form generate button pressed.

        echo $OUTPUT->header();
        $mform->display();
        echo san_commindivcomp_programtable($data->dateselectorf, $data->dateselectort);
        echo $OUTPUT->footer();

    } else if ($data->exportbutton == get_string('exportbutton', 'report_san_commindivcomp')) { // If export form generate button pressed.

        // Call function.
        san_commindivcomp_export_grades($data->dateselectort, $data->dateselectorf);

    }
} else {

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

}