<?php
/**
 * CGHS Cost Centre Screen
 *
 * Form/table page
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/moodle/local/costcentre/index_form.php');
$PAGE->set_title('Cost Centre Screen');
$PAGE->set_heading('Cost Centre Screen');

require_once($CFG->libdir.'/formslib.php');
require_once('../../config.php');

/**
 * This class extends Moodleform so that the costcentre form can be created.
 */
class costcentreform extends moodleform {
    /**
     * Function to create table of cost centres and delete button.
     * @return moodleform
    */
    public function definition() {

        global $DB;

        $mform =& $this->_form;

        $deleted = $this->_customdata['delete'];
        $added = $this->_customdata['added'];
        $issue = $this->_customdata['issue'];
        $action = $this->_customdata['action'];
        $cc = $this->_customdata['cc'];

        $mform->addElement('header', 'headerelement', 'Cost Centre Screen'); // Start fieldset.
        $text = get_string('costcentre_warning', 'report_costcentre');

        // This hidden element is always here to show which form we are on.
        $mform->addElement('hidden', 'formid', 'costcentre');

        if ($action) {
            $costcentrename = $DB->get_field('cghs_cost_centre','name', array('id' => $cc), null);
            $mform->addElement('static', '', '', 
                                get_string('costcentre_deletetext', 'report_costcentre').$costcentrename.'?');
            $mform->addElement('hidden', 'ccid', $cc);
            $array = array();
            $array[] =& $mform->createElement('submit', 'confirmdelete', 'Yes');
            $array[] =& $mform->createElement('submit', 'backdelete', 'Cancel');
            $mform->addGroup($array, 'coarray', '', array(' '), false);

        } else {
            $mform->addElement('static', '', '', '<span style="text-align:left">'.$text.'</span>');
            if ($issue) {

                $text = get_string('costcentre_managerwarning', 'report_costcentre');
                $mform->addElement('static', '', '', '<span style="color:red" >'.$text.'</span>');

            }
            if ($deleted) {
                // If delete button clicked.
                $text = get_string('costcentre_centredeleted', 'report_costcentre');
                $mform->addElement('static', '', '', '<span style="color:red" >'.$text.'</span>');

            }
            if ($added) {
                // If add button clicked.
                $text = get_string('costcentre_centreadded', 'report_costcentre');
                $mform->addElement('static', '', '', '<span style="color:red" >'.$text.'</span>');

            }            
            $array = array();
            $attributes='size="80"';
            $array[] =& $mform->createElement('text', 'centrename', '', $attributes);
            $array[] =& $mform->createElement('submit', 'newcentre', 'Add');
            $mform->addGroup($array, 'coarray', 'Cost Centre:', array(' '), false);
            $mform->setDefault('centrename', get_string('costcentre_newcentrename', 'report_costcentre'));
        }
        
    }
    /**
     * Validation for above function.
     * @param $data, $files
     * @return array
    */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['centrename'] == null) {

            $errors['coarray'] = get_string('costcentre_required', 'report_costcentre');

        } else if ($data['centrename'] == get_string('costcentre_newcentrename', 'report_costcentre')  & $data['newcentre'] == 'Add' ) {

            $errors['coarray'] = get_string('costcentre_required', 'report_costcentre');

        }

        return $errors;
    }
}