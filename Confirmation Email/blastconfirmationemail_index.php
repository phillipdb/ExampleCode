<?php
 /**
  * Blast - confirmation email 
  *
  * Creates an email to the support contact address if there is one.
  *
  * @package    local
  * @subpackage confirmationemail
  * @author     Phillip Bennett, Pukunui (http://pukunui.com)
  * @copyright  2013 BLAST
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

require_once('../../config.php');

Global $DB, $CFG;

$orgunit = optional_param('orgunit', 0, PARAM_INT);
$datefrom = optional_param('datefrom', 0, PARAM_NOTAGS);


$to = new stdClass();
$to->email = 'phillipdigbybennett@gmail.com';

// The from details.
$from = new stdClass();
$from->idnumber = $data->idnumber;
$from->firstname = $data->firstname;
$from->lastname = $data->surname;
$from->email = $data->email;
$from->facility = $data->facility;
$from->phone = $data->phone;
$from->maildisplay = 1;

$emailsubject = get_string('subject', 'local_helpemail');
$messagetext = get_string('messagetext', 'local_helpemail', $from);
$messagehtml = text_to_html($messagetext, null, false, true);
email_to_user($to, $from, $emailsubject, $messagetext, $messagehtml);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();