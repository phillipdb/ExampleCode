<?php
/**
 * CGHS FEED BACK REPORT
 *
 * Library functions.
 *
 * @package    Report
 * @subpackage feedback
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 CGHS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../config.php');

/**
 * This function creates an exported Excel spreadsheet based on the parameters passed from the form.
 * @param $staff, $course, $centre, $datefrom, $dateto.
 * @return Excel spreadsheet.
 */
function feedback_export_grades($staff, $course, $centre, $datefrom, $dateto, $onlyuser) {

    global $CFG, $DB, $USER;
    require_once($CFG->dirroot.'/lib/excellib.class.php');
    require_once($CFG->libdir.'/adminlib.php');

    $feedbacksettings = get_config('report_feedback');
    $profilefield = $feedbacksettings->report_feedback_primary;
    $secondaryprofilefield = $feedbacksettings->report_feedback_secondary;
    // Cost centre name passed through as a name so no need to try and get it.
    if ($staff == '0') { // If all students chosen.

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
            if ($onlyuser == '1') {
                $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ? AND x.uid = $USER->id ) OR 
                (x.timemodified < 1 AND x.uid = $USER->id)) AND x.courseid = ? AND x.uid = $USER->id ";
            } else {
                $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ? ) OR (x.timemodified < 1)) AND x.courseid = ? ";
            }
            $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2 ";
            $results = $DB->get_records_sql($sql, array($profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre,
                                                        $profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre,
                                                        $datefrom, $dateto, $course));
        } else {
            if ($onlyuser == '1') {
                $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ?) OR (x.timemodified < 1)) 
                AND x.uid = $USER->id ";
            } else {
                $sql .=" WHERE (x.timemodified > ? AND x.timemodified < ?) OR (x.timemodified < 1) ";
            }
            
            $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2  ";
            $results = $DB->get_records_sql($sql, array($profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre,
                                                        $profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre, $datefrom, $dateto));
        }

        // Calculate file name.
        $date = usergetdate(time());
        $date = $date[mday]."-".$date[mon]."-".$date[year];
        $name = $date.get_string('filename', 'report_feedback');
        $downloadfilename = clean_filename($name);
        $downloadfilename .= '.xls';

        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($downloadfilename);        
        // Adding the worksheet.
        $myxls =& $workbook->add_worksheet($worksheetname);
        $myxls->set_column(0,0,20,null,null,null);
        $myxls->set_column(0,1,20,null,null,null);
        $myxls->set_column(0,2,20,null,null,null);
        $myxls->set_column(0,3,20,null,null,null);
        $myxls->set_column(0,4,20,null,null,null);
        $myxls->set_column(0,5,15,null,null,null);
        $myxls->set_column(0,6,15,null,null,null);

        // Print names of all the fields.
        $myxls->write_string(0, 0, get_string('export_pcostcentre', 'report_feedback'));
        $myxls->write_string(0, 1, get_string('export_scostcentre', 'report_feedback'));
        $myxls->write_string(0, 2, get_string('export_lastname', 'report_feedback'));
        $myxls->write_string(0, 3, get_string('export_firstname', 'report_feedback'));
        $myxls->write_string(0, 4, get_string('export_course', 'report_feedback'));
        $myxls->write_string(0, 5, get_string('export_feedbackstatus', 'report_feedback'));
        $myxls->write_string(0, 6, get_string('export_feedbackdate', 'report_feedback'));
        if ($results) {
            foreach($results as $k => $v) {
                $i++;
                if ($v->timemodified == '0') {
                    $feedbackstatus = get_string('notcompleted', 'report_feedback');
                    $feedbackdate = get_string('notcompleted', 'report_feedback');
                } else {
                    $feedbackstatus = get_string('completed', 'report_feedback');
                    $feedbackdate = date('d/m/Y', $v->timemodified);
                }
                $myxls->write_string($i, 0, $v->primarycc);
                $myxls->write_string($i, 1, $v->secondarycc);
                $myxls->write_string($i, 2, $v->lastname);
                $myxls->write_string($i, 3, $v->firstname);
                $myxls->write_string($i, 4, $v->fullname);
                $myxls->write_string($i, 5, $feedbackstatus);
                $myxls->write_string($i, 6, $feedbackdate);
            }
        } else {
            $myxls->write_string(0, 0, get_string('noresults', 'report_feedback'));
        }
        // Close the workbook
        $workbook->close();
        exit;

    } else { // Student is submitting.

        if ($staff == 'logged') {
            $staff = $USER->id;
        }
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
                    AND ue.visible = 1 AND u.suspended = 0 AND u.deleted = 0
        ORDER BY  4,3,7 ) as x WHERE x.uid = ?";

    $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2 ";
    $results = $DB->get_records_sql($sql, array($profilefield,
                                                $secondaryprofilefield,
                                                $profilefield,
                                                $secondaryprofilefield,
                                                $staff));

        // Calculate file name.
        $date = usergetdate(time());
        $date = $date[mday]."-".$date[mon]."-".$date[year];
        $name = $date.get_string('filename', 'report_feedback');
        $downloadfilename = clean_filename($name);
        $downloadfilename .= '.xls';
        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($downloadfilename);        
        // Adding the worksheet.
        $myxls =& $workbook->add_worksheet($worksheetname);
        $myxls->hide_gridlines();
        $myxls->hide_screen_gridlines();
        $myxls->set_column(0,0,30,null,null,null);
        $myxls->set_column(0,1,30,null,null,null);
        $myxls->set_column(0,2,30,null,null,null);
        $myxls->set_row(0,225,null,null,null);

        if ($results) {
            $title = random_string();
            $i= 6;
            foreach($results as $k => $v) {
                $i++;
                if ($v->timemodified == null || $v->timemodified == '0') {
                    $feedbackstatus = get_string('notcompleted', 'report_feedback');
                    $feedbackdate = get_string('notcompleted', 'report_feedback');
                } else {
                    $feedbackstatus = get_string('completed', 'report_feedback');
                    $feedbackdate = date('d/m/Y', $v->timemodified);
                }
                if ($title != $v->firstname) {
                    $myxls->insert_bitmap(0, 0, 'cghs.bmp', 1, 1, 1, 1);
                    $myxls->write_string(1, 0, get_string('pluginname', 'report_feedback'));
                    $myxls->write_string(2, 0, get_string('staffmember', 'report_feedback'));
                    $myxls->write_string(2, 1, $v->firstname." ".$v->lastname);
                    $myxls->write_string(3, 0, get_string('export_pcostcentre', 'report_feedback'));
                    $myxls->write_string(3, 1, $v->primarycc);
                    $myxls->write_string(4, 0, get_string('export_scostcentre', 'report_feedback'));
                    $myxls->write_string(4, 1, $v->secondarycc);

                    $myxls->write_string(6, 0, get_string('export_course', 'report_feedback'));
                    $myxls->write_string(6, 1, get_string('export_feedbackstatus', 'report_feedback'));
                    $myxls->write_string(6, 2, get_string('export_feedbackdate', 'report_feedback'));
                    $title = $v->firstname;
                }


                $myxls->write_string($i, 0, $v->fullname);
                $myxls->write_string($i, 1, $feedbackstatus);
                $myxls->write_string($i, 2, $feedbackdate);
            }
        } else {
             $myxls->write_string(0, 0, get_string('noresults', 'report_feedback'));
        }
        // Close the workbook
        $workbook->close();
        exit;

    }

}

/**
 * Returns a table of relevant Organisation Units
 * @param $staff, $course, $centre, $datefrom, $dateto
 * @return html_writer table
 */
function feedback_completiontable($staff, $course, $centre, $datefrom, $dateto, $onlyuser) {
    global $CFG, $DB, $USER;

    $feedbacksettings = get_config('report_feedback');
    $profilefield = $feedbacksettings->report_feedback_primary;
    $secondaryprofilefield = $feedbacksettings->report_feedback_secondary;

    if ($staff == '0') { // Manager submitting.

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
            if ($onlyuser == '1') {
                $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ? AND x.uid = $USER->id ) OR 
                (x.timemodified < 1 AND x.uid = $USER->id)) AND x.courseid = ? AND x.uid = $USER->id ";
            } else {
                $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ? ) OR (x.timemodified < 1)) AND x.courseid = ? ";
            }
            $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2 ";
            $results = $DB->get_records_sql($sql, array($profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre,
                                                        $profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre,
                                                        $datefrom, $dateto, $course));
        } else {
            if ($onlyuser == '1') {
                $sql .=" WHERE ((x.timemodified > ? AND x.timemodified < ?) OR (x.timemodified < 1)) 
                AND x.uid = $USER->id ";
            } else {
                $sql .=" WHERE (x.timemodified > ? AND x.timemodified < ?) OR (x.timemodified < 1) ";
            }
            
            $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2  ";
            $results = $DB->get_records_sql($sql, array($profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre,
                                                        $profilefield,
                                                        $secondaryprofilefield,
                                                        $centre, $centre, $datefrom, $dateto));
        }

    } else { // Student submitted.

        if ($staff == 'logged') {
            $staff = $USER->id;
        }

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
                        AND ue.visible = 1 AND u.suspended = 0 AND u.deleted = 0
            ORDER BY  4,3,7 ) as x WHERE x.uid = ?";

            $sql .=" GROUP BY 4,3,7,2 ORDER BY  4,3,2 ";
            $results = $DB->get_records_sql($sql, array($profilefield,
                                                        $secondaryprofilefield,
                                                        $profilefield,
                                                        $secondaryprofilefield,
                                                        $staff));
    }

    // Create the table headings.
    $table = new html_table();
    $table->width = '100%';
    $table->tablealign = 'center';
    $table->cellpadding = '1px';

    // Set the row heading.
    // Create the report heading.
    $row = new html_table_row();
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('reporttitle','report_feedback') ."<br>";
    $cell->style = 'width:100%; text-align:left; font-size:20px; color:#3c6899';
    $cell->colspan = 3;
    $row->cells[] = $cell;
    $table->data[] = $row; // End of report heading.  
    
    if (empty($results)) {

        // Set the row heading.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('norecords','report_feedback');
        $cell->colspan = 3;
        $cell->style = "text-align:left; color: #3c6899; font-size:16px";
        $row->cells[] = $cell;

        $table->data[] = $row;

       return html_writer::table($table); // Return table.
    } else {
        $userid = "";
        foreach ($results as $c => $v) {
            if ($v->uid != $userid) { // If different user result make new heading.

                $userid = $v->uid;

                // Create the report heading.
                $row = new html_table_row();
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text .= get_string('staffmember','report_feedback').$v->firstname." ".$v->lastname."<br>";
                $cell->text .= get_string('primarycc','report_feedback').$v->primarycc."<br>";
                $cell->text .= get_string('secondarycc','report_feedback').$v->secondarycc." ";
                $cell->colspan = 3;
                $cell->style = "text-align:left; color: #3c6899; font-size:16px";
                $row->cells[] = $cell;
                $table->data[] = $row; // End of report heading.  
            

                // Set the row heading.
                $row = new html_table_row();
                // Create the student cell heading
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = get_string('export_course','report_feedback');
                $cell->style = "text-align:left; font-size:14px";
                $row->cells[] = $cell;

                // Create the student cell heading
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = get_string('export_feedbackstatus','report_feedback');
                $cell->style = "text-align:left; font-size:14px";
                $row->cells[] = $cell;

                // Create the student cell heading
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = get_string('export_feedbackdate','report_feedback');
                $cell->style = "text-align:left; font-size:14px";
                $row->cells[] = $cell;

                // Cycle through SQL results filling in the html_table data.
                $table->data[] = $row;
            }

            // Create the row.
            $row = new html_table_row(); 
            // Set course cell. 
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->fullname;
            $cell->style = "text-align:left; font-size:14px";
            $row->cells[] = $cell;            

            // Set the feedback status cell.  
            $cell = new html_table_cell();
            $cell->header = true;
            if ($v->timemodified == '0') { // If not completed it will be null.
                $cell->text = get_string('notcompleted','report_feedback');
            } else {
                $cell->text = get_string('completed','report_feedback');
            }
            $cell->style = 'text-align:left; width:40%';
            $row->cells[] = $cell;

            // Set the feedback time cell.
            $cell = new html_table_cell();
            $cell->header = true;
            if ($v->timemodified == '0' || $v->timemodified == null ) {
                $cell->text = get_string('notcompleted','report_feedback');
            } else {
                $timemodified = date("d/m/Y",$v->timemodified);
                $cell->text = $timemodified;
            }
            $cell->style = 'text-align:left; width:40%';
            $row->cells[] = $cell;
            $table->data[] = $row;
        }
    }
    return html_writer::table($table); // Return table.
}

/**
 * This fucntions calculates an array of costcentres depending on the roles of the logged in user.
 * @param none
 * @return array
 */
function feedback_managerarray() {
    global $DB, $USER;
    // Fill in the cost centre select box.

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
    return $centres;
}