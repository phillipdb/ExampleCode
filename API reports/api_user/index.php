<?php
/**
 * API User Report
 *
 * Index processing page.
 *
 * @package    report
 * @subpackage api_user
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/index_form.php');
require_once($CFG->dirroot.'/report/api_user/lib.php');
$context = get_system_context();
$PAGE->set_context($context);
$PAGE->set_url('/moodle/report/api_user/index.php');
$PAGE->set_title(get_string('pagetitle', 'report_api_user'));
$PAGE->set_pagelayout('report');
$PAGE->set_heading(get_string('pageheading', 'report_api_user'));

require_login();
require_capability('report/api_user:canview', $context);

$mform = new api_user_form();

if ($mform->is_cancelled()) {

    redirect($CFG->wwwroot.'/');// Redirect to main page.

} else if ($data = $mform->get_data()) {

    if ($data->exportbutton == get_string('exportbutton', 'report_api_user')) { // If form export button pressed.

        // Call export function.
        api_user_export($data->dateselectorf, $data->dateselectort, $data->coursesselect);

    } else if ($data->submitbutton) {

        echo $OUTPUT->header();
        $mform->display();
        echo api_user_previewtable($data->dateselectorf, $data->dateselectort, $data->coursesselect);
        echo $OUTPUT->footer();

    }

} else {

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

}