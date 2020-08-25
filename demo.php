<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_questionnaire
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include ('../../config.php');
global $DB;
$qid = optional_param('qid', null, PARAM_INT);              // Survey id.

// Get all the instructors;

$queryins = "Select DISTINCT(c.staffid)
             FROM {questionnaire_question} q
             JOIN {questionnaire_quest_ins} c
              ON question_id = q.id
              WHERE q.surveyid =".$qid."
              ORDER by c.staffid";

$resultins = $DB->get_records_sql($queryins);
$rowheaders = [];
$rowheaders[] = 'user name ';
$staff = [];
$staffcnt = 0;
foreach($resultins as $result) {
   $staff[] = $result->staffid;
   $userid = $result->staffid;
   $lname = $DB->get_field('user','lastname', array('id' => $userid));
   $fname = $DB->get_field('user','firstname', array('id' => $userid)) . ' '.$lname;
   $rowheaders[] = $fname;
   $staffcnt = $staffcnt + 1;
}
$olduser = 0;
$lines = [];
// Get all the results

$queryres = "SELECT CONCAT_WS('_', qr.id, 'ratescale', qrr.id) AS id, qr.submitted, qr.complete,
                    qr.grade, qr.userid, u.firstnamephonetic, u.lastnamephonetic, u.middlename,
                    u.alternatename, u.firstname, u.lastname,
                    u.username, u.department, u.institution, u.id as usrid, qr.id AS rid,
                    qrr.question_id, qrr.choice_id, null AS response,
                    qrr.rankvalue
             FROM   {questionnaire_response} qr
             JOIN   {questionnaire_response}_rank qrr
               ON   qrr.response_id = qr.id AND qr.questionnaireid =".$qid ."
                    AND qr.complete = 'y'
                    LEFT JOIN {user} u ON u.id = qr.userid
                    ORDER BY usrid, id";

$surveyres = $DB->get_records_sql($queryres);
$cols = [];
for($k = 0; $k < $staffcnt; $k++ ) {
    $cols[$k] = 0;
}
$cnt = 0;
foreach ($surveyres as $survey){
	 $choiceid = $survey->choice_id;
	 // Get the staff id.
	 $staffid = $DB->get_field('questionnaire_quest_ins', 'staffid', array('id' => $choiceid));
         $rankvalue = $survey->rankvalue;
	 $key = array_search($staffid, $staff);
	 $cols[$key] = $rankvalue;
	 $rankvalue = $survey->rankvalue;
  	 if ($olduser == 0) {
	     $olduser = $survey->userid;
	 }

	 if ($olduser <> $survey->userid) {
             // display old values;
             $ln = [];
             $first = $first .' '.$last;
             $ln = array($first);
             for($k = 0; $k < $staffcnt; $k++) {
                 array_push($ln, $cols[$k]);
                 // Reset.
                 $cols[$k] = 0;
             }
             $lnew[$cnt] = $ln;
             $cnt = $cnt + 1;
             $olduser = $survey->userid;
	 } else {
	     $last = $survey->lastname;
	     $first = $survey->firstname;
	 }
}
$first = $first .' '.$last;
$ln = array($first);
for($k = 0; $k < $staffcnt; $k++ ) {
    array_push($ln, $cols[$k]);
}
$lines[] = $ln;
$lnew[$cnt] = $ln;

$filename = '123';

 // Use Moodle's core download function for outputting csv.
\core\dataformat::download_data($filename, 'csv', $rowheaders, $lnew);

