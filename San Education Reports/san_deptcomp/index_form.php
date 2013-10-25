<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * String definitions
 *
 * @package    report
 * @subpackage san_deptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/formslib.php');

/**
 * Extending Moodle Form to create a new form.
 **/

class departmentcompletionform extends moodleform {

    /**
     * Create the form with all the elements, databaseu queries to fill the drop down boxes.
     **/
    public function definition() {

        global $CFG, $DB;

        $mform =& $this->_form;
        $mform->addElement('header', 'headerelement', 'Department Completion Status Report'); // Start fieldset.
        $instructionstext = get_string('instructionstext', 'report_san_deptcomp');
        $mform->addElement('static', '', '', $instructionstext);
        $mform->addElement('date_selector', 'dateselectorf', get_string('date', 'report_san_deptcomp'));
        $mform->setType('datefrom', PARAM_INT);
        $mform->addElement('date_selector', 'dateselectort', get_string('dateto', 'report_san_deptcomp'));;
        $mform->setType('dateto', PARAM_INT);

        $mform->addElement('text', 'idnumber', get_string('staffnumbersearch', 'report_san_deptcomp'));
        $mform->addElement('text', 'surname', get_string('surnamesearch', 'report_san_deptcomp'));

        // Job Type Select.
        $jobtypename = get_string('custom_jobtype', 'report_san_deptcomp');
        $jobtypeq = $DB->get_field('user_info_field', 'param1', array('shortname' => $jobtypename));
        $jobtypeq = explode("\n",$jobtypeq);
        $jobtypeselect = array();
        foreach($jobtypeq as $p => $pp) {
            $jobtypeselect[$pp] = $pp;
        }
        asort($jobtypeselect);
        array_unshift($jobtypeselect,get_string('all', 'report_san_deptcomp'));
        $mform->addElement('select', 'jobtypeselect', get_string('select_jobtype', 'report_san_deptcomp'), $jobtypeselect, '');

        // Department Select.
        $departmentq = $DB->get_records('user', array('deleted' => 0), null, 'id, department');
        $departmentselect = array();
        $departmentselect[0] = get_string('all', 'report_san_deptcomp');
        foreach ($departmentq as $k => $v) {
            $departmentselect[$v->department] = $v->department;
        }
        $departmentselect = array_unique($departmentselect);
        $selectlabel =  get_string('select_costcentre', 'report_san_deptcomp');
        $mform->addElement('select', 'departmentselect', $selectlabel, $departmentselect, '');
        // Due to client recommendations this department field is labeled as Cost Centre on the form.

        // Internal Company Select.
        $internalq = $DB->get_records('user', array('deleted' => 0), null, 'id, institution');
        $internalselect = array();
        $internalselect[0] = get_string('all', 'report_san_deptcomp');
        foreach ($internalq as $k => $v) {
            $internalselect[$v->institution] = $v->institution;
        }
        $internalselect = array_unique($internalselect);
        $selectlabel =  get_string('select_internal', 'report_san_deptcomp');
        $mform->addElement('select', 'internalselect', $selectlabel , $internalselect, '');

        // Course Select.
        $coursesselectq = $DB->get_records('course', array(), 'fullname ASC', 'id, fullname');
        $coursesselect = array();
        $coursesselect[0] = get_string('all', 'report_san_deptcomp');
        foreach ($coursesselectq as $k => $v) {
            $coursesselect[$k] = $v->fullname;
        }
        $mform->addElement('select', 'coursesselect', get_string('select_courses', 'report_san_deptcomp'), $coursesselect, '');

        // Submit, reset, cancel buttons.
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('cancel', 'Cancel');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('previewbutton', 'report_san_deptcomp'));
        $buttonarray[] = &$mform->createElement('submit', 'exportbutton', get_string('exportbutton', 'report_san_deptcomp'));
        $buttonarray[] = &$mform->createElement('submit', 'resetbutton','Reset Form');
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
               $errors['dateselectort'] = get_string('datevalidation', 'report_san_deptcomp');
        }
        return $errors;
    }

}