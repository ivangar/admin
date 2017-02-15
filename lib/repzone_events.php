<?php 

require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/FirePHP.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/fb.php');
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/programs/CCC_Symposium/rep_zone/config/env_constants.php");
require_once dirname(__FILE__) . '/../../Classes/PHPExcel.php';

if(!isset($_SESSION))
{
session_start();
}  

function createDatabaseConnection()
{
    try {
        $con = new PDO('mysql:host=' . HOST . ';dbname=' . DATABASE . ';charset='. ENCODING, USER, PWD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

    } catch (PDOException $e) {
        $con = "PDO database connection problem: " . $e->getMessage();
    } catch (Exception $e) {
        $con = "General problem: " . $e->getMessage();
    }

    $con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $con;
}

class Repzone{

	// define properties
    public $con;	//db connection
    public $events = array(); //String containing html talbe of posts

	function Set_Repzone(){
    	$this->con = createDatabaseConnection(); // get database connection credentials
	}

	function Get_Events(){

		//Limit the result object from start row passed (0, 10, 20, 30 ...) in chunks of 10

		$sql = 'SELECT * FROM events';
        $query = $this->con->prepare($sql);
        $query->execute();

        while($event = $query->fetch(PDO::FETCH_ASSOC) ){
            array_push($this->events, $event);
        }

        return true;

	}

	function Sanitize($str,$remove_nl=true)
	{
	    if($remove_nl)
	    {
	        $injections = array('/(\n+)/i',
	            '/(\r+)/i',
	            '/(\t+)/i',
	            '/(%0A+)/i',
	            '/(%0D+)/i',
	            '/(%08+)/i',
	            '/(%09+)/i'
	            );
	        $str = preg_replace($injections,'',$str);
	    }

	    return $str;
	}  

	function CreateExcelReport(){
		// Create new PHPExcel object

		$var = 'Im in the excel report fucntion';
		$this->Get_Events();
		print_r($this->events);
		
		/*
		$objPHPExcel = new PHPExcel();

		$rows = array();
		$row_count = 2;  //counter to loop through the Excel spreadsheet rows

		$objPHPExcel->getProperties()->setCreator("dxLink")
							 ->setLastModifiedBy("dxLink")
							 ->setTitle("dxLink Repzone Events")
							 ->setSubject("Repzone Events")
							 ->setDescription("Gets all the events from the Repzone database")
							 ->setKeywords("dxLink Discussion Forum Topics Comments")
							 ->setCategory("Test result file");

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'User')
				            ->setCellValue('B1', 'Topic Id')
				            ->setCellValue('C1', 'Province')
				            ->setCellValue('D1', 'Profession')
				            ->setCellValue('E1', 'Comment')
				            ->setCellValue('F1', 'Date Posted')
				            ->setCellValue('G1', 'Topic');

		$sql = "SELECT posts.post_id, posts.topic_id, posts.province, posts.profession, posts.message, posts.date_posted, doctors.first_name, doctors.last_name FROM posts, doctors WHERE doctors.doctor_id = posts.doctor_id ORDER BY posts.date_posted DESC";
        $query = $this->con->prepare($sql);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	$fisrt_name = $result_row['first_name'];
        	$last_name = $result_row['last_name'];
        	$doctor = $fisrt_name . ' ' . $last_name;
        	$post_id = $result_row['post_id'];  
			$topic_id = $result_row['topic_id'];  
			$province = $result_row['province']; 
			$profession = $result_row['profession']; 
			$message = $result_row['message'];  
			$date_posted = $result_row['date_posted'];
			$topic = $this->Get_Topic($topic_id);
			array_push($rows, array($doctor, $topic_id, $province, $profession, $message, $date_posted, $topic));
        }
        
        
        foreach ($rows as $row => $column) {

			$objPHPExcel->getActiveSheet()->setCellValue('A' . $row_count, $column[0])
							            ->setCellValue('B' . $row_count, $column[1])
							            ->setCellValue('C' . $row_count, $column[2])
							            ->setCellValue('D' . $row_count, $column[3])
							            ->setCellValue('E' . $row_count, $column[4])
							            ->setCellValue('F' . $row_count, $column[5])
							            ->setCellValue('G' . $row_count, $column[6]);

			$objPHPExcel->getActiveSheet()->getRowDimension($row_count)->setRowHeight(50); 

		    $row_count++;		
		}	

		//Set widths of all columns
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(140);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(150);

		//Fill design settings for first heading row
		$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
		$objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
		$objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
		$objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFont()->setSize(16);
		$objPHPExcel->getActiveSheet()->freezePane('A2');

		//Align all cells
		$objPHPExcel->getActiveSheet()->getStyle('A1:G' . $row_count)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('A1:G' . $row_count)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('../Reports/dxLink_Forum_report.xlsx');
		
		echo 'exported';
		return true;
		*/
	}

} //ends class

if(isset($_COOKIE['remember_dxLinkAdmin']) && !empty($_COOKIE['remember_dxLinkAdmin']) ){
  	$_SESSION['user_is_logged_in'] = true;
}

if (isset($_REQUEST['action'])) {
	
	$action = $_REQUEST['action'];
	
	//Instance of Repzone class
	$repzone_events = new Repzone();

    $repzone_events->Set_Repzone();  //This should include topic id to be sent
	
	switch ($action) {
		case 'exportRepzone':
			$repzone_events->CreateExcelReport();
			break;
		default;
			break;
	}
	

} else {
	return false;
}
?>