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

		$sql = "SELECT events.event_date, events.location, reps.rep_name, moderators.mod_name FROM events, reps, moderators WHERE events.rep_id = reps.rep_id AND events.moderator_id = moderators.moderator_id ORDER BY events.event_date ASC";
        $query = $this->con->prepare($sql);
        $query->execute();

        while($event = $query->fetch(PDO::FETCH_ASSOC) ){
            array_push($this->events, $event);
        }

        return true;
	}

	function CreateExcelReport(){
		
		$objPHPExcel = new PHPExcel();  // Create new PHPExcel object
		$row_count = 2;  //counter to loop through the Excel spreadsheet rows

		$objPHPExcel->getProperties()->setCreator("dxLink")
							 ->setLastModifiedBy("dxLink")
							 ->setTitle("dxLink Repzone Events")
							 ->setSubject("Repzone Events")
							 ->setDescription("Gets all the events from the Repzone database")
							 ->setKeywords("dxLink Discussion Forum Topics Comments")
							 ->setCategory("Test result file");

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Event date')
				            ->setCellValue('B1', 'Event location')
				            ->setCellValue('C1', 'Rep name')
				            ->setCellValue('D1', 'Speaker name');
	
        $this->Get_Events(); //Get the events query

	    foreach ($this->events as $index => $event) {
			$objPHPExcel->getActiveSheet()->setCellValue('A' . $row_count, $event["event_date"])
							            ->setCellValue('B' . $row_count, $event["location"])
							            ->setCellValue('C' . $row_count, $event["rep_name"])
							            ->setCellValue('D' . $row_count, $event["mod_name"]);

			$objPHPExcel->getActiveSheet()->getRowDimension($row_count)->setRowHeight(50); 

		    $row_count++;	
		}	

		//Set widths of all columns
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(55);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(100);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(55);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(55);

		//Fill design settings for first heading row
		$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
		$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
		$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
		$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->getFont()->setSize(16);
		$objPHPExcel->getActiveSheet()->freezePane('A2');

		//Align all cells
		$objPHPExcel->getActiveSheet()->getStyle('A1:D' . $row_count)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('A1:D' . $row_count)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('../Reports/dxLink_repzone.xlsx');
		
		echo 'exported';
		return true;

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