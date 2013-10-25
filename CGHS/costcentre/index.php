<?php
/**
 * CGHS Cost Centre Screen
 *
 * Settings Page
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot.'/report/costcentre/centremanager_index_form.php');
require_once($CFG->dirroot.'/report/costcentre/costcentre_index_form.php');
require_once($CFG->dirroot.'/report/costcentre/courses_index_form.php');
require_once($CFG->dirroot.'/report/costcentre/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/moodle/report/feedback/index.php');
$PAGE->set_pagelayout('report');

require_login();

$formid = optional_param('formid', null, PARAM_NOTAGS);

global $DB, $OUTPUT;
// Formid tells this page which form to show.
if ($formid == 'centremanager') {

	$PAGE->set_url('/report/costcentre/centremanager_index.php?formid=centremanager');
	$PAGE->set_title('Cost Centre Manager Screen');

	$action = optional_param('action', 0, PARAM_NOTAGS);
	$ccid = optional_param('ccid', 0, PARAM_INT);

	require_capability('report/cghs_centremanager:view', get_system_context());

	$mform = new centremanagerform();

	$managerid = optional_param('managers', null, PARAM_NOTAGS);
	$allcentres = optional_param('allcentres', null, PARAM_NOTAGS);
	$managerscentres = optional_param('managerscentres', null, PARAM_NOTAGS);

	echo $OUTPUT->header();

	print_object($displaybutton);

	if (optional_param('addbutton', null, PARAM_NOTAGS) or optional_param('processbutton', null, PARAM_NOTAGS)) { // Add button pushed.

	    if ($managerid == 'choose') {

	            $mform = new centremanagerform('', array('managers' => 'choose')); // Send id back to form.

	    } else {

	        foreach ($allcentres as $k => $v) {

	            // Create if exists constraint.
	            if ($DB->record_exists('cghs_cost_centre_manager', array('costcentreid'=> $v, 'ccmanageruserid'=> $managerid))) {

	                $mform = new centremanagerform('', array('managers' => $managerid)); // Send id back to form.

	            } else {

	                $newccm = new stdClass();
	                $newccm->costcentreid = $v;
	                $newccm->ccmanageruserid = $managerid;
	                $timenow = time();
	                $newccm->timecreated = $timenow;
	                $DB->insert_record('cghs_cost_centre_manager', $newccm);

	            }
	        }
	    }

	    $mform = new centremanagerform('', array('managers' => $managerid)); // Send id back to form.

	} else if ($removebutton = optional_param('removebutton', 0, PARAM_NOTAGS)) { // Remove button pushed.

	        foreach ($managerscentres as $k => $v) {

	            $DB->delete_records('cghs_cost_centre_manager', array('costcentreid'=> $v, 'ccmanageruserid'=> $managerid));

	        }

	        $mform = new centremanagerform('', array('managers' => $managerid)); // Send id back to form.

	} else if ($allsearchbutton = optional_param('allsearchbutton', 0, PARAM_NOTAGS)) { // All search button clicked.

	    $allsearch = optional_param('allsearch', 0, PARAM_NOTAGS);
	    $mform = new centremanagerform('', array('managers' => $managerid,'allsearch' => $allsearch)); // Send id back to form.

	} else if ($selsearchbutton = optional_param('selsearchbutton', 0, PARAM_NOTAGS)) { // Selected search button clicked.

	    $selectedsearch = optional_param('selectedsearch', 0, PARAM_NOTAGS);
	    $mform = new centremanagerform('', array('managers' => $managerid,'selectedsearch' => $selectedsearch)); // Send id back to form.

	} else if ($clearsearchbutton = optional_param('clearsearchbutton', 0, PARAM_NOTAGS)) { // Reset search.

	    $mform = new centremanagerform('', array('managers' => $managerid)); // Send id back to form.

	}

	$mform->display();
	echo $OUTPUT->footer();

} else if ($formid == 'costcentre') {

	$PAGE->set_pagelayout('report');
	$PAGE->set_url('/report/costcentre/costcentre_index.php?formid=costcentre');
	$PAGE->set_title('Cost Centre Screen');

	$ccid = optional_param('ccid', 0, PARAM_INT);
	$action = optional_param('action', 0, PARAM_NOTAGS);
	$delete = optional_param('confirmdelete', 0, PARAM_NOTAGS);

	require_capability('report/cghs_costcentre:view', get_system_context());

	$mform = new costcentreform();
	$data = $mform->get_data();

	echo $OUTPUT->header();

	if ($data) {

	    if ($data->newcentre) { // New cost centre button submitted.

	        $newcc = new stdClass();
	        $newcc->name = $data->centrename;
	        $timenow = time();
	        $newcc->timecreated = $timenow;
	        $DB->insert_record('cghs_cost_centre', $newcc);

	        $mform = new costcentreform('', array('added' => 'added'));

	    } else if ($delete) { // Delete button pushed.

	        if ($managerexists = $DB->record_exists('cghs_cost_centre_manager', array('costcentreid' => $ccid))) {

	            // If manager is assigner to orgunit you cannot delete it.
	            $mform = new costcentreform('', array('issue' => 'manager'));

	        } else {

	            $DB->delete_records('cghs_cost_centre', array('id'=>$ccid));
	            $mform = new costcentreform('', array('delete' => 'delete'));

	        }
	    }
	} else if ($action) { // Delete button pushed, display confirmation message.

	    $mform = new costcentreform('', array('action' => 'action', 'cc' => $ccid));

	}

	$mform->display();
	echo costcentre_costcentretable();
	echo $OUTPUT->footer();

} else if ($formid == 'courses') {

	$PAGE->set_url($CFG->wwwroot.'/report/costcentre/courses_index.php?formid=courses');
	$PAGE->set_title('Cost Centre Report Submission Screen');
	$PAGE->set_heading('Cost Centre Report Submission Screen');
	$PAGE->set_pagelayout('report');

	require_capability('report/cghs_courses:view', get_system_context());

	$mform = new coursesform();

	if ($data = $mform->get_data()) { // Form submitted.

	    $centre = $data->ccselect;
	    $course = $data->cselect;
	    $datefrom = $data->datefrom;
	    $dateto = $data->dateto;
	    costcentre_export_grades($centre, $course, $datefrom, $dateto);
	    redirect($CFG->wwwroot.'/report/costcentre/index.php?formid=courses');
	    die();

	} else {

	    echo $OUTPUT->header();
	    echo $mform->display();
	    echo $OUTPUT->footer();

	}
} else {
		echo $OUTPUT->header();
	    echo $OUTPUT->footer();
}
