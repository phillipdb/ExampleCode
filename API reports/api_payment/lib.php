<?php
/**
 * API Payment Report
 *
 * Index processing page.
 *
 * @package    report
 * @subpackage api_payment
 * @author     Phillip Bennett, Pukunui (http://pukunui.com)
 * @copyright  2013 API
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**  
 * Exports results to a .CSV file.
 *
 * @param $datefrom, $dateto, $course.
 * @return downloadable .CVS file
 */
function api_payment_export($datefrom, $dateto, $course) {
    global $CFG, $DB;

    // Create array of completed users and their courses.
    $sql =" SELECT  u.firstname AS firstname, 
                    u.lastname AS lastname, 
                    c.fullname AS coursename, 
                    p.timeupdated AS transactiontime,
                    e.cost,
                    p.txn_id AS transactionid,
                    p.parent_txn_id AS approvalnumber
            FROM mdl_enrol_paypal p 
            INNER JOIN mdl_user u ON p.userid = u.id 
            INNER JOIN mdl_course c ON p.courseid = c.id
            INNER JOIN mdl_enrol e ON e.courseid = p.courseid AND e.courseid = c.id
            AND p.timeupdated > ? AND p.timeupdated < ?
            AND c.id = ?";
    // Run student sql.
    $studentquery = $DB->get_records_sql($sql, array($datefrom, $dateto, $course));

    // CSV file creation and Data Export to CSV File.
    $filename = 'csvexport_'.date("Ymd").'.csv';
    @header('Content-Disposition: attachment; filename='.$filename);
    @header('Content-Type: text/csv');
    $csvhead = array(get_string('firstname', 'report_api_payment'));
    $csvhead[] = get_string('lastname', 'report_api_payment');
    $csvhead[] = get_string('coursename', 'report_api_payment');
    $csvhead[] = get_string('dateheader', 'report_api_payment');
    $csvhead[] = get_string('cost', 'report_api_payment');
    $csvhead[] = get_string('payee', 'report_api_payment');
    $csvhead[] = get_string('transactionid', 'report_api_payment');
    $csvhead[] = get_string('approvalnumber', 'report_api_payment');

    $csvheading = implode(',', $csvhead);
    echo $csvheading;
    echo "\n";
    if (empty($studentquery)) {
        $printarray[] = str_replace(', ', ' ', get_string('norecords', 'report_api_payment'));
        $line = implode(',', $printarray);
        echo $line;
        echo "\n";
    } else {
        // Looping through query output to write into CSV file.
        foreach ($studentquery as $c => $v) {
            $printarray = array(str_replace(',', ' ', $v->firstname));
            $printarray[] = str_replace(', ', ' ', $v->lastname);
            $printarray[] = str_replace(', ', ' ', $v->coursename);
            $printarray[] = str_replace(', ', ' ', date('Y-m-d',$v->transactiontime));
            $printarray[] = str_replace(', ', ' ', '$'.$v->cost);
            $printarray[] = str_replace(', ', ' ', $v->payee);
            $printarray[] = str_replace(', ', ' ', $v->transactionid);
            $printarray[] = str_replace(', ', ' ', $v->approvalnumber);

            $line = implode(', ', $printarray);
            echo $line;
            echo "\n";
        }
    }
}

/**  
 * Returns a table of relevant information and displays on the screen.
 *
 * @param $datefrom, $dateto, $course
 * @return html_table
 */
function api_payment_previewtable($datefrom, $dateto, $course) {
    global $CFG, $DB, $USER;

    $passgrade = get_config('report_api_payment');
    $passmark = $passgrade->report_api_payment_grade;

    // Create array of completed users and their courses..
    $sql =" SELECT  u.firstname AS firstname, 
                    u.lastname AS lastname, 
                    c.fullname AS coursename, 
                    p.timeupdated AS transactiontime,
                    e.cost,
                    p.txn_id AS transactionid,
                    p.parent_txn_id AS approvalnumber
            FROM mdl_enrol_paypal p 
            INNER JOIN mdl_user u ON p.userid = u.id 
            INNER JOIN mdl_course c ON p.courseid = c.id
            INNER JOIN mdl_enrol e ON e.courseid = p.courseid AND e.courseid = c.id
            WHERE e.enrol = 'paypal'
            AND p.timeupdated > ? AND p.timeupdated < ?
            AND c.id = ?";
    // Run student sql.
    $studentquery = $DB->get_records_sql($sql, array($datefrom, $dateto, $course));

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
    $cell->text = get_string('firstname', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('lastname', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    $coursename = $DB->get_field('course', 'fullname', array('id' => $course), null);
    // Create the coursename cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('coursename', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the transaction date heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('dateheader', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('cost', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('payee', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('transactionid', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    // Create the student cell heading.
    $cell = new html_table_cell();
    $cell->header = true;
    $cell->text = get_string('approvalnumber', 'report_api_payment');
    $cell->style = "text-align:left; font-size:18px;";
    $row->cells[] = $cell;

    $table->data[] = $row;

    // Cycle through SQL results filling in the html_table data.

    if (empty($studentquery)) {

        // Set the row heading.
        $row = new html_table_row();

        // Create the cell.
        $cell = new html_table_cell();
        $cell->header = true;
        $cell->text = get_string('norecords', 'report_api_payment');
        $row->cells[] = $cell;
        $cell->colspan = 8;
        $cell->style = "text-align:left; font-size:16px;";
        $table->data[] = $row;

        return html_writer::table($table); // Return table.
    } else {
        foreach ($studentquery as $c => $v) {
            // If it is a new row we need to start the row properly with names.
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

            // Create the cell.
            $fullname = $DB->get_field('course', 'fullname', array('id' => $v->courseid), null);
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->coursename;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = date('Y-m-d',$v->transactiontime);
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = '$'.$v->cost;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->payee;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->transactionid;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            // Create the cell.
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $v->approvalnumber;
            $cell->style = 'text-align:left; width:40%; font-size:16px;';
            $row->cells[] = $cell;

            $table->data[] = $row;
        }
    }
    return html_writer::table($table); // Return table.
}