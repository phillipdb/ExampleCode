<?php
/**
 * CGHS FEED BACK REPORT
 *
 * Index Page.
 *
 * @package    Report
 * @subpackage feedback
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir.'/formslib.php');
/**
 * Class extending moodleform to help with the development of the feedback form
 * 
 */
class feedbackform extends moodleform {
    /**
     * This function uses the add_element calls to create a moodleform.
     * @return moodleform
     */
    public function definition() {
        global $CFG, $DB, $USER;

		$mform =& $this->_form;

        $centre = $this->_customdata['ccselect'];
        $display = $this->_customdata['display'];
        $datefrom = $this->_customdata['datefrom'];
        $dateto = $this->_customdata['dateto'];
        $courseid = $this->_customdata['courseid'];

        if (has_capability('report/feedback:managerview', get_system_context())) { 
			// If the user logged in is a manager they see the org unit they are the manager of. 
	        $mform->addElement('header', 'headerelement', get_string('reporttitle', 'report_feedback')); // Start fieldset.

            $centres = feedback_managerarray();
	        $centreselect = $mform->addElement('select', 'ccselect', get_string('costcentre', 'report_feedback'), $centres, '');

            $courses = $DB->get_records_menu('course', array('visible' => '1'), null, 'id, fullname');
            $course = array();
            foreach ($courses as $k => $v) {
                $course[$k] = $v;
            }
            asort($course);
            $course = array(0=>'All')+$course;

            $staffselect = $mform->addElement('select', 
                                                'courseselect',
                                                get_string('studentname', 'report_feedback'),
                                                $course, 'wrap="virtual" rows="20" cols="50"');

            $mform->addElement('date_selector', 'datefrom', get_string('datefrom', 'report_feedback'));
            $mform->addElement('date_selector', 'dateto', get_string('dateto', 'report_feedback'));
            $mform->setType('datefrom', PARAM_INT);
            $mform->setType('dateto', PARAM_INT);

            $mform->addElement('checkbox', 'onlyuser', get_string('onlyuser', 'report_feedback'));

	       	// Display, Generate buttons.
	        $buttonarray=array();
	        $buttonarray[] = &$mform->createElement('submit', 'displaybutton', get_string('display','report_feedback'));
	        $buttonarray[] = &$mform->createElement('submit', 'generatebutton', get_string('generate','report_feedback'));
	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

	        $mform->addElement('hidden', 'closeheaderafter', '0');  // This is here only to enable closing.
	        $mform->closeHeaderBefore('closeheaderafter');          // The header with the table within.

		} else {

            $mform->addElement('header', 'headerelement', get_string('reporttitle', 'report_feedback')); // Start fieldset.

            $mform->addElement('static', 'description', null, get_string('studentblurb', 'report_feedback'));

            $buttonarray=array();
            $buttonarray[] = &$mform->createElement('submit', 'displaybutton', get_string('display','report_feedback'));
            $buttonarray[] = &$mform->createElement('submit', 'generatebutton', get_string('generate','report_feedback'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

            $mform->addElement('hidden', 'closeheaderafter', '0');  // This is here only to enable closing.
            $mform->closeHeaderBefore('closeheaderafter');            // The header with the table within.
        }
    }

    /**  
     * Validation
     * @param $data, $files
     * @return array
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['ccselect'] == get_string('requiredmessage', 'report_feedback')) {

            $errors['ccselect'] = get_string('requiredcentre', 'report_feedback');

        }

        return $errors;
    } 
}