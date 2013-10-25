<?php
/**
 * API Completion Report
 *
 * Index processing page.
 *
 * @package    report
 * @subpackage api_completion
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/formslib.php');

/**
 * Form class.
 **/
class api_completion_form extends moodleform {
    /**
     * Create the form and display when called from index.php
     * @param none
     **/
    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $mform->addElement('header', 'headerelement', get_string('heading', 'report_api_completion')); // Start fieldset.

        // Course Select.
        $coursesselectq = $DB->get_records_menu('course', array('visible' => '1'), 'fullname ASC', 'id, fullname');
        $mform->addElement('select', 'coursesselect', get_string('select_courses', 'report_api_completion'), $coursesselectq, '');

        $mform->addElement('date_selector', 'dateselectorf', get_string('date', 'report_api_completion'));
        $mform->setType('datefrom', PARAM_INT);

        $mform->addElement('date_selector', 'dateselectort', get_string('to', 'report_api_completion'));
        $mform->setType('dateto', PARAM_INT);

        // Submit, cancel, export buttons.
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('cancel', 'Cancel');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('previewbutton', 'report_api_completion'));
        $buttonarray[] = &$mform->createElement('submit', 'exportbutton', get_string('exportbutton', 'report_api_completion'));
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
                $errors['dateselectort'] = get_string('datevalidation', 'report_api_completion');
        }
        return $errors;
    }

}