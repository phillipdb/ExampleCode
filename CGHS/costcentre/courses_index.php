<?php
/**
 * Index page.
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/courses_index_form.php');
require_once(dirname(__FILE__).'/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot.'/report/costcentre/courses_index.php?formid=courses');
$PAGE->set_title('Cost Centre Report Submission Screen');
$PAGE->set_heading('Cost Centre Report Submission Screen');
$PAGE->set_pagelayout('report');

require_login();
require_capability('report/cghs_courses:view', get_system_context());

$mform = new coursesform();

if ($data = $mform->get_data()) { // Form submitted.

    $centre = $data->ccselect;
    $course = $data->cselect;
    $datefrom = $data->datefrom;
    $dateto = $data->dateto;
    costcentre_export_grades($centre, $course, $datefrom, $dateto);
    redirect($CFG->wwwroot.'/report/costcentre/courses_index.php');

} else {

    echo $OUTPUT->header();
    echo $mform->display();
    echo $OUTPUT->footer();

}