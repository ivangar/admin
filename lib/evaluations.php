<?php 

require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/FirePHP.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/fb.php');
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/connect_db.php");
require_once dirname(__FILE__) . '/../../Classes/PHPExcel.php';
require_once dirname(__FILE__) . '/../../Classes/PHPExcel/Cell/AdvancedValueBinder.php';

if(!isset($_SESSION))
{
session_start();
}  

function createDBConnection()
{
    try {
        $con = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset='. DB_ENCODING, DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

    } catch (PDOException $e) {
        $con = "PDO database connection problem: " . $e->getMessage();
    } catch (Exception $e) {
        $con = "General problem: " . $e->getMessage();
    }

    return $con;
}

class Evaluations{

	// define properties
    private $data;  //data passed from POST
	private $objPHPExcel = NULL;
 	private $con = null;  //db connection
	private $choices = array(5, 4, 3, 2, 1);	//Opinion scale (Agree, Disagre, ...)	
	private $french_choices = array('A', 'B', 'C', 'D'); //French evaluation scale (Agree, Disagre, ...)
	private $french_eval = 0;	//boolean flag to indicate that the iteration is for the French Evlation 
	private $labels = array();
	private $evaluations = array();
	private $question;
  	private $program_sections = array();   //This holds the program sections: array(array([0]=>'Evaluation Id', [1]=>'Evaluation name') ...)
  	private $program_section_id;
  	private $program_section_name;
	private $total_no_evaluations;
	private $questions = array();
	private $tables = '';
	private $rows = '';

	public function __construct()
    {
       	$this->con = createDBConnection(); // get database connection credentials

    }

	public function Set_Results(){

		if( !empty($this->program_sections) ){
			foreach($this->program_sections as $section => $section_info)
    		{	
	    		$section_id = $section_info[0];
	    		$section_name = $section_info[1];
	    		$this->Set_Program_SectionId($section_id);
		    	$this->Set_Total_Evaluations();
				$this->Set_Test_Question_Ids();	
				$this->Get_Question_labels();
				$this->Get_All_Data();
				$data = $this->Get_Results();
				$this->Generate_data_table($section_name, $data);
				$this->Clean_data();
			}

			$this->Close_DB_connection();
			return true;
			
		}
		 
		elseif(!empty($this->program_section_id)){
				$section_name = $this->program_section_name;
				$this->Set_Total_Evaluations();
				$this->Set_Test_Question_Ids();	
				$this->Get_Question_labels();
				$this->Get_All_Data();
				$result = $this->Get_Results(); 
				$this->Generate_data_table($section_name, $result);
				$this->Clean_data();

				$this->Close_DB_connection();
				return true;
		}


		else {
			$this->Close_DB_connection();
			return false;
		}

	}
    
    //Set the excel object with the data to be printed
	public function Set_Excel_Results(&$row){
    	
		foreach($this->program_sections as $section => $section_info)
    	{	
    		$section_id = $section_info[0];
    		$section_name = $section_info[1];
    		$this->Set_Program_SectionId($section_id);
    		$this->Set_Total_Evaluations();
			$this->Set_Test_Question_Ids();
    		$this->Get_Question_labels();
    		$choices = ($this->french_eval ? $this->french_choices : $this->choices);
    		$columns = array('B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N');
    		$last_cell = $columns[sizeof($this->labels)];
    		

    		//Set TITLE SECTION
    		$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $section_name);
    		$this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(30);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':' . $last_cell . $row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFC2EDC9');
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':' . $last_cell . $row)->getFont()->setSize(18);
			$this->objPHPExcel->getActiveSheet()->mergeCells('A' . $row . ':' . $last_cell . $row);

    		//Increment row to column titles
    		$row++;

    		//Set COLUMN TITLES FOR THIS SECTION
			$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, 'Statements');

			for($x=0; $x <= sizeof($choices); $x++) {

				if(sizeof($this->labels) == $x) { 
					$this->objPHPExcel->getActiveSheet()->setCellValue($columns[$x] . $row, 'Total'); 
					$last_cell = $columns[$x]; 
				}

				else
					$this->objPHPExcel->getActiveSheet()->setCellValue($columns[$x] . $row, $this->labels[$x]);

			}

	   		//Fill design settings for first heading row
			$this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':' . $last_cell . $row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFB7B8B4');
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':' . $last_cell . $row)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':' . $last_cell . $row)->getFont()->setSize(15);
			$this->objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(45);

    		
			//increment row to first column counter
		    $row++;

		    //Get all data related to each question of this section
			$this->Get_Excel_Data($row);

	        //increment row to title row again
			$row++;

			$this->Clean_data();
		}

		$this->Close_DB_connection();
	}

	private function Generate_data_table($title, $data){
		
		$theader = '';

		//Generate table headers
		for($x=0; $x < sizeof($this->labels); $x++) {
			$theader .= "<th>" . $this->labels[$x] . "</th>";
		}

		$this->tables .= "<h2 class='sub-header section'> $title </h2>
              				<div class='table-responsive' >
			                  <table class='table table-striped table-hover table-bordered sortable'>
			                    <thead>
			                      <tr>
			                        <th>Statements</th>" . $theader . "<th>Total</th>
			                      </tr>
			                    </thead>
			                    <tbody > {$data}
			                    </tbody>
			                  </table> 
        					</div>";
	} 

	private function Clean_data(){
		$this->questions = array();
		$this->rows = '';
		$this->evaluations = array();
		$this->labels = array();
		$this->french_eval = 0;
	}

	public function Print_result(){

		return $this->tables;
	}

	private function Close_DB_connection(){
    	$this->con = null;
	}

	public function Set_Program_SectionId($program_section_id){
    	$this->program_section_id = $program_section_id;  
	}

	public function Set_data($qry){
    	$this->data = $qry;  
	}

	private function Set_Total_Evaluations(){
        	
		$sql = 'SELECT COUNT(*) AS evaluations FROM evaluations WHERE program_section_id = :program_section_id';
        $query = $this->con->prepare($sql);
        $query->bindValue(':program_section_id', $this->program_section_id);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$this->total_no_evaluations = $result_row['evaluations'];  
	        	return true;
        }

		return false;
	}


	/* GET ALL TEST SECTIONS COMPLETED FOR ALL PROGRAMS */
	public function Get_Eval_Ids(){
        
		$sql = 'SELECT DISTINCT program_section_id FROM evaluations ORDER BY program_section_id DESC';
        $query = $this->con->prepare($sql);
        $query->execute();

        while($section_id = $query->fetch(PDO::FETCH_ASSOC) ){

        	$sql2 = 'SELECT program_section_name FROM program_sections WHERE program_section_id = :program_section_id';
        	$query2 = $this->con->prepare($sql2);
        	$query2->bindValue(':program_section_id', $section_id['program_section_id']);
        	$query2->execute();

        	while($section_name = $query2->fetch(PDO::FETCH_ASSOC) ){
	       	 	array_push($this->program_sections, array($section_id['program_section_id'], $section_name['program_section_name']) ); } 
        }
		
		return true;
	}

	public function Get_Program_ID($qry){
        
		$sql = 'SELECT program_section_id, program_section_name FROM program_sections WHERE program_section_name LIKE :program_section_name AND program_section_type = :evaluation_form';
        $query = $this->con->prepare($sql);
        $query->bindValue(':program_section_name', '%'.$qry.'%');
        $query->bindValue(':evaluation_form', 'Evaluation form');
        $query->execute();


        while($section = $query->fetch(PDO::FETCH_ASSOC) ){		
				$this->Set_Program_SectionId($section['program_section_id']);
				$this->program_section_name = $section['program_section_name'];
        }

		return true;
	}

	private function Set_Test_Question_Ids(){
        
		$sql = 'SELECT DISTINCT question_id FROM doctor_answers WHERE program_section_id = :program_section_id AND answer_choice IS NOT NULL';
        $query = $this->con->prepare($sql);
        $query->bindValue(':program_section_id', $this->program_section_id);
        $query->execute();
 
        while($result_row = $query->fetch() ){
	        	array_push($this->questions, $result_row['question_id']);  
        }

        if(empty($this->questions))
			return false;

		else return true;
	}

	//Get question
	private function Get_Question($q_id){

		$sql = 'SELECT question FROM questions WHERE question_id = :question_id';
        $query = $this->con->prepare($sql);
        $query->bindValue(':question_id', $q_id);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$this->question = $result_row['question'];  
	        	return true;
        }

		return false;
	}

	//Get question
	private function Get_Question_labels(){

		for($x=0; $x < sizeof($this->choices); $x++) {

		   	$sql = 'SELECT DISTINCT doctor_answer FROM doctor_answers WHERE program_section_id = :program_section_id AND answer_choice = :choice';
	        $query = $this->con->prepare($sql);
		    $query->execute(array(':program_section_id'=>$this->program_section_id,
	 	                  		  ':choice'=>$this->choices[$x]));

	        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
		        array_push($this->labels, $result_row['doctor_answer']);  
	        }
		}

		if(empty($this->labels)){
			
			$this->french_eval = 1;

			for($x=0; $x < sizeof($this->french_choices); $x++) {

			   	$sql = 'SELECT DISTINCT doctor_answer FROM doctor_answers WHERE program_section_id = :program_section_id AND answer_choice = :choice';
		        $query = $this->con->prepare($sql);
			    $query->execute(array(':program_section_id'=>$this->program_section_id,
		 	                  		  ':choice'=>$this->french_choices[$x]));

		        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
			        array_push($this->labels, $result_row['doctor_answer']);  
		        }
			}
		}

		if(empty($this->labels)){return false;}

		return true;
	}

	//Get statement evaluations per each choice
	private function Get_Statement_Eval($q_id){
		
		$choices = ($this->french_eval ? $this->french_choices : $this->choices);

		for($x=0; $x < sizeof($choices); $x++) {

  	    	$sql = 'SELECT COUNT(*) AS answers FROM doctor_answers WHERE question_id = :question_id AND program_section_id = :program_section_id AND answer_choice = :choice';
	        $query = $this->con->prepare($sql);
		    $query->execute(array(':question_id'=>$q_id,
	 	                  ':program_section_id'=>$this->program_section_id,
	 	                  ':choice'=>$choices[$x]
						  ));

	        while($total = $query->fetch(PDO::FETCH_ASSOC) ){
		        	array_push($this->evaluations, $total['answers']);
	        }
		}


		return true;
	}

	private function Generate_Results($number){

		$td = '';

		//Generate table data cells
		for($x=0; $x < sizeof($this->labels); $x++) {
			$td .= "\n<td>{$this->evaluations[$x]} %</td>\n";
		}

		$this->rows .= "<tr >\n<td style='cursor:pointer;color: #428bca;'><span class='topic' title=\"{$this->question} \" >Statement {$number}</span ></td>" . $td . "<td>{$this->total_no_evaluations}</td>\n
						</tr>\n";
	}

	private function Get_Results(){

		if(isset($this->rows) && !empty($this->rows)) return $this->rows;

		else return false;
	}

	//this public function gets all data for the table
	private function Get_All_Data(){
		
		$counter = 1;

		//loop through each question to see correct
		foreach ($this->questions as $questions => $q_id) {	
			$this->Get_Question($q_id);
			$this->Get_Statement_Eval($q_id);
			$this->Get_Percentage();
			$this->Generate_Results($counter);
		    $this->evaluations = array();
		    $counter++;
		}

		return true;
	}

	private function Get_Percentage(){

		$choices = ($this->french_eval ? $this->french_choices : $this->choices);

		for($x=0; $x < sizeof($choices); $x++) {

			$percent = ($this->evaluations[$x]/$this->total_no_evaluations) * 100;
			$this->evaluations[$x] = round($percent, 1);
		}

		return true;
	}

  	//Get Data for every Column
	private function Get_Excel_Data(&$row){
		//Question 1 counter
		$question = 1;
		$choices = ($this->french_eval ? $this->french_choices : $this->choices);
    	$columns = array('B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N');
    	$last_cell = '';

		//loop through each question to see correct
		foreach ($this->questions as $questions => $q_id) {	

			$this->Get_Question($q_id);
			$this->Get_Statement_Eval($q_id);
			$this->Get_Percentage();
 
		    $this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getAlignment()->setWrapText(true);

			//Set COLUMN VALUES ROW
			$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $this->question);

			for($x=0; $x <= sizeof($this->labels); $x++) {

				if(sizeof($this->labels) == $x) { 
					$this->objPHPExcel->getActiveSheet()->setCellValue($columns[$x] . $row, $this->total_no_evaluations); 
					$last_cell = $columns[$x - 1]; 
				}

				else
					$this->objPHPExcel->getActiveSheet()->setCellValue($columns[$x] . $row, ($this->evaluations[$x]/100));

			}

			$this->objPHPExcel->getActiveSheet()->getStyle('B' . $row . ':' . $last_cell . $row)->getNumberFormat()->setFormatCode('0.0%');

	        $this->evaluations = array(); //Clean evaluations array for the next set

			//increment row to next question
		    $row++;
 
		    $question++;

		}

		return true;
	}

	public function CreateExcelReport(){
	
		// Set value binder
		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		$this->objPHPExcel = new PHPExcel();

		$row = 1;  //counter to loop through the Excel spreadsheet rows

		$this->objPHPExcel->getProperties()->setCreator("dxLink")
							 ->setLastModifiedBy("dxLink")
							 ->setTitle("dxLink Program Evaluations")
							 ->setSubject("dxLink Program Evaluations")
							 ->setDescription("Generates all data from dxLink accredited program evaluations")
							 ->setKeywords("dxLink Discussion Program Test Evaluations")
							 ->setCategory("Evaluations");

		$this->objPHPExcel->setActiveSheetIndex(0);

		//Set all data to be used for the spreadsheet
		$this->Set_Excel_Results($row);
		

		//Align all cells and set width
		$this->objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(22);
		$this->objPHPExcel->getActiveSheet()->getStyle('A1:Z' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$this->objPHPExcel->getActiveSheet()->getStyle('A1:Z' . $row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

		$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
		$objWriter->save('../Reports/dxLink_program-evaluation_report.xlsx');

		echo 'exported';
		return true;
	}

} //ends class

if(isset($_COOKIE['remember_dxLinkAdmin']) && !empty($_COOKIE['remember_dxLinkAdmin']) ){
  	$_SESSION['user_is_logged_in'] = true;
}

if(isset($_POST['search_q']))
{

    // run the application
    $qry = trim($_POST['search_q']);

    $program_evaluations = new Evaluations($qry);
    $program_evaluations->Get_Program_ID($qry);
	$program_evaluations->Set_data($qry);  //Set the query data

	if($program_evaluations->Set_Results()){
		$_SESSION['result'] = $program_evaluations->Print_result();
	}

	else{$_SESSION['error'] = 'We are sorry, your query does not match any record. Please try again.';}
	
    header("Location: ../program_evaluations.php?search=result"); 
}

?>