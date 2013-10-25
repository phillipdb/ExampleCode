<?php
/**
 * API User Report
 *
 * Library functions
 *
 * @package    report
 * @subpackage api_user
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**  
 * Exports results to a .CSV file.
 *
 * @param $datefrom, $dateto, $course.
 * @return download .CVS file
 */
function api_user_export($datefrom, $dateto, $course) {
    global $CFG, $DB;

    $newcustomshortname = get_config('report_api_user');

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "c.id"));
    $sql =" SELECT  max(s.BigID) AS id,
                    max(s.userid) AS userid,
                    s.firstname AS firstname,
                    s.lastname AS lastname,
                    s.courseid AS courseid,
                    s.fullname AS fullname,
                    max(mintimecreated) as timecreated
            FROM (  SELECT A.*
                    FROM (  SELECT  $studentcourse as BigID,
                                    u.id as userid,
                                    u.firstname,
                                    u.lastname,
                                    c.id as courseid,
                                    c.fullname,
                                    min(ue.timecreated) as mintimecreated
                            FROM    {course} c
                            JOIN    {enrol} e on e.courseid = c.id
                            JOIN    {user_enrolments} ue on ue.enrolid = e.id
                            JOIN    {user} u on u.id = ue.userid
                            WHERE c.visible = '1'
                            GROUP BY 1, 2, 3, 4, 5, 6
                    ) as A
            WHERE A.mintimecreated >  ? AND A.mintimecreated < ?

            UNION ALL

            SELECT  CONCAT(f.uuid, g.cid) as BigID,
                    f.uuid,
                    f.Fname,
                    f.Lname,
                    g.cid ,
                    g.Cname,
                    0 as mintime
            FROM (  SELECT  b.firstname as Fname,
                            b.lastname as Lname,
                            b.userid as uuID
                    FROM (  SELECT  $studentcourse as BigID,
                                    u.id as userid,
                                    u.firstname,
                                    u.lastname,
                                    c.id as courseid,
                                    c.fullname,
                                    min(ue.timecreated) as mintimecreated
                            FROM    {course} c
                            JOIN    {enrol} e on e.courseid = c.id
                            JOIN    {user_enrolments} ue on ue.enrolid = e.id
                            JOIN    {user} u on u.id = ue.userid
                            WHERE c.visible = '1'
                            GROUP BY 1, 2, 3, 4, 5, 6
                    ) as b
                    WHERE b.mintimecreated >  ? AND b.mintimecreated < ?
                    GROUP BY 1,2,3
            ) as f
            ,
            (   SELECT  d.fullname as Cname,
                        d.courseid as cID
                FROM (  SELECT  $studentcourse as BigID,
                                u.id as userid,
                                u.firstname,
                                u.lastname,
                                c.id as courseid,
                                c.fullname,
                                min(ue.timecreated) as mintimecreated
                        FROM    {course} c
                        JOIN    {enrol} e on e.courseid = c.id
                        JOIN    {user_enrolments} ue on ue.enrolid = e.id
                        JOIN    {user} u on u.id = ue.userid
                        WHERE c.visible = '1'
                        GROUP BY 1, 2, 3, 4, 5, 6
                ) as d
                WHERE d.mintimecreated >  ? AND d.mintimecreated < ?
                GROUP BY 1,2
                )as g

            ) as s
            ";

    // Sort out search parameters.
    if ($course != 0 ) { // If all courses not chosen.
        $sql .=" WHERE s.courseid = $course ";
    }

    $sql .= "GROUP BY 4,3,6,5
            ORDER BY 4,3,6,5";

    // Run student sql.
    $studentquery = $DB->get_records_sql($sql, array(   $datefrom,
                                                        $dateto,
                                                        $datefrom,
                                                        $dateto,
                                                        $datefrom,
                                                        $dateto));

    // CSV file creation and Data Export to CSV File.
    $filename = 'csvexport_'.date("Ymd").'.csv';
    @header('Content-Disposition: attachment; filename='.$filename);
    @header('Content-Type: text/csv');
    $csvhead = array(get_string('firstname', 'report_api_user'));
    $csvhead[] = get_string('lastname', 'report_api_user');
    $csvhead[] = get_string('email', 'report_api_user');
    $csvhead[] = get_string('state', 'report_api_user');
    $csvhead[] = get_string('username', 'report_api_user');
    if ($course != 0) {
        $coursename = $DB->get_field('course', 'fullname', array('id' => $course), null);
        $csvhead[] = $coursename;
    } else {
        $coursename = $DB->get_fieldset_select('course', 'fullname', 'visible = ? ORDER BY fullname ASC', array('1'));
        foreach ($coursename as $k => $v) {
            $csvhead[] = $v;
        }
    }

    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";
    if ($studentquery) {
        $userid = null;
        // Looping through query output to write into CSV file.
        foreach ($studentquery as $u => $v) {

            if ($userid != $v->userid) {

                if ($userid != null) { // Otherwise will print heading twice.
                    $line = implode(',', $printarray);
                    echo $line;
                    echo "\n";
                }
                $userid = $v->userid;

                $printarray = array(str_replace(',', ' ', $v->firstname));
                $printarray[] = str_replace(', ', ' ', $v->lastname);

                // Get the users email.
                $email = $DB->get_field('user', 'email', array('id' => $v->userid), null);
                $printarray[] = str_replace(', ', ' ', $email);

                // State custom field id.
                $sql = "SELECT data FROM {user_info_field} i
                        INNER JOIN {user_info_data} id on id.fieldid =i.id
                        WHERE i.shortname = ? AND id.userid = ?";
                $state =$DB->get_field_sql($sql, array($newcustomshortname->report_api_user_state, $v->userid));
                $printarray[] = str_replace(', ', ' ', $state);

                $username = $DB->get_field('user', 'username', array('id' => $v->userid), null);
                $printarray[] = str_replace(', ', ' ', $username);

                // Work out date constraints.
                if ($v->timecreated == '0') {
                    $timecreated = " - ";
                } else {
                    $timecreated = date('Y-m-d', $v->timecreated);
                }
                $printarray[] = str_replace(', ', ' ', $timecreated);

            } else {
                // Work out date constraints.
                if ($v->timecreated == '0') {
                    $timecreated = " - ";
                } else {
                    $timecreated = date('Y-m-d', $v->timecreated);
                }
                $printarray[] = str_replace(', ', ' ', $timecreated);
            }

        }
        $line = implode(', ', $printarray);
        echo $line;
        echo "\n";

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
 * @param $datefrom, $dateto, $course
 * @return html_table
 */
function api_user_previewtable($datefrom, $dateto, $course) {
    global $CFG, $DB, $USER;

    $newcustomshortname = get_config('report_api_user');

    $studentcourse = $DB->sql_concat_join("' '", array("u.id", "c.id"));
    $sql =" SELECT  max(s.BigID) AS id,
                    max(s.userid) AS userid,
                    s.firstname AS firstname,
                    s.lastname AS lastname,
                    s.courseid AS courseid,
                    s.fullname AS fullname,
                    max(mintimecreated) as timecreated
            FROM (  SELECT A.*
                    FROM (  SELECT  $studentcourse as BigID,
                                    u.id as userid,
                                    u.firstname,
                                    u.lastname,
                                    c.id as courseid,
                                    c.fullname,
                                    min(ue.timecreated) as mintimecreated
                            FROM    {course} c
                            JOIN    {enrol} e on e.courseid = c.id
                            JOIN    {user_enrolments} ue on ue.enrolid = e.id
                            JOIN    {user} u on u.id = ue.userid
                            WHERE c.visible = '1'
                            GROUP BY 1, 2, 3, 4, 5, 6
                    ) as A
            WHERE A.mintimecreated >  ? AND A.mintimecreated < ?

            UNION ALL

            SELECT  CONCAT(f.uuid, g.cid) as BigID,
                    f.uuid,
                    f.Fname,
                    f.Lname,
                    g.cid ,
                    g.Cname,
                    0 as mintime
            FROM (  SELECT  b.firstname as Fname,
                            b.lastname as Lname,
                            b.userid as uuID
                    FROM (  SELECT  $studentcourse as BigID,
                                    u.id as userid,
                                    u.firstname,
                                    u.lastname,
                                    c.id as courseid,
                                    c.fullname,
                                    min(ue.timecreated) as mintimecreated
                            FROM    {course} c
                            JOIN    {enrol} e on e.courseid = c.id
                            JOIN    {user_enrolments} ue on ue.enrolid = e.id
                            JOIN    {user} u on u.id = ue.userid
                            WHERE c.visible = '1'
                            GROUP BY 1, 2, 3, 4, 5, 6
                    ) as b
                    WHERE b.mintimecreated >  ? AND b.mintimecreated < ?
                    GROUP BY 1,2,3
            ) as f
            ,
            (   SELECT  d.fullname as Cname,
                        d.courseid as cID
                FROM (  SELECT  $studentcourse as BigID,
                                u.id as userid,
                                u.firstname,
                                u.lastname,
                                c.id as courseid,
                                c.fullname,
                                min(ue.timecreated) as mintimecreated
                        FROM    {course} c
                        JOIN    {enrol} e on e.courseid = c.id
                        JOIN    {user_enrolments} ue on ue.enrolid = e.id
                        JOIN    {user} u on u.id = ue.userid
                        WHERE c.visible = '1'
                        GROUP BY 1, 2, 3, 4, 5, 6
                ) as d
                WHERE d.mintimecreated >  ? AND d.mintimecreated < ?
                GROUP BY 1,2
                )as g

            ) as s
            ";

    // Sort out search parameters.
    if ($course != 0 ) { // If all courses not chosen.
        $sql .=" WHERE s.courseid = $course ";
    }

    $sql .= "GROUP BY 4,3,6,5
            ORDER BY 4,3,6,5";

    // Run student sql.
    $studentquery = $DB->get_records_sql($sql, array(   $datefrom,
                                                        $dateto,
                                                        $datefrom,
                                                        $dateto,
                                                        $datefrom,
                                                        $dateto));

    // Create the table headings.
    $table = new html_table();
    $table->width = '60%';
    $table->tablealign = 'left';
    $table->cellpadding = '1px';

    // Set the row heading.
    $row = new html_table_row();

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('firstname', 'report_api_user');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('lastname', 'report_api_user');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('email', 'report_api_user');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('state', 'report_api_user');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('username', 'report_api_user');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    if ($course != 0) {
        $coursename = $DB->get_field('course', 'fullname', array('id' => $course), null);
        // Create the student cell heading.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = $coursename;
        $cell->style = "text-align:left; font-size:18px;";
        $row->cells[] = $cell;
    } else {
        $coursename = $DB->get_fieldset_select('course', 'fullname', 'visible = ? ORDER BY fullname ASC', array('1'));
        foreach ($coursename as $k => $v) {
            // Create the student cell heading.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v;
            $cell->style = "text-align:left; font-size:18px;";
            $row->cells[] = $cell;
        }
    }

    $table->data[] = $row;

    // Cycle through SQL results filling in the html_table data.

    if (empty($studentquery)) {

        // Set the row heading.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('norecords', 'report_api_user');
        $cell->colspan = 14;
        $row->cells[] = $cell;
        $cell->style = "text-align:left; font-size:16px;";
        $table->data[] = $row;

        return html_writer::table($table); // Return table.
    } else {
        $userid = null;
        foreach ($studentquery as $c => $v) {

            if ($userid != $v->userid) {

                if ($userid != null) {
                    $table->data[] = $row;
                }
                $userid = $v->userid;

                // Set the row heading object.
                $row = new html_table_row();

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

                $email = $DB->get_field('user', 'email', array('id' => $v->userid), null);
                // Create the cell.
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = $email;
                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                $row->cells[] = $cell;

                // State custom field id.
                $sql = "SELECT data FROM {user_info_field} i
                        INNER JOIN {user_info_data} id on id.fieldid =i.id
                        WHERE i.shortname = ? AND id.userid = ?";
                $state =$DB->get_field_sql($sql, array($newcustomshortname->report_api_user_state, $v->userid));

                // Create the cell.
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = $state;
                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                $row->cells[] = $cell;

                $username = $DB->get_field('user', 'username', array('id' => $v->userid), null);
                // Create the cell.
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = $username;
                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                $row->cells[] = $cell;

                // Work out date constraints.
                if ($v->timecreated == '0') {
                    $timecreated = " - ";
                } else {
                    $timecreated = date('Y-m-d', $v->timecreated);
                }
                // Create the cell.
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = $timecreated;
                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                $row->cells[] = $cell;

            } else {

                // Work out date constraints.
                if ($v->timecreated == '0') {
                    $timecreated = " - ";
                } else {
                    $timecreated = date('Y-m-d', $v->timecreated);
                }

                // Create the cell.
                $cell = new html_table_cell();
                $cell->header = true;
                $cell->text = $timecreated;
                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                $row->cells[] = $cell;
            }
        }
        $table->data[] = $row;
    }
    return html_writer::table($table); // Return table.
}