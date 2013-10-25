<?php
/**
 * SAN Education Individual Completion Status Report
 *
 * Display a drop down list of programs.
 *
 * @package    report
 * @subpackage san_commindivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/formslib.php');
/**  
 * Moodle form class.
 *
 */
class commindividualcompletionform extends moodleform {
    /**  
     * Create the form for the user to be able to enter parameters.
     *
     */
    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $mform->addElement('header', 'headerelement', 'Individual Completion Status Report'); // Start fieldset.

        $mform->addElement('date_selector', 'dateselectorf', get_string('date', 'report_san_commindivcomp'));
        $mform->setType('datefrom', PARAM_INT);

        $mform->addElement('date_selector', 'dateselectort', get_string('to', 'report_san_commindivcomp'));
        $mform->setType('dateto', PARAM_INT);

        // Submit, cancel, export buttons.
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('cancel', get_string('cancel', 'report_san_commindivcomp'));
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('previewbutton', 'report_san_commindivcomp'));
        $buttonarray[] = &$mform->createElement('submit', 'exportbutton', get_string('exportbutton', 'report_san_commindivcomp'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }

     /**
      * Validation.
      * @param $data, $files
      * @return array
      **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['dateselectort'] < $data['dateselectorf']) {

                $errors['dateselectort'] = get_string('datevalidation', 'report_san_commindivcomp');

        }
        return $errors;
    }

}