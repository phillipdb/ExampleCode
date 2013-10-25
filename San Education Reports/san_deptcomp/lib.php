<?php
/**
 * SAN Education Departmental Completion Status Report
 *
 * Library Functions
 *
 * @package    report
 * @subpackage san_deptcomp
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 SAN Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This function creates a .csv file from the from elements. File automatically downloads.
 * @param $dateto, $datefrom, $idnumber, $surname, $jobtype, $department, $internal, $courses
 * @return Downloaded .csv file.
 **/
function san_deptcomp_export_grades($dateto, $datefrom, $idnumber, $surname, $jobtype, $department, $internal, $courses) {
    global $CFG, $DB;

    $newcustomshortname = get_config('report_san_deptcomp');

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "cc.id", "c.id"));
    $sql .=" SELECT DISTINCT $studentcourse AS usercourseid, u.id AS userid, u.idnumber AS idnumber, 
                    u.firstname AS firstname,  u.lastname AS lastname,  u.institution AS institution, 
                    u.department AS department, u.email, id.data AS data, c.id AS courseid, 
                    cc.timecompleted AS compdate,  c.fullname AS course,  c.idnumber AS code, gg.finalgrade 
                FROM ({user} u
                INNER JOIN {course_completions} cc ON u.id = cc.userid)
                INNER JOIN {course} c ON cc.course = c.id
                INNER JOIN {user_info_data} id on id.userid = u.id
                INNER JOIN {user_info_field} uif on id.fieldid = uif.id
                LEFT JOIN (SELECT id, courseid
                           FROM {grade_items} gi
                           WHERE gi.itemtype = 'course') as a on a.courseid = c.id
                LEFT JOIN {grade_grades} gg on a.id = gg.itemid and gg.userid = cc.userid
                WHERE cc.timecompleted > ? AND cc.timecompleted < ?
                AND u.idnumber LIKE '%".$idnumber."%' AND u.lastname LIKE '%".$surname."%' 
                AND uif.shortname = ? 
                AND gg.finalgrade = 100.00000";

    if ($courses != 0 ) { // If all courses not chosen.
        $sql .=" AND c.id = $courses ";
    }
    if ($internal != '0') {
        $sql .=" AND u.institution = '".$internal."'";
    }
    if ($department != '0') {
        $sql .=" AND u.department = '".$department."'";
    }
    if ($jobtype != '0') { // Job type chosen.
        $student .=" AND id.data ='".$jobtype."'";
    }
    $sql .= " ORDER BY 5, 4, 12";

    $users = $DB->get_records_sql($sql, array($datefrom, $dateto, $newcustomshortname->report_san_deptcomp_jobtype));

    // CSV file creation and Data Export to CSV File.
    $filename = 'csvexport_'.date("Ymd").'.csv';
    @header('Content-Disposition: attachment; filename='.$filename);
    @header('Content-Type: text/csv');
    $csvhead = array(get_string('staffnumber', 'report_san_deptcomp'));
    $csvhead[] = get_string('firstname', 'report_san_deptcomp');
    $csvhead[] = get_string('surname', 'report_san_deptcomp');
    $csvhead[] = get_string('email', 'report_san_deptcomp');
    $csvhead[] = get_string('jobtype', 'report_san_deptcomp');
    $csvhead[] = get_string('institution', 'report_san_deptcomp');
    $csvhead[] = get_string('department', 'report_san_deptcomp');
    $csvhead[] = get_string('coursefullname', 'report_san_deptcomp');
    $csvhead[] = get_string('coursecode', 'report_san_deptcomp');
    $csvhead[] = get_string('completiondate', 'report_san_deptcomp');
    $csvhead[] = get_string('cpdpoints', 'report_san_deptcomp');
    $csvhead[] = get_string('grade', 'report_san_deptcomp');

    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";

    // Looping through query output to write into CSV file.
    foreach ($users as $u) {
        $compdate = null;
        $cpdpoints = null;
        $certificatesql =" SELECT c.printseal FROM {certificate} c INNER JOIN {certificate_issues} ci ON 
                            ci.certificateid = c.id WHERE ci.userid = ? AND c.course = ?";
        $certificate = $DB->get_records_sql($certificatesql, array($u->userid, $u->courseid));

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

        $printarray = array(str_replace(',', ' ', $u->idnumber));
        $printarray[] = str_replace(',', ' ', $u->firstname);
        $printarray[] = str_replace(',', ' ', $u->lastname);
        $printarray[] = str_replace(',', ' ', $u->email);
        $printarray[] = str_replace(',', ' ', $u->data);
        $printarray[] = str_replace(',', ' ', $u->institution);
        $printarray[] = str_replace(',', ' ', $u->department);
        $printarray[] = str_replace(',', ' ', $u->course);
        $printarray[] = str_replace(',', ' ', $u->code);
        $printarray[] = str_replace(',', ' ', date('d/m/Y', $u->compdate));
        $printarray[] = str_replace(',', ' ', $cpdpoints);
        $printarray[] = str_replace(',', ' ', get_string('gradecompleted', 'report_san_deptcomp'));

        $line = implode(',', $printarray);
        echo $line;
        echo "\n";
    }
}

/**  
 * Returns a table of relevant information
 *
 * @param $datefrom, $dateto, $idnumber, $surname, $jobtype, $department, $internal, $courses
 * @return html_writer table
 */
function san_deptcomp_previewtable($datefrom, $dateto, $idnumber, $surname, $jobtype, $department, $internal, $courses) {
    global $CFG, $DB;

    $newcustomshortname = get_config('report_san_deptcomp');

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "cc.id", "c.id"));
    $student =" SELECT DISTINCT $studentcourse AS usercourseid, u.id AS userid, u.idnumber AS idnumber, 
                    u.firstname AS firstname,  u.lastname AS lastname,  u.institution AS institution, 
                    u.department AS department, u.email, id.data AS data, c.id AS courseid, 
                    cc.timecompleted AS compdate,  c.fullname AS course,  c.idnumber AS code, gg.finalgrade 
                FROM ({user} u
                INNER JOIN {course_completions} cc ON u.id = cc.userid)
                INNER JOIN {course} c ON cc.course = c.id
                INNER JOIN {user_info_data} id on id.userid = u.id
                INNER JOIN {user_info_field} uif on id.fieldid = uif.id
                LEFT JOIN (SELECT id, courseid
                           FROM {grade_items} gi
                           WHERE gi.itemtype = 'course') as a on a.courseid = c.id
                LEFT JOIN {grade_grades} gg on a.id = gg.itemid and gg.userid = cc.userid
                WHERE cc.timecompleted > ? AND cc.timecompleted < ?
                AND u.idnumber LIKE '%".$idnumber."%' AND u.lastname LIKE '%".$surname."%' 
                AND uif.shortname = ? 
                AND gg.finalgrade = 100.00000";
    if ($courses != 0 ) { // If all courses not chosen.
        $student .=" AND c.id = $courses ";
    }
    if ($internal != '0') {
        $student .=" AND u.institution = '".$internal."'";
    }
    if ($department != '0') {
        $student .=" AND u.department = '".$department."'";
    }
    if ($jobtype != '0') { // Job type chosen.
        $student .=" AND id.data ='".$jobtype."'";
    }

    $student .=" ORDER BY 5, 4, 12";

    // Run student sql.
    $studentquery = $DB->get_records_sql($student, array($datefrom, $dateto, $newcustomshortname->report_san_deptcomp_jobtype));

    $normaldatefrom = date('d/m/Y', $datefrom);
    $normaldateto = date('d/m/Y', $dateto);

    // Create the table headings.
    $table = new html_table();
    $table->width = '70%';
    $table->tablealign = 'left';
    $table->cellpadding = '1px';
    $table->head =array(get_string('reportdatefrom', 'report_san_deptcomp') .$normaldatefrom .get_string('dateto', 'report_san_deptcomp'). $normaldateto);
    $table->headspan = array(12,12);

    // Set the row heading.
    $row = new html_table_row();

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('staffnumber', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('firstname', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('surname', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('email', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('jobtype', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('institution', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('department', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursefullname', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursecode', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('completiondate', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('cpdpoints', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('grade', 'report_san_deptcomp');
    $cell->style = 'width:40%; text-align:left; font-size:18px;';
    $row->cells[] = $cell;

    // Cycle through SQL results filling in the html_table data.
    $table->data[] = $row;

    if (empty($studentquery)) {

        // Set the row heading.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('norecordsfound', 'report_san_deptcomp');
        $cell->colspan = 12;
        $cell->style = "text-align:left; font-size:18px;";
        $row->cells[] = $cell;

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
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->firstname;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->lastname;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->email;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->data;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->institution;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->department;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->course;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->code;
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            $certificatesql =" SELECT c.printseal FROM {certificate} c INNER JOIN {certificate_issues} ci ON
                                ci.certificateid = c.id WHERE ci.userid = ? AND c.course = ?";
            $certificate = $DB->get_records_sql($certificatesql, array($v->userid, $v->courseid), null);

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

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = date('d/m/Y', $v->compdate);
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $cpdpoints;
            $cell->style = 'width:33%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = get_string('gradecompleted', 'report_san_deptcomp');
            $cell->style = 'width:40%; text-align:left; font-size:16px;';
            $row->cells[] = $cell;

            $table->data[] = $row;
        }
    }
    return html_writer::table($table); // Return table.
}
