<?php
/**
 * CGHS Cost Centre Screen
 *
 * Index Page.
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot.'/report/costcentre/costcentre_index_form.php');
require_once($CFG->dirroot.'/report/costcentre/lib.php');

$PAGE->set_pagelayout('report');
$PAGE->set_url('/report/costcentre/costcentre_index.php?formid=costcentre');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Cost Centre Screen');

$ccid = optional_param('ccid', 0, PARAM_INT);
$action = optional_param('action', 0, PARAM_NOTAGS);
$delete = optional_param('confirmdelete', 0, PARAM_NOTAGS);

require_login();
require_capability('report/cghs_costcentre:view', get_system_context());
global $DB, $OUTPUT;

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