<head>
Content-type: text/html; charset=utf-8
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
</head>

<H1> Grades Uploader </H1>
<?php

require_once '../config.php';
require_once($CFG->libdir . '/gradelib.php'); //

// require_sesskey(); //protect ajax submit
 
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


require_login();

use PhpOffice\PhpSpreadsheet\IOFactory;


$inputFileType = 'Xls';
$inputFileName='/var/www/composer/sem2grades.xls';
// $inputFileName='/var/www/composer/testgrades.xls';

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

$courseInterval=3; //column used by each course
$courseFullNameRow=8;
$courseShortNameRow=$courseFullNameRow-1	;
$courseFullNameCol=3; //'D'
$courseLetterOffset=2;
$coursePointOffset=1;




$course_prefix ="22_23"; //moodle course shortname prefix
$course_prefix =""; //moodle course shortname prefix


$courseNumber=11;
// $courseNumber=1; //column number in excel contain courses
$nim_col='C';   // NIM column
$nim_start=10; //excel row number start looking NIM
$nim_end=96;
// $nim_end=11; //excel row number end looking NIM
$currentuser='2';
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

	$course=false;
	if (!$course = $DB->get_record('course', array('shortname' => $shortName))) {
		print_error('invalidcourseshort code'. $shortName);
	}else {
		echo "In Moodle: Course id: ".$course->id." ".$course->fullname;
	}
	require_login($course);
	
	echo "<br>";
	$max_tries=1;
	$gitem=false;
	for ($try=1;$try<=$max_tries+1;$try++){
		ob_flush();flush();
		
		if ($gitem = $DB->get_record('grade_items', array('courseid' => $course->id))) {
			echo(' item TOTAL exist ='.$gitem->id. ", grade max =".$gitem->grademax);
			break;
		}else {
			echo(' item TOTAL NOT exist ');
			
			if($try<=$max_tries){
				echo(' try creating ... ');
				create_grade_total($DB,$course->id);
				continue;
			}else{
				die ("CAN NOT create TOTAL grade ");
			}
		}
		
	}

	$max_tries=1;
	for ($try=1;$try<=$max_tries+1;$try++){

		if($gitem->grademax<100){
				echo " <br><font color=\"red\"> => course total 'grademax' not 100=>".$gitem->grademax."</font><br>";
			if($try<=$max_tries){
				echo(" try set items id=".$gitem->id." to 100 ... ");
				$DB->update_record('grade_items', array('id' => $gitem->id, 'grademax' => 100));
				continue;
			}else{
				die ("CAN NOT set 100 to grademax ");
			}
		} else{
				echo " <br><font color=\"green\"> => course total 'grademax' ALREADY 100=>".$gitem->grademax."</font><br>";
				break;
			
		}
		

		
	}
	
	//create course context first ... 
	$context = context_course::instance($course->id);
	require_capability('moodle/grade:edit', $context);
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
			 $user=false;
			if (!$user = $DB->get_record('user', array('username' => trim($NIM->getValue()) ))) {
				 echo " <font color=\"red\">NIM ". $NIM. "Not FOUND</font><br>";
				die ();
			}else {
				echo " <font color=\"green\">userid:".$user->id." NIM ". $user->username. " FOUND</font><br>";
				
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
			 echo "XLS: ".$NIM." ".$fullName." ".$point." ".$letter."<BR>";
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
				 echo " <font color=\"green\"> => LETTER already SAME</font>";
				 
			 }

			//finding existing grades
			$max_tries=1;
			for ($try=1;$try<=$max_tries+1;$try++){
				$grade=$DB->get_record("grade_grades",array("itemid"=>$gitem->id,"userid"=>$user->id));
				if(!$grade){
					// die;					
					if($try<=$max_tries){
						echo " <br><font color=\"black\"> grade NOT exist try inserting ... </font><br>";
						$gar=array("itemid"=>$gitem->id,"userid"=>$user->id
								   ,"rawgrademax"=>100.0, "rowgrademin"=>0.0
								   ,"usermodified"=>$currentuser,  "finalgrade"=>$point
								   ,"overridden"=>1693481396,"timemodified"=>1693481396
							);
						$DB->insert_record('grade_grades', $gar);
						continue;
					}else{
						echo " <br><font color=\"red\"> can NOT insert grade </font>";
						die ;
					}
				} else{
						echo "<br><font color=\"green\"> grade found=>".$grade->finalgrade." </font>";
						
						// var_dump($grade); die;
						break;
					
				}
			}
			
			//TODO check grade point equality .. 
			$max_tries=1;
			for ($try=1;$try<=$max_tries+1;$try++){
				$grade=$DB->get_record("grade_grades",
										array("itemid"=>$gitem->id
												,"userid"=>$user->id));
												
				if(!(floatval($grade->finalgrade)-floatval($point)<=1)){ //the difference more than one point ?
					
					// echo "grade not the same".$grade->finalgrade."!=".$point; die;					
					if($try<=$max_tries){
						echo " <br><font color=\"black\"> grade NOT the same".$grade->finalgrade."!=".$point." </font>";
						echo " <br><font color=\"black\"> updating </font>";
						$gar=array("id"=>$grade->id
						           ,"itemid"=>$gitem->id,"userid"=>$user->id
								   ,"rawgrademax"=>100.0, "rowgrademin"=>0.0
								   ,"usermodified"=>$currentuser,  "finalgrade"=>$point
								   ,"overridden"=>1693481396,"timemodified"=>1693481396
							);
						$DB->update_record('grade_grades', $gar);
						continue;
					}else{
						echo " <br><font color=\"red\"> can NOT update grade </font><br>";
						die ;
					}
				} else{
						echo " <br><font color=\"green\"> grade already the same </font><br>";
						break;
					
				}
			}
			 
	} //eo. for ($nim=$nim_start;  $nim<=$nim_end; $nim++){
	
	/**/
	
	$courseFullNameCol+=$courseInterval;
}	
echo "finish...<br>";