<head>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
</head>

<H1> Grades Uploader </H1>
<?php

require_once '../config.php';
require_once($CFG->libdir . '/gradelib.php'); //

 
require '/var/www/composer/vendor/autoload.php';//absolute values of composer installation...
require './../grade/edit/tree/total_creator.php';

// die;

//https://stackoverflow.com/questions/3302857/algorithm-to-get-the-excel-like-column-name-of-a-number
//get excel name column
function getNameFromNumber($num) {
    $numeric = $num % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval($num / 26);
    if ($num2 > 0) {
        return getNameFromNumber($num2 - 1) . $letter;
    } else {
        return $letter;
    }
}

/* */
function getLetterFromPoint($grade_letters,$point){
	foreach ($grade_letters as $num => $let) {
		//echo $num ."=>". $let." ";
		$numreal=floatval($num);
		if($point>=$numreal)return $let;
	}
	
	return $let; //lowest letter
		
}

function getPointFromLetter($grade_letters,$cl){
	foreach ($grade_letters as $num => $let) {
		if(trim($let)==trim($cl))return (floatval($num)+1.0);
	}
	return 0;
}


use PhpOffice\PhpSpreadsheet\IOFactory;


$inputFileType = 'Xls';
$inputFileName='/var/www/composer/sem2grades.xls';
$inputFileName='/var/www/composer/testgrades.xls';

// Create a new Reader of the type defined in $inputFileType
$reader = IOFactory::createReader($inputFileType);
// Load $inputFileName to a PhpSpreadsheet Object
$spreadsheet = $reader->load($inputFileName);

// Use the PhpSpreadsheet object's getSheetCount() method to get a count of the number of WorkSheets in the WorkBook
$sheetCount = $spreadsheet->getSheetCount();
// $helper->log('There ' . (($sheetCount == 1) ? 'is' : 'are') . ' ' . $sheetCount . ' WorkSheet' . (($sheetCount == 1) ? '' : 's') . ' in the WorkBook');

// $helper->log('Reading the names of Worksheets in the WorkBook');
// Use the PhpSpreadsheet object's getSheetNames() method to get an array listing the names/titles of the WorkSheets in the WorkBook
$sheetNames = $spreadsheet->getSheetNames();
foreach ($sheetNames as $sheetIndex => $sheetName) {
    // $helper->log('WorkSheet #' . $sheetIndex . ' is named "' . $sheetName . '"');
	echo "worksheet ".$sheetIndex ." =>" .$sheetName."<br><br>";
}


$spreadsheet->setActiveSheetIndex(0);

$courseNumber=11;
// $courseNumber=2;
$courseInterval=3; //column used by each course
$courseFullNameRow=8;
$courseShortNameRow=$courseFullNameRow-1	;
$courseFullNameCol=3; //'D'
$courseLetterOffset=2;
$coursePointOffset=1;



$nim_col='C';
$nim_start=10;
$nim_end=96;

// $course_prefix ="22_23"; //moodle course shortname prefix
$course_prefix =""; //moodle course shortname prefix


for ($c=1;$c<=$courseNumber;$c++){
	
	
	
	//1. get course code
	
	//2. cek item exist ? if not ... regrade ..
	
	$coord=getNameFromNumber($courseFullNameCol).$courseShortNameRow;
	// echo $coord."<br>";		
	$shortName=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
	$shortName=trim($shortName);
	if($shortName==false) die ("error get course shortname");
	
	$coord=getNameFromNumber($courseFullNameCol).$courseFullNameRow;
	// echo $coord."<br>";		
	$fullName=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
	if($fullName==false) die ("error get course name");
	echo "<BR>\n";
	echo "<br>in Excel : Course ".$shortName." ".$fullName;

	if (!$course = $DB->get_record('course', array('shortname' => $shortName))) {
		print_error('invalidcourseshort code'. $shortName);
	}else {
		echo "In Moodle: Course id: ".$course->id." ".$course->fullname;
	}
	
	echo "<br>";
	$max_tries=1;
	for ($try=1;$try<=$max_tries+1;$try++){
		if ($gitem = $DB->get_record('grade_items', array('courseid' => $course->id))) {
			echo(' item TOTAL exist ='.$gitem->id. ", grade max =".$gitem->grademax);
			
		}else {
			echo(' item TOTAL NOT exist ');
			
			if($try<=$max_tries){
				create_grade_total($DB,$course->id);
				continue;
			}else{
				die ("CAN NOT create TOTAL grade ");
			}
		}
		if($gitem->grademax<100){
			echo " <font color=\"red\"> => course total 'grademax' not 100=>".$giteim->grademax."</font><br>";
			die;
		}
		
	}

	//create course context first ... 
	$context = context_course::instance($course->id);
	if (!$context ){
		die ("error getting course context");
	}
	$grade_letters= grade_get_letters($context) ; //get letters and its 
	if(!$grade_letters){
		die("can't get course grade letters ");
	} 
	echo "<br>";
	// var_dump($grade_letters);
	// foreach ($grade_letters as $num => $let) {
		// echo $num ."=>". $let." ";
	// }
	// echo "<BR>\n";
	
	// $nim_end=$nim_start; //debugging only
	
	/**/
	for ($nim=$nim_start;  $nim<=$nim_end; $nim++){
			echo "<br>";
			$coord=$nim_col.$nim;
			 $NIM=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 if($NIM==false) die ("error get NIM at".$coord);

			 //getting userid
			if (!$user = $DB->get_record('user', array('username' => trim($NIM->getValue()) ))) {
				 echo " <font color=\"red\">NIM ". $NIM. "Not FOUND</font><br>";
				die ();
			}
			 
			 $point_col=$courseFullNameCol+$coursePointOffset;
			 $coord=getNameFromNumber($point_col).$nim;
			 $point=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 // echo "9point coord:  ".$coord." => ".$point . ")";
			 if($point==false) die ("error get POINT at".$coord);
			 $letter_col=$courseFullNameCol+$courseLetterOffset;
			 $coord=getNameFromNumber($letter_col).$nim;
			 $letter=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 if($point==false) die ("error get course name");
			 $letter=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 $fullName=$user->firstname." ". $user->lastname;
			 echo $NIM." ".$fullName." ".$point." ".$letter."<BR>";
			 $point=floatval($point->getValue());
			 
			 
			 //inserting, updating ..
			 $cl=trim(getLetterFromPoint($grade_letters,$point));
			 $letter=trim($letter->getValue());
			 echo "correct letter for ".$point." is ".$cl;
			 
			 if ($cl != $letter){
				 $point=getPointFromLetter($grade_letters,$letter);
				 echo " <font color=\"red\"> => LETTER NOT SAME</font><br>";
				 echo " <font color=\"red\"> => point CORRECTED to ".$point."</font><br>";
				 
			 } else {
				 echo " <font color=\"green\"> => LETTER already SAME</font><br>";
				 
			 }
			 // $itemmodule, $iteminstance,$userid_or_ids=null;
			 // $stgrades=grade_get_grades($course->id, "course", null, null, $user->id);
			 // if(!$stgrades){
				 // echo " <font color=\"red\"> => COURSE TOTAL NOT GRADED</font><br>";
					// die;
			 // }
			 // var_dump($stgrades);
			 // function grade_update($source, $courseid, $itemtype, $itemmodule, $iteminstance, $itemnumber, $grades=NULL, $itemdetails=NULL)
			 // grade_update("course", $course->id, "course", null, null, null, $grades=NULL, null);
			die;
			 
	} //eo. for ($nim=$nim_start;  $nim<=$nim_end; $nim++){
	
	/**/
	
	$courseFullNameCol+=$courseInterval;
}	
echo "finish...<br>";