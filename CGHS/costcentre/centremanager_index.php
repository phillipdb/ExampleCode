<?php
/**
 * CGHS Cost Centre Manager Screen
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
require_once($CFG->dirroot.'/report/costcentre/centremanager_index_form.php');

$PAGE->set_pagelayout('report');
$PAGE->set_url('/report/costcentre/centremanager_index.php?formid=centremanager');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Cost Centre Manager Screen');

$action = optional_param('action', 0, PARAM_NOTAGS);
$ccid = optional_param('ccid', 0, PARAM_INT);

require_login();
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