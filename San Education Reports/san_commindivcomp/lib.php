<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Library Functions
 *
 * @package    report
 * @subpackage san_commindivcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**  
 * Export results to a .csv file, called from index_from.php
 * @param $dateto, $datefrom
 * @return Downloaded .csv file.
 */

function san_commindivcomp_export_grades($dateto, $datefrom) {
    global $CFG, $DB, $USER;

    $newsettings = get_config('report_san_commindivcomp');
    // If custom settings are not set yet use defaults.
    if (empty($newsettings)) {
        $setting_position = get_string('position', 'report_san_commindivcomp');
        $setting_location = get_string('location', 'report_san_commindivcomp');
        $setting_employment = get_string('employment', 'report_san_commindivcomp');
    } else {
        $setting_position = $newsettings->report_san_commindivcomp_position;
        $setting_location = $newsettings->report_san_commindivcomp_location;
        $setting_employment = $newsettings->report_san_commindivcomp_employment;
    }

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "cc.id", "c.id"));
    $student ="SELECT $studentcourse AS usercourseid, 
                u.firstname AS firstname,  u.lastname AS lastname, c.id AS courseid, 
                cc.timecompleted AS compdate,  c.fullname AS course,  c.idnumber AS code, gg.finalgrade 
            FROM ({user} u
            INNER JOIN {course_completions} cc ON u.id = cc.userid)
            INNER JOIN {course} c ON cc.course = c.id
            LEFT JOIN (SELECT id, courseid
                       FROM {grade_items} gi
                       WHERE gi.itemtype = 'course') as a on a.courseid = c.id
            LEFT JOIN {grade_grades} gg on a.id = gg.itemid and gg.userid = cc.userid
            WHERE cc.timecompleted > ? AND cc.timecompleted < ?
            AND u.id = ?
            AND gg.finalgrade = 100.00000
            ORDER BY c.fullname";
    // Run student sql.
    $studentquery = $DB->get_records_sql($student, array($datefrom, $dateto, $USER->id));

    // CSV file creation and Data Export to CSV File.
    $filename = 'csvexport_'.date("Ymd").'.csv';
    @header('Content-Disposition: attachment; filename='.$filename);
    @header('Content-Type: text/csv');

    $csvhead = array(get_string('userid', 'report_san_commindivcomp'));
    $csvhead[] = get_string('firstname', 'report_san_commindivcomp');
    $csvhead[] = get_string('surname', 'report_san_commindivcomp');
    $csvhead[] = get_string('email', 'report_san_commindivcomp');
    $csvhead[] = get_string('employmenttype', 'report_san_commindivcomp');
    $csvhead[] = get_string('positiontitle', 'report_san_commindivcomp');
    $csvhead[] = get_string('department', 'report_san_commindivcomp');
    $csvhead[] = get_string('internalcompany', 'report_san_commindivcomp');
    $csvhead[] = get_string('coursefullname', 'report_san_commindivcomp');
    $csvhead[] = get_string('coursecode', 'report_san_commindivcomp');
    $csvhead[] = get_string('completiondate', 'report_san_commindivcomp');
    $csvhead[] = get_string('cpdpoints', 'report_san_commindivcomp');
    $csvhead[] = get_string('grade', 'report_san_commindivcomp');

    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";
    if ($studentquery) {

        // Looping through query output to write into CSV file.
        foreach ($studentquery as $u) {

            $compdate = null;
            $cpdpoints = null;

                // Employment Type custom field id.
            $sql = "SELECT data FROM mdl_user_info_field i INNER JOIN mdl_user_info_data id on id.fieldid =i.id
                    WHERE i.shortname = ? AND id.userid = ?";
            $employment =$DB->get_field_sql($sql, array($setting_employment, $USER->id), null);

            // Position Title custom field id.
            $sql = "SELECT data FROM mdl_user_info_field i INNER JOIN mdl_user_info_data id on id.fieldid =i.id
                    WHERE i.shortname = ? AND id.userid = ?";
            $position =$DB->get_field_sql($sql, array($setting_position, $USER->id), null);

            // Location custom field id.
            $sql = "SELECT data FROM mdl_user_info_field i INNER JOIN mdl_user_info_data id on id.fieldid =i.id
                    WHERE i.shortname = ? AND id.userid = ?";
            $location =$DB->get_field_sql($sql, array($setting_location, $USER->id), null);

            $certificatesql =" SELECT c.printseal FROM {certificate} c INNER JOIN {certificate_issues} ci ON
                                ci.certificateid = c.id WHERE ci.userid = ? AND c.course = ?";
            $certificate = $DB->get_records_sql($certificatesql, array($USER->id, $u->courseid), null);

            $institution = $DB->get_field('user', 'institution', array('id' => $USER->id), null);

            $department = $DB->get_field('user','department',array('id' => $USER->id), null);

            // Change completion date to normal date view.
            foreach ($certificate as $k){
                if ($k->printseal != 0) {
                    $cpdpoints = str_replace('0', '', $k->printseal);
                    $cpdpoints = str_replace('_', '.', $cpdpoints);
                    $cpdpoints = str_replace('.png', '', $cpdpoints);
                    $cpdpoints = str_replace('point', '0.', $cpdpoints);
                } else {
                    $cpdpoints = '0';
                }
            }

            $printarray = array(str_replace(',', ' ', $USER->idnumber));
            $printarray[] = str_replace(',', ' ', $USER->firstname);
            $printarray[] = str_replace(',', ' ', $USER->lastname);
            $printarray[] = str_replace(',', ' ', $USER->email);
            $printarray[] = str_replace(',', ' ', $employment);
            $printarray[] = str_replace(',', ' ', $position);
            $printarray[] = str_replace(',', ' ', $department);
            $printarray[] = str_replace(',', ' ', $location);
            $printarray[] = str_replace(',', ' ', $u->course);
            $printarray[] = str_replace(',', ' ', $u->code);
            $printarray[] = str_replace(',', ' ', date('d/m/Y', $u->compdate));
            $printarray[] = str_replace(',', ' ', $cpdpoints);
            $printarray[] = str_replace(',', ' ', get_string('gradecompleted', 'report_san_commindivcomp'));

            $line = implode(',', $printarray);
            echo $line;
            echo "\n";

        }

    } else {

        $printarray = array("No Records Found");
        $line = implode(',', $printarray);
        echo $line;
        echo "\n";

    }

}

/**  
 * Returns a table of relevant Organisation Units
 *
 * @param $datefrom, $dateto
 * @return html_writer table
 */
function san_commindivcomp_programtable($datefrom, $dateto) {
    global $CFG, $DB, $USER;

    $newsettings = get_config('report_san_commindivcomp');
    // If custom settings are not set yet use defaults.
    if (empty($newsettings)) {
        $setting_position = get_string('position', 'report_san_commindivcomp');
        $setting_location = get_string('location', 'report_san_commindivcomp');
        $setting_employment = get_string('employment', 'report_san_commindivcomp');
    } else {
        $setting_position = $newsettings->report_san_commindivcomp_position;
        $setting_location = $newsettings->report_san_commindivcomp_location;
        $setting_employment = $newsettings->report_san_commindivcomp_employment;
    }
    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "cc.id", "c.id"));
    $student ="SELECT $studentcourse AS usercourseid, 
                    u.firstname AS firstname,  u.lastname AS lastname, c.id AS courseid, 
                    cc.timecompleted AS compdate,  c.fullname AS course,  c.idnumber AS code, gg.finalgrade 
                FROM ({user} u
                INNER JOIN {course_completions} cc ON u.id = cc.userid)
                INNER JOIN {course} c ON cc.course = c.id
                LEFT JOIN (SELECT id, courseid
                           FROM {grade_items} gi
                           WHERE gi.itemtype = 'course') as a on a.courseid = c.id
                LEFT JOIN {grade_grades} gg on a.id = gg.itemid and gg.userid = cc.userid
                WHERE cc.timecompleted > ? AND cc.timecompleted < ?
                AND u.id = ?
                AND gg.finalgrade = 100.00000
                ORDER BY c.fullname";
    // Run student sql.
    $studentquery = $DB->get_records_sql($student, array($datefrom, $dateto, $USER->id));

    // Create the table headings.
    $table = new html_table();
    $table->width = '100%';
    $table->tablealign = 'left';
    $table->cellpadding = '1px';

    // Set the row heading.
    $row = new html_table_row();
    $table->data[] = $row;

    $normaldatefrom = date('d/m/Y', $datefrom);
    $normaldateto = date('d/m/Y', $dateto);

    // Employment Type custom field id.
    $sql = "SELECT data FROM mdl_user_info_field i INNER JOIN mdl_user_info_data id on id.fieldid =i.id
            WHERE i.shortname = ? AND id.userid = ?";
    $employment =$DB->get_field_sql($sql, array($setting_employment, $USER->id));

    // Position Title custom field id.
    $sql = "SELECT data FROM mdl_user_info_field i INNER JOIN mdl_user_info_data id on id.fieldid =i.id
            WHERE i.shortname = ? AND id.userid = ?";
    $position = $DB->get_field_sql($sql, array($setting_position, $USER->id));

    // Location custom field id.
    $sql = "SELECT data FROM mdl_user_info_field i INNER JOIN mdl_user_info_data id on id.fieldid =i.id
            WHERE i.shortname = ? AND id.userid = ?";
    $location =$DB->get_field_sql($sql, array($setting_location, $USER->id));

    $institution = $DB->get_field('user', 'institution', array('id' => $USER->id), null);

    $department = $DB->get_field('user','department',array('id' => $USER->id), null);

    // Create the report heading.
    $row = new html_table_row();
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('staffnumber', 'report_san_commindivcomp').' '.$USER->idnumber ."<br>";
    $cell->text .= get_string('firstname', 'report_san_commindivcomp').' '.$USER->firstname ."<br>";
    $cell->text .= get_string('surname', 'report_san_commindivcomp').' '.$USER->lastname ."<br>";
    $cell->text .= get_string('emailaddress', 'report_san_commindivcomp').' '.$USER->email ."<br>";
    $cell->text .= get_string('employmenttype', 'report_san_commindivcomp').' '.$employment."<br>";
    $cell->text .= get_string('positiontitle', 'report_san_commindivcomp').' '.$position ."<br>";
    $cell->text .= get_string('department', 'report_san_commindivcomp').' '.$department ."<br>";
    $cell->text .= get_string('internalcompany', 'report_san_commindivcomp').' '.$location."<br>";
    $cell->text .= get_string('reportdatefrom', 'report_san_commindivcomp').' '.$normaldatefrom .''.get_string('to', 'report_san_commindivcomp'). $normaldateto. "<br>";
    $cell->colspan = 5;
    $cell->style = "text-align:left; font-size:20px;";
    $row->cells[] = $cell;
    $table->data[] = $row; // End of report heading.

    // Set the row heading.
    $row = new html_table_row();

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursefullname', 'report_san_commindivcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursecode', 'report_san_commindivcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('completiondate', 'report_san_commindivcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('grade', 'report_san_commindivcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('cpdpoints', 'report_san_commindivcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Cycle through SQL results filling in the html_table data.
    $table->data[] = $row;

    if ($studentquery) {

        $total = 0;
        foreach ($studentquery as $c => $v) {
            $compdate = null;
            $cpdpoints = null;
            // Set the row heading object.
            $row = new html_table_row();

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->course;
            $cell->style = 'text-align:left; width:33%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->code;
            $cell->style = 'text-align:left; width:33%; font-size:16px;';
            $row->cells[] = $cell;

            $certificatesql =" SELECT c.printseal FROM {certificate} c INNER JOIN {certificate_issues} ci ON
                                ci.certificateid = c.id WHERE ci.userid = ? AND c.course = ?";
            $certificate = $DB->get_records_sql($certificatesql, array($USER->id, $v->courseid));
            // Change completion date to normal date view.
            foreach ($certificate as $k) {
                if ($k->printseal != 0) {
                    $cpdpoints = str_replace('0', '', $k->printseal);
                    $cpdpoints = str_replace('_', '.', $cpdpoints);
                    $cpdpoints = str_replace('.png', '', $cpdpoints);
                    $cpdpoints = str_replace('point', '0.', $cpdpoints);
                } else {
                    $cpdpoints = '0';
                }
            }

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = date('d/m/Y', $v->compdate);
            $cell->style = 'text-align:left; width:33%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = get_string('gradecompleted', 'report_san_commindivcomp');
            $cell->style = 'text-align:left; width:33%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $cpdpoints;
            $cell->style = 'text-align:left; width:33%; font-size:16px;';
            $row->cells[] = $cell;

            $table->data[] = $row;
            switch ($cpdpoints) {
                case 0:
                    $total = $total + 0;
                    break;
                case "0.5":
                    $total = $total + 0.5;
                    break;
                case "1":
                    $total = $total++;
                    break;
                case "1.5":
                    $total = $total + 1.5;
                    break;
                case "2":
                    $total = $total + 2;
                    break;
                case "2.5":
                    $total = $total + 2.5;
                    break;
                case "3":
                    $total = $total + 3;
                    break;
                case "3.5":
                    $total = $total + 3.5;
                    break;
                case "4":
                    $total = $total + 4;
                    break;
                case "4.5":
                    $total = $total + 4.5;
                    break;
                case "5":
                    $total = $total + 5;
                    break;
                case "5.5":
                    $total = $total + 5.5;
                    break;
                case "6":
                    $total = $total + 6;
                    break;
                case "6.5":
                    $total = $total + 6.5;
                    break;
                case "7":
                    $total = $total + 7;
                    break;
                case "8":
                    $total = $total + 8;
                    break;
                case "9":
                    $total = $total + 9;
                    break;
                case "10":
                    $total = $total + 10;
                    break;
            }
        }
        // Set the total points row.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('totalcolumn', 'report_san_commindivcomp');
        $cell->style = 'text-align:right; font-size:16px;';
        $cell->colspan = 4;
        $row->cells[] = $cell;

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = $total;
        $cell->style = 'text-align:left; font-size:16px;';
        $cell->colspan = 1;
        $row->cells[] = $cell;

        $table->data[] = $row;

    } else {

        // Set the row heading.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('noresults', 'report_san_commindivcomp');
        $cell->style = 'text-align:left; font-size:16px;';
        $cell->colspan = 5;
        $row->cells[] = $cell;

        $table->data[] = $row;

    }
    return html_writer::table($table); // Return table.
}
