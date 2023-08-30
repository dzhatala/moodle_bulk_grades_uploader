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
 * The Gradebook setup page.
 *
 * @package   core_grades
 * @copyright 2008 Nicolas Connault
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/lib.php'; // for preferences
require_once $CFG->dirroot.'/grade/edit/tree/lib.php';


// $courseid        = required_param('id', PARAM_INT);
$courseid=39;
// $action          = optional_param('action', 0, PARAM_ALPHA);
// $eid             = optional_param('eid', 0, PARAM_ALPHANUM);
// $weightsadjusted = optional_param('weightsadjusted', 0, PARAM_INT);



function create_grade_total($DB,$courseid){
	// $url = new moodle_url('/grade/edit/tree/index.php', array('id' => $courseid));
	// $PAGE->set_url($url);
	// $PAGE->set_pagelayout('admin');
	/// Make sure they can even access this course
	if (!$course = $DB->get_record('course', array('id' => $courseid))) {
		print_error('invalidcourseid');
	}

	require_login($course);
	$context = context_course::instance($course->id);
	require_capability('moodle/grade:manage', $context);

	// $PAGE->requires->js_call_amd('core_grades/edittree_index', 'enhance');

	/// return tracking object
	$gpr = new grade_plugin_return(array('type'=>'edit', 'plugin'=>'tree', 'courseid'=>$courseid));
	$returnurl = $gpr->get_return_url(null);

	// get the grading tree object
	// note: total must be first for moving to work correctly, if you want it last moving code must be rewritten!
	$gtree = new grade_tree($courseid, false, false);

	if (empty($eid)) {
		$element = null;
		$object  = null;

	} else {
		if (!$element = $gtree->locate_element($eid)) {
			print_error('invalidelementid', '', $returnurl);
		}
		$object = $element['object'];
	}

	$switch = grade_get_setting($course->id, 'aggregationposition', $CFG->grade_aggregationposition);

	$strgrades             = get_string('grades');
	$strgraderreport       = get_string('graderreport', 'grades');

	/*$moving = false;
	$movingeid = false;

	if ($action == 'moveselect') {
		if ($eid and confirm_sesskey()) {
			$movingeid = $eid;
			$moving=true;
		}
	}*/

	$grade_edit_tree = new grade_edit_tree($gtree, $movingeid, $gpr);
}





//$course

if ($gitem = $DB->get_record('grade_items', array('courseid' => $courseid))) {
		print_error('course exist '.$courseid);
	}else 

create_grade_total($DB,$courseid);

echo "<h1>SUCCESS</h1>";

