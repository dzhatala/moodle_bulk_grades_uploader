<head>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
</head>

<H1> Grades Uploader </H1>
<?php 
require '/var/www/composer/vendor/autoload.php';//absolute values of composer installation...


//https://stackoverflow.com/questions/3302857/algorithm-to-get-the-excel-like-column-name-of-a-number
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


use PhpOffice\PhpSpreadsheet\IOFactory;


$inputFileType = 'Xls';
$inputFileName='/var/www/composer/sem2grades.xls';

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
	echo "worksheet ".$sheetIndex ." =>" .$sheetName."<br>";
}


$spreadsheet->setActiveSheetIndex(0);

$courseNumber=11;
// $courseNumber=2;
$courseInterval=3; //column used by each course
$courseFullNameRow=8;
$courseFullNameCol=3; //'D'
$courseLetterOffset=2;
$coursePointOffset=1;

$course_prefix ="22_23";
for ($c=1;$c<=$courseNumber;$c++){
	
	
	
	//1. get course code
	
	//2. cek item exist ? if not ... regrade ..
	
	$coord=getNameFromNumber($courseFullNameCol).$courseFullNameRow;
	echo $coord."<br>";		
	$cell=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
	if($cell==false) die ("error get course name");
	echo "Course ".$cell."<br>\n";

	$nim_col='C';
	$nim_start=10;
	$nim_end=96;

	for ($nim=$nim_start;  $nim<=$nim_end; $nim++){
			$coord=$nim_col.$nim;
			// echo $nim.": getting ".$coord." => ";
			 $NIM=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 if($cell==false) die ("error get course name");
			 $point_col=$courseFullNameCol+$coursePointOffset;
			 $coord=getNameFromNumber($point_col).$nim;
			 $point=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 
			 $letter_col=$courseFullNameCol+$courseLetterOffset;
			 $coord=getNameFromNumber($letter_col).$nim;
			 if($cell==false) die ("error get course name");
			 $letter=$spreadsheet->getActiveSheet()->getCell($coord); // reads D12 cell from second sheet
			 if($cell){
				echo $NIM." ".$point." ".$letter."<BR>";
			 }else {
					xlsx_err();
			 }
			 
	}

	$courseFullNameCol+=$courseInterval;
}	
echo "finish...<br>";