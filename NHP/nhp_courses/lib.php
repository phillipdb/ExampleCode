<?php
/**
 * NHP Completed Courses Report 
 *
 * Library functions
 *
 * @package    report
 * @subpackage nhp_courses
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 Pukunui Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function nhp_export_grades() {
    global $CFG, $DB;

    $studentcat = $DB->sql_concat("u.firstname","' '","u.lastname");
    $sql = "SELECT  cc.id as completionid,
                    u.firstname AS firstname,
                    u.lastname AS lastname,
                    c.fullname AS coursename,
                    u.idnumber AS studentid,
                    cc.timecompleted AS completion,
                    c.summary AS summary,
                    ccc.name AS category,
                    c.idnumber as code
            FROM mdl_course c
            INNER JOIN mdl_course_completions cc on cc.course = c.id
            INNER JOIN mdl_user u on u.id = cc.userid
            INNER JOIN mdl_course_categories ccc on ccc.id = c.category
            ORDER BY 4,3,6";

    if ($users = $DB->get_records_sql($sql, null)) {
        // CSV file creation and Data Export to CSV File
        $filename = 'csvexport_'.date("Ymd").'.csv';
        @header('Content-Disposition: attachment; filename='.$filename);
        @header('Content-Type: text/csv');
        $csvhead = array(get_string('coursename','report_nhp_courses'), get_string('studentname','report_nhp_courses'),get_string('studentidnumber','report_nhp_courses'),  get_string('dateachieved','report_nhp_courses'),get_string('coursecategory','report_nhp_courses'), get_string('coursecode', 'report_nhp_courses'),get_string('coursedescription', 'report_nhp_courses'));
        $csvheading = implode(',', $csvhead);
        echo $csvheading;
        echo "\n";

        // Looping through query output to write into CSV file.
        foreach ($users as $u) {
            $date = date('d/m/Y',$u->completion);
            $summary = strip_tags($u->summary);
            $studentname = $u->firstname.' '.$u->lastname;
            $printarray = array(str_replace(',',' ',$u->coursename), str_replace(',', ' ', $studentname), str_replace(',', ' ', $u->studentid), str_replace(',',' ',$date), str_replace(',', ' ', $u->category), str_replace(',',' ',$u->code), str_replace(',',' ',$summary));
            $line = implode(',', $printarray);
            echo $line;
            echo "\n";
        }
    exit;
    }
}