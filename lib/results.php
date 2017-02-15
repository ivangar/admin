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

function createDatabaseConnection()
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

class Results{

	// define properties

    private $con = null;	//db connection
    private $data;  //data passed from POST
    private $q_id;
    private $question;
    private $total_correct_answers;
    private $total_incorrect_answers;
    private $total_correct_answers_ratio;
    private $total_incorrect_answers_ratio;
    private $total_no_evaluations;
    private $program_section_id;
    private $program_section_name;
    private $program_sections = array();
	private $questions = array();
	private $average = array();
	private $tables = '';
	private $objPHPExcel = NULL;

	public function __construct()
    {
       	$this->con = createDatabaseConnection(); // get database connection credentials
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
				$this->Get_All_Data();
				$data = $this->Get_Results();
				$average = $this->Get_Average();
				$this->Generate_data_table($section_name, $data, $average);

				$this->Clean_data();
			}

			return true;
			$this->Close_DB_connection();
		}

		elseif(!empty($this->program_section_id)){
				$section_name = $this->program_section_name;
				$this->Set_Total_Evaluations();
				$this->Set_Test_Question_Ids();	
				$this->Get_All_Data();
				$result = $this->Get_Results();
				$average = $this->Get_Average();
				$this->Generate_data_table($section_name, $result, $average);
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

    		//Set TITLE SECTION
    		$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $section_name);
    		$this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(30);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFEAF0AA');
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':F' . $row)->getFont()->setSize(18);
			$this->objPHPExcel->getActiveSheet()->mergeCells('A' . $row . ':F' . $row);

    		//Increment row to column titles
    		$row++;

    		//Set COLUMN TITLES FOR THIS SECTION
			$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, 'Question #')
		            ->setCellValue('B' . $row, 'Correct Answers')
		            ->setCellValue('C' . $row, 'Percentage Ratio')
		            ->setCellValue('D' . $row, 'Incorrect Answers')
		            ->setCellValue('E' . $row, 'Percentage Ratio')
		            ->setCellValue('F' . $row, 'Total');

	   		//Fill design settings for first heading row
			$this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
			$this->objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':F' . $row)->getFont()->setSize(15);

    		$this->Set_Program_SectionId($section_id);
	    	$this->Set_Total_Evaluations();
			$this->Set_Test_Question_Ids();
			
			//increment row to first column counter
		    $row++;

		    //Get all data related to each question of this section
			$this->Get_Excel_Data($row);

			//Get Average of this section
 			$this->Get_Excel_Average($row);

	        $this->objPHPExcel->getActiveSheet()->getStyle('C' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
			$this->objPHPExcel->getActiveSheet()->getStyle('E' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

	        //increment row to title row again
			$row++;

			$this->Clean_data();
		}

		$this->Close_DB_connection();
	}

	private function Generate_data_table($title, $data, $average){
		
		$this->tables .= "<h2 class='sub-header section'> $title </h2>
              				<div class='table-responsive' >
			                  <table class='table table-striped table-hover table-bordered sortable'>
			                    <thead>
			                      <tr>
			                        <th >Question #</th>
			                        <th >Correct Answers</th>
			                        <th >Percentage Ratio</th>
			                        <th >Incorrect Answers</th>
			                        <th >Percentage Ratio</th>
			                        <th >Total</th>
			                      </tr>
			                    </thead>
			                    <tbody > {$data}
			                    </tbody>
			                    <tfoot> {$average}
			                    </tfoot>
			                  </table> 
        					</div>";
	} 

	private function Clean_data(){
		$this->questions = array();
		$this->average = array();
		$this->posts = '';
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
        	
		$sql = 'SELECT COUNT(*) AS evaluations FROM results WHERE program_section_id = :program_section_id';
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
	public function Get_Program_TestIds(){
        
		$sql = 'SELECT DISTINCT program_section_id FROM results ORDER BY program_section_id DESC';
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
        
		$sql = 'SELECT program_section_id, program_section_name FROM program_sections WHERE program_section_name LIKE :program_section_name';
        $query = $this->con->prepare($sql);
        $query->bindValue(':program_section_name', '%'.$qry.'%');
        $query->execute();


        while($section_id = $query->fetch(PDO::FETCH_ASSOC) ){		
				$this->Set_Program_SectionId($section_id['program_section_id']);
				$this->program_section_name = $section_id['program_section_name'];
        }

		return true;
	}

	private function Set_Test_Question_Ids(){
        
		$sql = 'SELECT question_id FROM program_sec_test_content WHERE program_section_id = :program_section_id ';
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

	private function Get_No_Correct_Answers($q_id){

		$sql = 'SELECT answer FROM valid_answers WHERE question_id = :question_id LIMIT 1';
        $query = $this->con->prepare($sql);
        $query->bindValue(':question_id', $q_id);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$valid_answer = $result_row['answer'];  

	        	$sql = 'SELECT COUNT(*) AS answers FROM doctor_answers WHERE question_id = :question_id AND program_section_id = :program_section_id AND doctor_answer = :valid_answer';
		        $query = $this->con->prepare($sql);
			    $query->execute(array(':question_id'=>$q_id,
		 	                  ':program_section_id'=>$this->program_section_id,
							  ':valid_answer'=>$valid_answer
							  ));

		        while($total = $query->fetch(PDO::FETCH_ASSOC) ){
			        	$this->total_correct_answers = $total['answers'];  
			        	return true;
		        }
 
        }

		return false;	
	}

	private function Get_No_Incorrect_Answers(){

		$this->total_incorrect_answers = $this->total_no_evaluations - $this->total_correct_answers;	
		return true;
	}

	private function Calculate_Average(){
		$this->average[0] = $this->average[0] + $this->total_correct_answers;
		$this->average[1] = $this->average[1] + $this->total_correct_answers_ratio;
		$this->average[2] = $this->average[2] + $this->total_incorrect_answers;
		$this->average[3] = $this->average[3] + $this->total_incorrect_answers_ratio;

		return true;
	}

	private function Get_Average(){

		//Divide all numbers by total and round to integer
		$correct = round($this->average[0]/6);	
		$correct_ratio = round($this->average[1]/6);
		$incorrect = round($this->average[2]/6);
		$incorrect_ratio = round($this->average[3]/6);

		return "<tr >\n<td >Average</td>\n<td>{$correct} </td>\n<td>{$correct_ratio} %</td>\n<td>{$incorrect}</td>\n<td>{$incorrect_ratio} %</td>\n<td></td>\n
				</tr>\n";
	}

	private function Get_Excel_Average($row){

		$correct = round($this->average[0]/6);	
		$correct_ratio = round($this->average[1]/6);
		$incorrect = round($this->average[2]/6);
		$incorrect_ratio = round($this->average[3]/6);

		$this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(27);

		//Set AVERAGE VALUES ROW
		$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, 'AVERAGE')
            ->setCellValue('B' . $row, $correct)
            ->setCellValue('C' . $row, ($correct_ratio / 100))
            ->setCellValue('D' . $row, $incorrect)
            ->setCellValue('E' . $row, ($incorrect_ratio / 100));

        return true;
	}

	private function Get_Percentage_Correct_Answers(){

		$percent = ($this->total_correct_answers/$this->total_no_evaluations) * 100;
		$this->total_correct_answers_ratio = intval($percent);
		return true;
	}

	private function Get_Percentage_Incorrect_Answers(){

		$percent = ($this->total_incorrect_answers/$this->total_no_evaluations) * 100;
		$this->total_incorrect_answers_ratio = intval($percent);
		return true;
	}

	private function Generate_Results($question){

		$this->posts .= "<tr >\n
							<td style='cursor:pointer;color: #428bca;'><span class='topic' title=\"$this->question \" >Question $question</span ></td>\n
							<td>$this->total_correct_answers</td>\n
							<td>$this->total_correct_answers_ratio %</td>\n
							<td>$this->total_incorrect_answers</td>\n
							<td>$this->total_incorrect_answers_ratio %</td>\n
							<td>$this->total_no_evaluations</td>\n
						</tr>\n
							 ";
	}

	private function Get_Results(){

		if(isset($this->posts) && !empty($this->posts)) return $this->posts;

		else return false;
	}

	//this public function gets all data for the table
	private function Get_All_Data(){

		$question = 1;

		//loop through each question to see correct
		foreach ($this->questions as $questions => $q_id) {	
			$this->Get_No_Correct_Answers($q_id);
			$this->Get_No_Incorrect_Answers();
			$this->Get_Percentage_Correct_Answers();
		    $this->Get_Percentage_Incorrect_Answers();
		    $this->Get_Question($q_id);
		    $this->Generate_Results($question);
		    $this->Calculate_Average();
		    $question++;
		}

		return true;
	}

  	//Get Data for every Column
	private function Get_Excel_Data(&$row){
		//Question 1 counter
		$question = 1;

		//loop through each question to see correct
		foreach ($this->questions as $questions => $q_id) {	

			$this->Get_No_Correct_Answers($q_id);
			$this->Get_No_Incorrect_Answers();
			$this->Get_Percentage_Correct_Answers();
		    $this->Get_Percentage_Incorrect_Answers();

		    $this->objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);

			//Set COLUMN VALUES ROW
			$this->objPHPExcel->getActiveSheet()->setCellValue('A' . $row, 'Question ' . $question)
	            ->setCellValue('B' . $row, $this->total_correct_answers)
	            ->setCellValue('C' . $row, ($this->total_correct_answers_ratio/100))
	            ->setCellValue('D' . $row, $this->total_incorrect_answers)
	            ->setCellValue('E' . $row, ($this->total_incorrect_answers_ratio/100))
	            ->setCellValue('F' . $row, $this->total_no_evaluations);

			$this->objPHPExcel->getActiveSheet()->getStyle('C' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
			$this->objPHPExcel->getActiveSheet()->getStyle('E' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

			//increment row to next question
		    $row++;

		    $this->Calculate_Average();
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
							 ->setTitle("dxLink Program Results")
							 ->setSubject("dxLink Program Results")
							 ->setDescription("Generates all data from dxLink Pre-test and Post-test")
							 ->setKeywords("dxLink Discussion Program Test Results")
							 ->setCategory("Test result file");

		$this->objPHPExcel->setActiveSheetIndex(0);

		//Set all data to be used for the spreadsheet
		$this->Set_Excel_Results($row);

		//Align all cells and set width
		$this->objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(22);
		$this->objPHPExcel->getActiveSheet()->getStyle('A1:F' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$this->objPHPExcel->getActiveSheet()->getStyle('A1:F' . $row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

		$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
		$objWriter->save('../Reports/dxLink_program-tests_report.xlsx');

		echo 'exported';
		return true;
	}

} //ends class

if(isset($_POST['search_q']))
{
    // run the application
    $qry = trim($_POST['search_q']);

    $program_results = new Results();
	$program_results->Get_Program_ID($qry);

	if($program_results->Set_Results()){
		$_SESSION['result'] = $program_results->Print_result();
	}

	else{$_SESSION['error'] = 'We are sorry, your query does not match any Program test. Please try again.';}
	

	header("Location: ../program_tests.php?search=result"); 
}

?>