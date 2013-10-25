<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Library Functions
 *
 * @package    report
 * @subpackage san_commdeptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**  
 * Exports results to a .CSV file.
 *
 * @param $datefrom, $dateto, $idnumber, $surname, $jobtype, $department, $internal, $courses.
 * @return download .CVS file
 */
function san_commdeptcomp_export_grades($datefrom, $dateto, $idnumber, $surname, $jobtype, $department, $internal, $courses) {
    global $CFG, $DB;

    $newcustomshortname = get_config('report_san_commdeptcomp');

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "cc.id", "c.id"));
    $student .=" SELECT DISTINCT $studentcourse AS usercourseid, u.id AS userid, u.idnumber AS idnumber, 
            u.firstname AS firstname,  u.lastname AS lastname,  u.institution AS institution, 
            u.department AS department, u.email, dept.data AS customdepartment, jobt.data AS customjobtype, 
            loca.data AS customlocation, c.id AS courseid, c.id as courseid, cc.timecompleted AS compdate, 
            c.fullname AS course,  c.idnumber AS code, gg.finalgrade 
        FROM ({user} u
        INNER JOIN {course_completions} cc ON u.id = cc.userid)
        INNER JOIN {course} c ON cc.course = c.id
        INNER JOIN {user_info_data} id on id.userid = u.id 
        LEFT JOIN (SELECT id, courseid
                   FROM {grade_items} gi
                   WHERE gi.itemtype = 'course') as a on a.courseid = c.id
        LEFT JOIN {grade_grades} gg on a.id = gg.itemid and gg.userid = cc.userid
        INNER JOIN (SELECT u.id , dep.data 
                        FROM {user} u
                        LEFT JOIN 
                       (SELECT uid.userid, uid.data 
                       FROM {user_info_data} uid 
                       INNER JOIN {user_info_field} uif on uif.id = uid.fieldid
                       WHERE uif.shortname = ?) dep ON dep.userid = u.id) dept ON u.id = dept.id
            INNER JOIN (SELECT u.id , job.data 
                        FROM {user} u
                        LEFT JOIN (SELECT uid.userid, uid.data 
                        FROM {user_info_data} uid 
                        INNER JOIN {user_info_field} uif on uif.id = uid.fieldid
                        WHERE uif.shortname = ?) job ON job.userid = u.id) jobt ON u.id = jobt.id
            INNER JOIN (SELECT u.id , loc.data 
                        FROM {user} u
                        LEFT JOIN (SELECT uid.userid, uid.data 
                                    FROM {user_info_data} uid 
                                    INNER JOIN {user_info_field} uif on uif.id = uid.fieldid
                                    WHERE uif.shortname = ?) loc 
                                    ON loc.userid = u.id) loca ON u.id = loca.id
        WHERE cc.timecompleted > ? AND cc.timecompleted < ?
        AND gg.finalgrade = 100.00000
        AND u.idnumber LIKE '%".$idnumber."%' AND u.lastname LIKE '%".$surname."%' ";

    // Sort out search parameters.
    if ($courses != 0 ) { // If all courses not chosen.
        $student .=" AND c.id = $courses ";
    }
    if ($internal != '0') {
        $student .= " AND loca.data = '".$internal."'";
    }
    if ($department!= '0') {
        $student .= " AND dept.data = '".$department."'";
    }
    if ($jobtype != '0') {
        $student .= " AND jobt.data = '".$jobtype."'";
    }


    $student .= " ORDER BY 5, 4, 14";

    // CSV file creation and Data Export to CSV File.
    $filename = 'csvexport_'.date("Ymd").'.csv';
    @header('Content-Disposition: attachment; filename='.$filename);
    @header('Content-Type: text/csv');
    $csvhead = array(get_string('staffid', 'report_san_commdeptcomp'));
    $csvhead[] = get_string('firstname', 'report_san_commdeptcomp');
    $csvhead[] = get_string('surname', 'report_san_commdeptcomp');
    $csvhead[] = get_string('email', 'report_san_commdeptcomp');
    $csvhead[] = get_string('employmenttype', 'report_san_commdeptcomp');
    $csvhead[] = get_string('positiontitle', 'report_san_commdeptcomp');
    $csvhead[] = get_string('department', 'report_san_commdeptcomp');
    $csvhead[] = get_string('internalcompany', 'report_san_commdeptcomp');
    $csvhead[] = get_string('coursefullname', 'report_san_commdeptcomp');
    $csvhead[] = get_string('coursecode', 'report_san_commdeptcomp');
    $csvhead[] = get_string('completiondate', 'report_san_commdeptcomp');
    $csvhead[] = get_string('cpdpoints', 'report_san_commdeptcomp');
    $csvhead[] = get_string('grade', 'report_san_commdeptcomp');

    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";
    if ($users = $DB->get_records_sql($student, array($newcustomshortname->report_san_commdeptcomp_department,
                                                        $newcustomshortname->report_san_commdeptcomp_employment,
                                                        $newcustomshortname->report_san_commdeptcomp_internal,
                                                        $datefrom, 
                                                        $dateto))) {
        // Looping through query output to write into CSV file.
        foreach ($users as $u) {

            $compdate = null;
            $cpdpoints = null;

            // Position Title custom field id.
            $sql = "SELECT data FROM {user_info_field} i
                    INNER JOIN {user_info_data} id on id.fieldid =i.id
                    WHERE i.shortname = ? AND id.userid = ?";
            $position =$DB->get_field_sql($sql, array($newcustomshortname->report_san_commdeptcomp_position, $u->userid), null);

            $certificatesql =" SELECT c.printseal FROM {certificate} c INNER JOIN {certificate_issues} ci ON 
                                ci.certificateid = c.id WHERE ci.userid = ? AND c.course = ?";
            $certificate = $DB->get_records_sql($certificatesql, array($u->userid, $u->courseid), null);

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

            $grade = str_replace(".00000", ".0", $u->finalgrade);
            if ($grade == '100'){
                $grade = 'C';
            }

            $printarray = array(str_replace(',', ' ', $u->idnumber));
            $printarray[] = str_replace(', ', ' ', $u->firstname);
            $printarray[] = str_replace(', ', ' ', $u->lastname);
            $printarray[] = str_replace(', ', ' ', $u->email);
            $printarray[] = str_replace(', ', ' ', $u->customjobtype);
            $printarray[] = str_replace(', ', ' ', $position);
            $printarray[] = str_replace(', ', ' ', $u->customdepartment);
            $printarray[] = str_replace(', ', ' ', $u->customlocation);
            $printarray[] = str_replace(', ', ' ', $u->course);
            $printarray[] = str_replace(', ', ' ', $u->code);
            $printarray[] = str_replace(', ', ' ', date('d/m/Y', $u->compdate));
            $printarray[] = str_replace(', ', ' ', $cpdpoints);
            $printarray[] = str_replace(', ', ' ', get_string('gradecompleted', 'report_san_commdeptcomp'));

            $line = implode(', ', $printarray);
            echo $line;
            echo "\n";
        }

    } else {

        $printarray[] = str_replace(', ', ' ', 'No Results');
        $line = implode(',', $printarray);
        echo $line;
        echo "\n";

    }
}

/**  
 * Returns a table of relevant information
 *
 * @param $datefrom, $dateto, $idnumber, $surname, $jobtype, $department, $internal, $courses
 * @return html_table
 */
function san_commdeptcomp_previewtable($datefrom, $dateto, $idnumber, $surname, $jobtype, $department, $internal, $courses) {
    global $CFG, $DB, $USER;

    $newcustomshortname = get_config('report_san_commdeptcomp');

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "cc.id", "c.id"));
    $student .=" SELECT DISTINCT $studentcourse AS usercourseid, u.id AS userid, u.idnumber AS idnumber, 
            u.firstname AS firstname,  u.lastname AS lastname,  u.institution AS institution, 
            u.department AS department, u.email, dept.data AS customdepartment, jobt.data AS customjobtype,
            loca.data AS customlocation, c.id AS courseid, c.id as courseid, cc.timecompleted AS compdate,
            c.fullname AS course,  c.idnumber AS code, gg.finalgrade 
        FROM ({user} u
        INNER JOIN {course_completions} cc ON u.id = cc.userid)
        INNER JOIN {course} c ON cc.course = c.id
        INNER JOIN {user_info_data} id on id.userid = u.id 
        LEFT JOIN (SELECT id, courseid
                   FROM {grade_items} gi
                   WHERE gi.itemtype = 'course') as a on a.courseid = c.id
        LEFT JOIN {grade_grades} gg on a.id = gg.itemid and gg.userid = cc.userid
        INNER JOIN (SELECT u.id , dep.data 
                        FROM {user} u
                        LEFT JOIN 
                       (SELECT uid.userid, uid.data 
                       FROM {user_info_data} uid 
                       INNER JOIN {user_info_field} uif on uif.id = uid.fieldid
                       WHERE uif.shortname = ?) dep ON dep.userid = u.id) dept ON u.id = dept.id
            INNER JOIN (SELECT u.id , job.data 
                        FROM {user} u
                        LEFT JOIN (SELECT uid.userid, uid.data 
                        FROM {user_info_data} uid 
                        INNER JOIN {user_info_field} uif on uif.id = uid.fieldid
                        WHERE uif.shortname = ?) job ON job.userid = u.id) jobt ON u.id = jobt.id
            INNER JOIN (SELECT u.id , loc.data 
                        FROM {user} u
                        LEFT JOIN (SELECT uid.userid, uid.data 
                                    FROM {user_info_data} uid 
                                    INNER JOIN {user_info_field} uif on uif.id = uid.fieldid
                                    WHERE uif.shortname = ?) loc 
                                    ON loc.userid = u.id) loca ON u.id = loca.id
        WHERE cc.timecompleted > ? AND cc.timecompleted < ?
        AND gg.finalgrade = 100.00000
        AND u.idnumber LIKE '%".$idnumber."%' AND u.lastname LIKE '%".$surname."%' ";

    // Sort out search parameters.
    if ($courses != 0 ) { // If all courses not chosen.
        $student .=" AND c.id = $courses ";
    }
    if ($internal != '0') {
        $student .= " AND loca.data = '".$internal."'";
    }
    if ($department!= '0') {
        $student .= " AND dept.data = '".$department."'";
    }
    if ($jobtype != '0') {
        $student .= " AND jobt.data = '".$jobtype."'";
    }

    $student .= " ORDER BY 5, 4, 14";

    // Run student sql.
    $studentquery = $DB->get_records_sql($student, array($newcustomshortname->report_san_commdeptcomp_department,
                                                        $newcustomshortname->report_san_commdeptcomp_employment,
                                                        $newcustomshortname->report_san_commdeptcomp_internal,
                                                        $datefrom,
                                                        $dateto ));

    $normaldatefrom = date('d/m/Y', $datefrom);
    $normaldateto = date('d/m/Y', $dateto);

    // Create the table headings.
    $table = new html_table();
    $table->width = '60%';
    $table->tablealign = 'left';
    $table->cellpadding = '1px';
    $table->head =array(get_string('reportdatefrom', 'report_san_commdeptcomp').$normaldatefrom.get_string('reportto', 'report_san_commdeptcomp').$normaldateto);
    $table->headspan = array(14,14);

    // Set the row heading.
    $row = new html_table_row();

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('staffid', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('firstname', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('surname', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('email', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('employment', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('positiontitle', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('department', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('internalcompany', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursefullname', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursecode', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('completiondate', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('cpdpoints', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('grade', 'report_san_commdeptcomp');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Cycle through SQL results filling in the html_table data.
    $table->data[] = $row;

    if (empty($studentquery)) {

        // Set the row heading.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('norecords', 'report_san_commdeptcomp');
        $cell->colspan = 14;
        $row->cells[] = $cell;
        $cell->style = "text-align:left; font-size:16px;";
        $table->data[] = $row;

        return html_writer::table($table); // Return table.
    } else {
        foreach ($studentquery as $c => $v) {

            $compdate = null;
            $cpdpoints = null;

            // Set the row heading object.
            $row = new html_table_row();

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->idnumber;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->firstname;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->lastname;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->email;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->customjobtype;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Position Title custom field id.
            $sql = "SELECT data FROM {user_info_field} i
                    INNER JOIN {user_info_data} id on id.fieldid =i.id
                    WHERE i.shortname = ? AND id.userid = ?";
            $position =$DB->get_field_sql($sql, array($newcustomshortname->report_san_commdeptcomp_position, $v->userid));
            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $position;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->customdepartment;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->customlocation;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->course;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->code;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            $certificatesql =" SELECT c.printseal FROM {certificate} c INNER JOIN {certificate_issues} ci ON
                                ci.certificateid = c.id WHERE ci.userid = ? AND c.course = ?";
            $certificate = $DB->get_records_sql($certificatesql, array($v->userid, $v->courseid));

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
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $cpdpoints;
            $cell->style = 'text-align:left; width:33%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = get_string('gradecompleted', 'report_san_commdeptcomp');
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            $table->data[] = $row;
        }
    }
    return html_writer::table($table); // Return table.
}