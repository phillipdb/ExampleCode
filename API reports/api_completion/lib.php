<?php
/**
 * API Completion Report
 *
 * Index processing page.
 *
 * @package    report
 * @subpackage api_completion
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
function api_completion_export($datefrom, $dateto, $course) {
    global $CFG, $DB;

    $passgrade = get_config('report_api_completion');
    $passmark = $passgrade->report_api_completion_grade;

    // Create array of completed users and their courses.
    $sql =" SELECT  cc.id,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    c.id AS courseid,
                    cc.timecompleted AS completiondate
            FROM mdl_user u
            JOIN mdl_course_completions cc ON cc.userid = u.id
            JOIN mdl_course c ON c.id = cc.course
            WHERE cc.timecompleted > ? AND cc.timecompleted < ?
            AND c.visible = 1
            AND c.id = ?
            ORDER BY 4, 3";

    // Run student sql.
    $studentquery = $DB->get_records_sql($sql, array($datefrom, $dateto, $course));

    // CSV file creation and Data Export to CSV File.
    $filename = 'csvexport_'.date("Ymd").'.csv';
    @header('Content-Disposition: attachment; filename='.$filename);
    @header('Content-Type: text/csv');
    $coursename = $DB->get_field('course', 'fullname', array('id' => $course), null);
    $csvhead = array($coursename);
    $csvhead[] = get_string('firstname', 'report_api_completion');
    $csvhead[] = get_string('lastname', 'report_api_completion');
    if ($quizname = $DB->get_fieldset_select('quiz', 'name', 'course = ? ORDER BY name ASC', array($course))) {
        foreach ($quizname as $k => $v) {
            $csvhead[] = $v.get_string('gradeone', 'report_api_completion');
            $csvhead[] = $v.get_string('gradetwo', 'report_api_completion');
        }
    }
    $csvhead[] = get_string('passfail', 'report_api_completion');
    $csvhead[] = get_string('datecompleted', 'report_api_completion');

    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";
    if (empty($studentquery)) {
        $printarray[] = str_replace(', ', ' ', get_string('norecords', 'report_api_completion'));
        $line = implode(',', $printarray);
        echo $line;
        echo "\n";

    } else {
        $userid = null;
        // Looping through query output to write into CSV file.
        foreach ($studentquery as $c => $v) {
            $quizs = $DB->get_fieldset_select('quiz', 'id', 'course = ? ORDER BY name ASC', array($course));
            foreach ($quizs as $ii => $id) {
                $studentcourse = $DB->sql_concat_join("' '", array("A.userid", "A.itemid"));
                $sql = "SELECT  $studentcourse,
                                A.userid,
                                A.firstname,
                                A.lastname,
                                A.itemid,
                                A.finalgrade,
                                A.Quiz,
                                A.quizid,
                                A.courseid,
                                A.fullname,
                                MAX(A.attempt1) AS attempt1,
                                MAX(A.attempt2) AS attempt2,
                                MAX(A.grade1) AS grade1,
                                MAX(A.grade2) AS grade2
                        FROM (SELECT  u.id as userid,
                                u.firstname,
                                u.lastname,
                                c.id as courseid,
                                c.fullname,
                                gg.itemid,
                                gg.finalgrade,
                                gi.itemname AS quiz,
                                qa.quiz AS quizid,
                                qa.attempt AS attempt1,
                                0 AS attempt2,
                                qa.sumgrades as grade1,
                                0 AS grade2
                        FROM {user} u
                        INNER JOIN {grade_grades} gg ON gg.userid = u.id
                        INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                        INNER JOIN {course} c ON c.id = gi.courseid
                        INNER JOIN {quiz_attempts} qa ON qa.quiz = gi.iteminstance AND qa.userid = u.id
                        WHERE u.id = ? AND gi.itemmodule = 'quiz'
                        AND c.id= ? AND attempt = 1 AND qa.quiz = ?
                        UNION ALL
                        SELECT  u.id as userid,
                                u.firstname,
                                u.lastname,
                                c.id as courseid,
                                c.fullname,
                                gg.itemid,
                                gg.finalgrade,
                                gi.itemname AS quiz,
                                qa.quiz AS quizid,
                                0 AS attempt1,
                                qa.attempt AS attempt2,
                                0 as grade1,
                                qa.sumgrades AS grade2
                        FROM {user} u
                        INNER JOIN {grade_grades} gg ON gg.userid = u.id
                        INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                        INNER JOIN {course} c ON c.id = gi.courseid
                        INNER JOIN {quiz_attempts} qa ON qa.quiz = gi.iteminstance AND qa.userid = u.id
                        WHERE u.id = ? AND gi.itemmodule = 'quiz'
                        AND c.id= ? AND attempt = 2 AND qa.quiz = ?)  AS A
                        GROUP BY 1,2,3,4,5,6,7
                        ORDER BY 4,3,7";
                $quizresults = $DB->get_records_sql($sql, array($v->userid,
                                                                $v->courseid,
                                                                $id, $v->userid, $v->courseid, $id));
                if (!$quizresults) { // If no quiz matching this course and user print blank dashes.
                    if ($userid == null | $userid != $v->userid) {
                        $userid = $v->userid;
                        $fullname = $DB->get_field('course', 'fullname', array('id' => $v->courseid), null);
                        $printarray = array(str_replace(',', ' ', $fullname));
                        $firstname = $DB->get_field('user', 'firstname', array('id' => $v->userid), null);
                        $printarray[] = str_replace(', ', ' ', $firstname);
                        $lastname = $DB->get_field('user', 'lastname', array('id' => $v->userid), null);
                        $printarray[] = str_replace(', ', ' ', $lastname);
                        $printarray[] = str_replace(', ', ' ', ' - ');
                        $printarray[] = str_replace(', ', ' ', ' - ');
                    } else {
                        $printarray[] = str_replace(', ', ' ', ' - ');
                        $printarray[] = str_replace(', ', ' ', ' - ');
                    }
                } else {
                    foreach ($quizresults as $kk => $vv) {
                        if ($userid != $vv->userid) {
                            $userid = $vv->userid;
                            $fullname = $DB->get_field('course', 'fullname', array('id' => $v->courseid), null);
                            $printarray = array(str_replace(',', ' ', $fullname));
                            $firstname = $DB->get_field('user', 'firstname', array('id' => $v->userid), null);
                            $printarray[] = str_replace(', ', ' ', $firstname);
                            $lastname = $DB->get_field('user', 'lastname', array('id' => $v->userid), null);
                            $printarray[] = str_replace(', ', ' ', $lastname);
                            $grade1 = str_replace(".00000", ".0", $vv->grade1);
                            $printarray[] = str_replace(', ', ' ', $grade1);
                            if ($vv->attempt2 == 0) {
                                    $grade2 = ' - ';
                            } else {
                                    $grade2 = $vv->grade2;
                                    $grade2 = str_replace(".00000", ".0", $vv->grade2);
                            }
                            $printarray[] = str_replace(', ', ' ', $grade2);
                        } else {
                            $grade1 = str_replace(".00000", ".0", $vv->grade1);
                            $printarray[] = str_replace(', ', ' ', $grade1);
                            if ($vv->attempt2 == 0) {
                                $grade2 = ' - ';
                            } else {
                                $grade2 = $vv->grade2;
                                $grade2 = str_replace(".00000", ".0", $vv->grade2);
                            }
                            $printarray[] = str_replace(', ', ' ', $grade2);
                        }
                    }
                }
            }
            $sql =" SELECT  gg.userid,
                            gg.finalgrade
                    FROM mdl_grade_grades gg
                    INNER JOIN mdl_grade_items gi ON gg.itemid = gi.id
                    WHERE gi.itemtype = 'course'
                    AND gi.courseid = ? AND gg.userid = ?";
            if ($finalgrade = $DB->get_records_sql($sql, array($v->courseid, $v->userid))) {
                foreach ($finalgrade as $f => $g) {
                    if ($g->finalgrade >= $passmark) {
                        $passfail = get_string('pass', 'report_api_completion');
                    } else {
                        $passfail = get_string('fail', 'report_api_completion');
                    }
                }
            } else {
                $passfail = get_string('fail', 'report_api_completion');
            }
            $printarray[] = str_replace(', ', ' ', $passfail);
            $printarray[] = str_replace(', ', ' ', date('Y-m-d', $v->completiondate));

            $line = implode(', ', $printarray);
            echo $line;
            echo "\n";
        }
    }
}

/**  
 * Returns a table of relevant information
 *
 * @param $datefrom, $dateto, $course
 * @return html_table
 */
function api_completion_previewtable($datefrom, $dateto, $course) {
    global $CFG, $DB, $USER;

    $passgrade = get_config('report_api_completion');
    $passmark = $passgrade->report_api_completion_grade;

    // Create array of completed users and their courses..
    $sql =" SELECT  cc.id,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    c.id AS courseid,
                    cc.timecompleted AS completiondate
            FROM mdl_user u
            JOIN mdl_course_completions cc ON cc.userid = u.id
            JOIN mdl_course c ON c.id = cc.course
            WHERE cc.timecompleted > ? AND cc.timecompleted < ?
            AND c.visible = 1
            AND c.id = ?
            ORDER BY 4, 3";

    // Run student sql.
    $studentquery = $DB->get_records_sql($sql, array($datefrom, $dateto, $course));

    // Create the table headings.
    $table = new html_table();
    $table->width = '60%';
    $table->tablealign = 'left';
    $table->cellpadding = '1px';

    // Set the row heading.
    $row = new html_table_row();

    $coursename = $DB->get_field('course', 'fullname', array('id' => $course), null);
    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = $coursename;
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('firstname', 'report_api_completion');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('lastname', 'report_api_completion');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    if ($quizname = $DB->get_fieldset_select('quiz', 'name', 'course = ? ORDER BY name ASC', array($course))) {
        foreach ($quizname as $k => $v) {
            // Create the quiz cell heading.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v.get_string('gradeone', 'report_api_completion');
            $cell->style = "text-align:left; font-size:18px;";
            $row->cells[] = $cell;

            // Create the quiz cell heading.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v.get_string('gradetwo', 'report_api_completion');
            $cell->style = "text-align:left; font-size:18px;";
            $row->cells[] = $cell;
        }
    }

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('passfail', 'report_api_completion');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('datecompleted', 'report_api_completion');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    $table->data[] = $row;

    // Cycle through SQL results filling in the html_table data.

    if (empty($studentquery)) {

        // Set the row heading.
        $row = new html_table_row();
        $colspan = $DB->count_records('quiz', array('course' => $course));

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('norecords', 'report_api_completion');
        $row->cells[] = $cell;
        $cell->colspan = $colspan*2+5;
        $cell->style = "text-align:left; font-size:16px;";
        $table->data[] = $row;

        return html_writer::table($table); // Return table.
    } else {
        $userid = null;
        foreach ($studentquery as $c => $v) {
            $quizs = $DB->get_fieldset_select('quiz', 'id', 'course = ? ORDER BY name ASC', array($course));
            foreach ($quizs as $ii => $id) {
                $studentcourse = $DB->sql_concat_join("' '", array("A.userid", "A.itemid"));
                $sql = "SELECT  $studentcourse,
                                A.userid,
                                A.firstname,
                                A.lastname,
                                A.itemid,
                                A.finalgrade,
                                A.Quiz,
                                A.quizid,
                                A.courseid,
                                A.fullname,
                                MAX(A.attempt1) AS attempt1,
                                MAX(A.attempt2) AS attempt2,
                                MAX(A.grade1) AS grade1,
                                MAX(A.grade2) AS grade2
                        FROM (SELECT  u.id as userid,
                                u.firstname,
                                u.lastname,
                                c.id as courseid,
                                c.fullname,
                                gg.itemid,
                                gg.finalgrade,
                                gi.itemname AS quiz,
                                qa.quiz AS quizid,
                                qa.attempt AS attempt1,
                                0 AS attempt2,
                                qa.sumgrades as grade1,
                                0 AS grade2
                        FROM {user} u
                        INNER JOIN {grade_grades} gg ON gg.userid = u.id
                        INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                        INNER JOIN {course} c ON c.id = gi.courseid
                        INNER JOIN {quiz_attempts} qa ON qa.quiz = gi.iteminstance AND qa.userid = u.id
                        WHERE u.id = ? AND gi.itemmodule = 'quiz'
                        AND c.id= ? AND attempt = 1 AND qa.quiz = ?
                        UNION ALL
                        SELECT  u.id as userid,
                                u.firstname,
                                u.lastname,
                                c.id as courseid,
                                c.fullname,
                                gg.itemid,
                                gg.finalgrade,
                                gi.itemname AS quiz,
                                qa.quiz AS quizid,
                                0 AS attempt1,
                                qa.attempt AS attempt2,
                                0 as grade1,
                                qa.sumgrades AS grade2
                        FROM {user} u
                        INNER JOIN {grade_grades} gg ON gg.userid = u.id
                        INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                        INNER JOIN {course} c ON c.id = gi.courseid
                        INNER JOIN {quiz_attempts} qa ON qa.quiz = gi.iteminstance AND qa.userid = u.id
                        WHERE u.id = ? AND gi.itemmodule = 'quiz'
                        AND c.id= ? AND attempt = 2 AND qa.quiz = ?)  AS A
                        GROUP BY 1,2,3,4,5,6,7
                        ORDER BY 4,3,7";
                $quizresults = $DB->get_records_sql($sql, array($v->userid,
                                                                $v->courseid,
                                                                $id, $v->userid, $v->courseid, $id));

                if (!$quizresults) { // If no quiz matching this course and user print blank dashes.

                    if ($userid == null | $userid != $v->userid) {
                        $userid = $v->userid;
                        // If it is a new row we need to start the row properly with names.
                        $row = new html_table_row();
                        // Create the cell.
                        $fullname = $DB->get_field('course', 'fullname', array('id' => $v->courseid), null);
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = $fullname;
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;

                        // Create the cell.
                        $firstname = $DB->get_field('user', 'firstname', array('id' => $v->userid), null);
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = $firstname;
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;

                        // Create the cell.
                        $lastname = $DB->get_field('user', 'lastname', array('id' => $v->userid), null);
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = $lastname;
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;

                        // Create the cell.
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = " - ";
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;

                        // Create the cell.
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = " - ";
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;
                    } else {
                        // Create the cell.
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = " - ";
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;

                        // Create the cell.
                        $cell = new html_table_cell();
                        $cell->header = true;
                        $cell->text = " - ";
                        $cell->style = 'text-align:left; width:40%; font-size:16px;';
                        $row->cells[] = $cell;
                    }
                } else {
                    // Quiz records found.
                    foreach ($quizresults as $kk => $vv) {
                        // If userid does not equal the new one.
                        if ($userid != $vv->userid) {
                            // If null we would have to create new start of rows.
                            if ($userid == null |$userid != $vv->userid ) {
                                $userid = $vv->userid;
                                // Set the row heading object.
                                $row = new html_table_row();

                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = $vv->fullname;
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;

                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = $vv->firstname;
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;

                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = $vv->lastname;
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;

                                $grade1 = str_replace(".00000", ".0", $vv->grade1);
                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = $grade1;
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;

                                if ($vv->attempt2 == 0) {
                                    $grade2 = ' - ';
                                } else {
                                    $grade2 = $vv->grade2;
                                    $grade2 = str_replace(".00000", ".0", $vv->grade2);
                                }
                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = $grade2;
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;
                            } else {
                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = $grade2;
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;

                                // Create the cell.
                                $cell = new html_table_cell();
                                $cell->header = true;
                                $cell->text = date('Y-m-d', $v->completiondate);
                                $cell->style = 'text-align:left; width:40%; font-size:16px;';
                                $row->cells[] = $cell;

                                $table->data[] = $row;
                            }
                        } else {
                            $grade1 = str_replace(".00000", ".0", $vv->grade1);
                            // Create the cell.
                            $cell = new html_table_cell();
                            $cell->header = true;
                            $cell->text = $grade1;
                            $cell->style = 'text-align:left; width:40%; font-size:16px;';
                            $row->cells[] = $cell;

                            if ($vv->attempt2 == 0) {
                                $grade2 = ' - ';
                            } else {
                                $grade2 = $vv->grade2;
                                $grade2 = str_replace(".00000", ".0", $vv->grade2);
                            }
                            // Create the cell.
                            $cell = new html_table_cell();
                            $cell->header = true;
                            $cell->text = $grade2;
                            $cell->style = 'text-align:left; width:40%; font-size:16px;';
                            $row->cells[] = $cell;
                        }
                    }
                }
            }
            $sql =" SELECT  gg.userid,
                            gg.finalgrade
                    FROM mdl_grade_grades gg
                    INNER JOIN mdl_grade_items gi ON gg.itemid = gi.id
                    WHERE gi.itemtype = 'course'
                    AND gi.courseid = ? AND gg.userid = ?";
            if ($finalgrade = $DB->get_records_sql($sql, array($v->courseid, $v->userid))) {
                foreach ($finalgrade as $f => $g) {
                    if ($g->finalgrade >= $passmark) {
                        $passfail = get_string('pass', 'report_api_completion');
                    } else {
                        $passfail = get_string('fail', 'report_api_completion');
                    }
                }
            } else {
                $passfail = get_string('fail', 'report_api_completion');
            }

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $passfail;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = date('Y-m-d', $v->completiondate);
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            $table->data[] = $row;
        }
    }
    return html_writer::table($table); // Return table.
}