<?php
/**
 * CGHS Cost Centre manager Screen
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
$PAGE->set_url($CFG->dirroot.'/report/costcentre/centremanager_index_form.php');
$PAGE->set_title('Cost Centre Manager Screen');
$PAGE->set_heading('Cost Centre Manager Screen');

require_once($CFG->libdir.'/formslib.php');
require_once('../../config.php');

/**
 * Class to extend moodleform.
 * This class is used to create the from that is displayed on centre manager page.
 *
 */
class centremanagerform extends moodleform {
    /**
     * CGHS Cost Centre manager Screen
     *
     * Function to create the side-to-side table of managers and cost centres.
     */
    public function definition() {
        global $DB;

        $mform =& $this->_form;

        $managers = $this->_customdata['managers'];
        $allsearch = $this->_customdata['allsearch'];
        $selectedsearch = $this->_customdata['selectedsearch'];

        // This hidden element is always here to show which form we are on.
        $mform->addElement('hidden', 'formid', 'centremanager');

        // Start of table and program elements.
        $programlist = '<br>
                         <center><table width=60% border="0" cellspacing=0 cellpadding=0 rules="all">';

        // Get all managers to display in the select box.
        $programlist .= '<tr><td align="center" colspan="3"><h2>'.get_string('centremanager_tabletitle', 'report_costcentre');
        $programlist .=' </h2></td></tr>';
        $programlist .= '<tr><td align="center" colspan="3">';
        $programlist .=     '<select name="managers" id="managers" style="width: 200px;overflow:scroll">';
        $role = $DB->get_record('role', array('shortname' => get_string('centremanager_ccmanagerrole', 'report_costcentre')));
        $context = get_system_context();
        if ($managersq = get_role_users($role->id, $context)) {
            $programlist .=     "<option value=choose>".get_string('centremanager_choose', 'report_costcentre')."</option>" . "\n";
            foreach ($managersq as $k => $v) {
                if ($k == $managers) {

                    $id = $k;
                    $name = $v->firstname." ".$v->lastname;
                    // Fill in select box.
                    $programlist .= "<option value=$id selected>$name</option>" . "\n";

                } else {

                    $id = $k;
                    $name = $v->firstname." ".$v->lastname;
                    // Fill in select box.
                    $programlist .= "<option value=$id>$name</option>" . "\n";

                }
            }
        } else { // Else show no users with costcentremanager role.

            $programlist .=     "<option value=empty>&lt;".get_string('centremanager_nomanagers', 'report_costcentre')."&gt;</option>" . "\n";

        }

        $buttontag = get_string('centremanager_displaycentres', 'report_costcentre');
        $programlist .=     '<input type="submit" name="processbutton" value='.$buttontag.'>';

        if ($managers == 'choose') {

            $programlist .=  '<span style="color:red">*'.get_string('centremanager_managerselect', 'report_costcentre').'</span>';

        } else {
            $managerid = $managers; // Means there is an ID there so set it to display the manager further down.
        }
        $programlist .= '</td></tr>';

        $programlist .= '<tr>';
        $programlist .= '   <td align="left">'.get_string('centremanager_costcentresmanager', 'report_costcentre').'</td>';
        $programlist .= '   <td> </td>';
        $programlist .= '   <td align="left">'.get_string('centremanager_costcentresall', 'report_costcentre').'</td>';
        $programlist .= '</tr>';

        $programlist .= '<tr>';
        $programlist .= '   <td align="left">';
        $programlist .= '       <select name="managerscentres[]" id="managerscentres" multiple="multiple"';
        $programlist .= 'style="width: 200px;overflow:scroll; height: 200px">';
        // Get all of the managers cost centres.
        if ($managerid) { // If managerid in URL.
            $sql = " SELECT cc.id, cc.name 
                     FROM {cghs_cost_centre} cc 
                     INNER JOIN {cghs_cost_centre_manager} cm ON cm.costcentreid = cc.id
                     WHERE cm.ccmanageruserid = ".$managers."
                     AND cc.name LIKE '%".$selectedsearch."%'
                     ORDER BY cc.name ASC";

            if ($centresq = $DB->get_records_sql_menu($sql)) {
                foreach ($centresq as $p => $v) { // Fill in select box.
                    $programlist .= "<option value=$p>$v</option>" . "\n";
                }
                $programlist .= '</select>';

            } else { // Else no centres available.
                $programlist .= "<option value=empty>&lt;".get_string('centremanager_nocentres', 'report_costcentre')."&gt;</option>" . "\n";
                $programlist .= '</select>';
            }

        } else {

            $programlist .= "<option value=empty>".get_string('centremanager_managerselect', 'report_costcentre')."</option>" . "\n";
            $programlist .= '</select>';

        }

        $programlist .= '   </td>';
        $programlist .= '   <td align="left">';
        $programlist .= '       <div id="addcontrols">';
        $programlist .= '           <input type="submit" name="addbutton" value="ADD">';
        $programlist .= '           <br>';
        $programlist .= '           <br>';
        $programlist .= '       </div>';
        $programlist .= '       <div id="removecontrols">';
        $programlist .= '           <input type="submit" name="removebutton" value="REMOVE">';
        $programlist .= '       </div>';
        $programlist .= '   </td>';
        $programlist .= '   <td align="left">';
        $programlist .= '       <select name="allcentres[]" id="allcentres" multiple="multiple"';
        $programlist .= '       style="width: 200px;overflow:scroll; height: 200px">';

        // Get all cost centres.
        $sql = " SELECT id, name 
                FROM {cghs_cost_centre}
                WHERE name LIKE '%".$allsearch."%'
                ORDER BY name ASC";
        if ($centresq = $DB->get_records_sql_menu($sql)) {

            foreach ($centresq as $p => $v) { // Fill in select box.
                $programlist .= "<option value=$p>$v</option>" . "\n";
            }

        } else { // Else no centres available.

            $programlist .= "<option value=empty>&lt;".get_string('centremanager_nocentres', 'report_costcentre')."&gt;</option>" . "\n";

        }

        $programlist .= '       </select>';
        $programlist .= '   </td>';
        $programlist .= '</tr>';

        $search = get_string('centremanager_search', 'report_costcentre');
        $clearsearch = get_string('centremanager_clearsearch', 'report_costcentre');
        $programlist .= '<tr>';
        $programlist .= '   <td align="left">Search: <input type="text" name="selectedsearch"><br><br>';
        $programlist .=     '<input type="submit" name="selsearchbutton" value='.$search.'>     ';
        $programlist .=     '<input type="submit" name="clearsearchbutton" value='.$clearsearch.'>';
        $programlist .= '   </td>';
        $programlist .= '   <td> </td>';
        $programlist .= '   <td align="left">Search: <input type="text" name="allsearch"><br><br>';
        $programlist .=     '<input type="submit" name="allsearchbutton" value='.$search.'>     ';
        $programlist .=     '<input type="submit" name="clearsearchbutton" value='.$clearsearch.'>';
        $programlist .= '   </td>';
        $programlist .= '</tr>';


        $programlist .= '</table></center>';
        $mform->addElement('html', $programlist);
    }
}