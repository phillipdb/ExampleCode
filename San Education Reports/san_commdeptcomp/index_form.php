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

require_once($CFG->libdir.'/formslib.php');

/**
 * Form class.
 **/
class commdepartmentcompletionform extends moodleform {
    /**
     * Create the form and display when called from index.php
     * @param none
     **/
    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $mform->addElement('header', 'headerelement', 'Department Completion Status Report'); // Start fieldset.

        $instructionstext = get_string('instructionstext', 'report_san_commdeptcomp');
        $mform->addElement('static', '', '', $instructionstext);

        $mform->addElement('date_selector', 'dateselectorf', get_string('date', 'report_san_commdeptcomp'));
        $mform->setType('datefrom', PARAM_INT);

        $mform->addElement('date_selector', 'dateselectort', get_string('to', 'report_san_commdeptcomp'));
        $mform->setType('dateto', PARAM_INT);

        $mform->addElement('text', 'idnumber', get_string('staffnumbersearch', 'report_san_commdeptcomp'));

        $mform->addElement('text', 'surname', get_string('surnamesearch', 'report_san_commdeptcomp'));

        $customname = get_string('custom_employmenttype', 'report_san_commdeptcomp');
        $employmentlist = $DB->get_field('user_info_field', 'param1', array('shortname' => $customname));
        $employmentlist = explode("\n",$employmentlist);
        $employmentlist1 = array();
        foreach($employmentlist as $p => $pp) {
            $employmentlist1[$pp] = $pp;
        }
        asort($employmentlist1);
        array_unshift($employmentlist1,get_string('all', 'report_san_commdeptcomp'));
        $mform->addElement('select', 'jobtypeselect', get_string('select_jobtype', 'report_san_commdeptcomp'), $employmentlist1, '');



        // Department Type custom field id.
        $departmentselectq = $DB->get_field('user_info_field',
                                            'param1',
                                            array('shortname' => get_string('departmentcustom',
                                                                            'report_san_commdeptcomp')));
        $departmentselectq = explode("\n",$departmentselectq);
        $departmentselect = array();
        foreach($departmentselectq as $p => $pp) {
            $departmentselect[$pp] = $pp;
        }
        asort($departmentselect);
        array_unshift($departmentselect,get_string('all', 'report_san_commdeptcomp'));
        $mform->addElement('select', 'departmentselect', get_string('select_department', 'report_san_commdeptcomp'), $departmentselect, '');


        $internalq = $DB->get_field('user_info_field', 'param1', array('shortname' => get_string('internalcustom', 'report_san_commdeptcomp')));
        $internalq = explode("\n", $internalq);
        $internalselect = array();
        $internalselect[0] = get_string('all', 'report_san_commdeptcomp');
        foreach ($internalq as $p => $pp) {
            $internalselect[$pp] = $pp;
        }
        asort($internalselect);
        $mform->addElement('select', 'internalselect', get_string('select_internal', 'report_san_commdeptcomp'), $internalselect, '');

        // Course Select.
        $coursesselectq = $DB->get_records('course', array(), 'fullname ASC', 'id, fullname');
        $coursesselect = array();
        $coursesselect[0] = get_string('all', 'report_san_commdeptcomp');
        foreach ($coursesselectq as $k => $v) {
            $coursesselect[$k] = $v->fullname;
        }
        $mform->addElement('select', 'coursesselect', get_string('select_courses', 'report_san_commdeptcomp'), $coursesselect, '');

        // Submit, cancel, export buttons.
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('cancel', 'Cancel');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('previewbutton', 'report_san_commdeptcomp'));
        $buttonarray[] = &$mform->createElement('submit', 'exportbutton', get_string('exportbutton', 'report_san_commdeptcomp'));
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
                $errors['dateselectort'] = get_string('datevalidation', 'report_san_commdeptcomp');
        }
        return $errors;
    }

}