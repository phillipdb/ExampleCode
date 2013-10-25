<?php
/**
 * CGHS Completed Courses Report
 *
 * Display a drop down list of cost centers and courses, 
 * and date range selector. Display a report for the given parameters
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

/**
 * Class extending moodleforms to create the elements of coursesform.
 */
class coursesform extends moodleform {
    /**
     * Function to create the report form with date input etc.
     * @return moodleform
    */
    public function definition() {
        global $CFG, $DB, $USER;

        $mform =& $this->_form;

        $mform->addElement('header', 'headerelement', 'Cost Centre Report'); // Start fieldset.

        // This hidden element is always here to show which form we are on.
        $mform->addElement('hidden', 'formid', 'courses');

        if(has_capability('moodle/site:config', get_system_context())) { // User logged in is sysadmin.
            $centresq = $DB->get_records_sql('SELECT id, name FROM {cghs_cost_centre} 
                                                ORDER BY name ASC', array());
        } else { // Else they are a department manager.
            $sql = "SELECT cc.id, cc.name
                    FROM {cghs_cost_centre} cc
                    INNER JOIN {cghs_cost_centre_manager} cm ON cm.costcentreid = cc.id
                    WHERE cm.ccmanageruserid = ?
                    ORDER BY cc.name ASC";
            $centresq = $DB->get_records_sql($sql,array($USER->id));
        }

        $centres = array();
        // No cost centres available.
        if (empty($centresq)) {
            $centres[get_string('courses_empty', 'report_costcentre')] = get_string('courses_empty', 'report_costcentre');
        } else {
            foreach ($centresq as $k => $v) {
                $centres[$v->name] = $v->name;
            }
        }
        // Fill in the course select box.
        $courseq = $DB->get_records_menu('course', null, 'fullname ASC', 'id, fullname'); 
        $courses = array();
        $courses [0] = 'All';
        foreach ($courseq as $k => $v) {
            $courses[$k] = $v;
        }

        $mform->addElement('select',
                            'ccselect',
                            get_string('courses_selectcc', 'report_costcentre'),
                            $centres, 'wrap="virtual" rows="20" cols="50"');
        $mform->addElement('select', 'cselect',
                            get_string('courses_selectc', 'report_costcentre'),
                            $courses,
                            'wrap="virtual" rows="20" cols="50"');

        $mform->addElement('date_selector', 'datefrom', get_string('courses_datefrom', 'report_costcentre'));
        $mform->addElement('date_selector', 'dateto', get_string('courses_dateto', 'report_costcentre'));
        $mform->setType('datefrom', PARAM_INT);
        $mform->setType('dateto', PARAM_INT);

        $mform->addElement('submit', 'submitbutton', get_string('courses_buttonname', 'report_costcentre'));
    }

    /**
     * Validation for above function.
     * @param $data, $files
     * @return array
     **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['dateto'] < $data['datefrom']) {
            $errors['dateto'] = get_string('courses__date', 'report_costcentre');
        }
        if ($data['ccselect'] == get_string('courses__empty', 'report_costcentre')) {
            $errors['ccselect'] = get_string('courses__emptyerror', 'report_costcentre');
        }
        return $errors;
    }
}