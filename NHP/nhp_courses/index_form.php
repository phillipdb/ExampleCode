<?php
/**
 * ACAT Upcoming Courses Report
 *
 * Display a drop down list of Organisation Units and box for student name
 * search. Display a report for the given parameters
 *
 * @package    report
 * @subpackage nhp_courses
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 Pukunui Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir.'/formslib.php');

class nhp_coursesform extends moodleform {
    public function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;

        $mform->addElement('header', 'headerelement', 'NHP Completed Courses'); // Start fieldset.

        $mform->addElement('submit', 'submitbutton', get_string('buttonname','report_nhp_courses'));

        $mform->addElement('hidden', 'CloseHeaderBeforeThis', '0');  // This is here only to enable closing.
        $mform->closeHeaderBefore('closeheaderafter');               // The header with the table within.
    }
}