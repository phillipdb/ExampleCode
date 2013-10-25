<?php
/**
 * CGHS Cost Centre Report
 *
 * Library page
 *
 * @package    Report
 * @subpackage costcentre
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Displays a table of the cost centres plus a delete link.
 * Accessed from costcentre_index_form.php.
 * @param none
 * @return html_table
 */
function costcentre_costcentretable() {
    global $DB, $CFG, $OUTPUT;

    // This lib function is for the costcentre form.
    // Create the table headings.
    $table = new html_table();
    $table->width = '80%';
    $table->tablealign = 'left';
    $table->head =array(get_string('costcentre_costcentreheading', 'report_costcentre'), get_string('costcentre_actionheading', 'report_costcentre'));
    $table->headspan = array(1,1);

    // Set the row heading.
    $row = new html_table_row();

    $table->data[] = $row;

    $dbtable = 'cghs_cost_centre';
    $conditions = array();
    $costcentre = $DB->get_records_menu($dbtable, $conditions, 'name');

    if (empty($costcentre)) {
        // Set the row heading object.
        $row = new html_table_row();
        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('costcentre_norecords', 'report_costcentre');
        $cell->colspan = '2';
        $cell->style = 'text-align:right; fontsize:16px;';
        $row->cells[] = $cell;
        $table->data[] = $row;
    } else {
        foreach ($costcentre as $c => $v) {  // Fill in table.
            // Set the row heading object.
            $row = new html_table_row();

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v;
            $cell->style = 'text-align: left; width:90%; fontsize:16px;';
            $row->cells[] = $cell;

            $deletelink = "<a href='".$CFG->wwwroot."/report/costcentre/index.php?formid=costcentre&ccid=$c&action=1'>Delete</a>";
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $deletelink;
            $cell->style = 'text-align: left; width:10%; fontsize:16px;';
            $row->cells[] = $cell;

            $table->data[] = $row;
        }
    }
    return html_writer::table($table);
}

/**
 * This function is for the CSV exporting for the cghs_courses form.
 * @param $centre, $course, $datefrom, $dateto
 * @return downloaded csv file.
 */
function costcentre_export_grades($centre, $course, $datefrom, $dateto) {
    global $CFG, $DB;

        $primcc = get_string('courses_costcentrefieldname', 'report_costcentre');
        $seccc = get_string('courses_secondarycostcentrefieldname', 'report_costcentre');

        $staffcat = $DB->sql_concat_join("' '", array("ue.courseid", "u.id"));
        $sql = " SELECT x.id, x.uid, x.firstname, x.lastname, x.primarycc, x.secondarycc, x.courseid, x.fullname, 
                    MAX(x.timemodified) as timemodified
                FROM
                (SELECT $staffcat as id , u.id as uid, u.firstname, u.lastname, 
                        pri.data AS primarycc,
                        sec.data as secondarycc, ue.courseid AS courseid, ue.fullname, 0 as timemodified
                    FROM {user} u
                    INNER JOIN (SELECT c1.id as courseid, ue1.userid, c1.fullname, c1.visible
                                FROM {enrol} e1
                                JOIN {user_enrolments} ue1 ON ue1.enrolid = e1.id
                                JOIN {course} c1 on c1.id = e1.courseid
                                GROUP BY 1, 2) ue ON u.id = ue.userid
                    LEFT JOIN {user_info_data} pri ON u.id = pri.userid AND pri.fieldid = 
                        (SELECT id FROM {user_info_field} WHERE shortname = ?) 
                    LEFT JOIN {user_info_data} sec ON u.id = sec.userid AND sec.fieldid = 
                        (SELECT id FROM {user_info_field} WHERE shortname = ?)
               
                    WHERE (pri.data = ? OR sec.data = ?)
                    AND ue.visible = 1 AND u.suspended = 0 AND u.deleted = 0
                UNION 
                SELECT $staffcat as id , u.id as uid, u.firstname, u.lastname, pri.data AS primarycc,
                        sec.data as secondarycc, ue.courseid AS courseid, ue.fullname, fbc.timemodified
                        FROM {user} u
                        INNER JOIN (SELECT c1.id as courseid, ue1.userid, c1.fullname, c1.visible
                                    FROM {enrol} e1
                                    JOIN {user_enrolments} ue1 ON ue1.enrolid = e1.id
                                    JOIN {course} c1 on c1.id = e1.courseid
                                    GROUP BY 1, 2) ue ON u.id = ue.userid
                        LEFT JOIN {user_info_data} pri ON u.id = pri.userid AND pri.fieldid = 
                            (SELECT id FROM {user_info_field} WHERE shortname = ?) 
                        LEFT JOIN {user_info_data} sec ON u.id = sec.userid AND sec.fieldid = 
                            (SELECT id FROM {user_info_field} WHERE shortname = ?)
                        LEFT JOIN (SELECT fc.userid, fb.course, fc.timemodified
                                    FROM {feedback_completed} fc
                                    INNER JOIN {feedback} fb ON fb.id = fc.feedback) AS fbc ON fbc.userid = u.id AND fbc.course = ue.courseid 
                        WHERE (pri.data = ? OR sec.data = ?)
                        AND ue.visible = 1 AND u.suspended = 0 AND u.deleted = 0
            ORDER BY  4,3,7 ) as x   ";

        if ($course != '0') {
            $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ? ) OR (x.timemodified < 1) )
                    AND x.courseid = ? ";
            $sql .="GROUP BY 4,3,7,2 ORDER BY  4,3,2  ";
            $users = $DB->get_records_sql($sql, array($primcc,
                                                        $seccc,
                                                        $centre, $centre,
                                                        $primcc,
                                                        $seccc,
                                                        $centre, $centre,
                                                        $datefrom, $dateto, $course));
        } else {
            $sql .=" WHERE (x.timemodified > ? AND x.timemodified < ?) OR (x.timemodified < 1) ";
            $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2  ";
            $users = $DB->get_records_sql($sql, array($primcc,
                                                        $seccc,
                                                        $centre, $centre,
                                                        $primcc,
                                                        $seccc,
                                                        $centre, $centre, $datefrom, $dateto));
        }

    // CSV file creation and Data Export to CSV File.
    @header('Content-Disposition: attachment; filename='.'csvexport_'.date("Ymd").'.csv');
    @header('Content-Type: text/csv');

    $csvhead = array(get_string('courses_pcostcentre', 'report_costcentre'));
    $csvhead[] = get_string('courses_scostcentre', 'report_costcentre');
    $csvhead[] = get_string('courses_lastname', 'report_costcentre');
    $csvhead[] = get_string('courses_firstname', 'report_costcentre');
    $csvhead[] = get_string('courses_course', 'report_costcentre');
    $csvhead[] = get_string('courses_status', 'report_costcentre');
    $csvhead[] = get_string('courses_completiondate', 'report_costcentre');
    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";
    if ($users) {

        // Looping through query output to write into CSV file.
        foreach ($users as $u) {
            // Added to change the date format.
            $printarray = array(str_replace(',', ' ', $u->primarycc));
            $printarray[] = str_replace(',', ' ', $u->secondarycc);
            $printarray[] = str_replace(',', ' ', $u->lastname);
            $printarray[] = str_replace(',', ' ', $u->firstname);
            $printarray[] = str_replace(',', ' ', $u->fullname);

            if ($u->timemodified == '0') {
                $status = get_string('courses_notcompleted', 'report_costcentre');
                $compdate = get_string('courses_notcompleted', 'report_costcentre');
            } else {
                $status = get_string('courses_completed', 'report_costcentre');
                $compdate = date('d/m/Y', $u->timemodified);
            }

            $printarray[] = str_replace(',', ' ', $status);
            $printarray[] = str_replace(',', ' ', $compdate);
            $line = implode(',', $printarray);
            echo $line;
            echo "\n";
        }

    } else {
        $printarray = array(str_replace(',', ' ', get_string('courses_norecords', 'report_costcentre')));
        $line = implode(',', $printarray);
        echo $line;
        echo "\n";
    }

    // Kill the process once export is completed.
    exit;
}