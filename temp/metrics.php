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

class Metrics{

	// define properties

    private $con = null;	//db connection
    private $data;  //data passed from POST
    private $total_correct_answers = array();
    private $program_section_id;
	private $questions = array();
	private $total_no_evaluations;
	private $programs = array();
	public $charts = array();
	public $output;

	public function __construct()
    {
       	$this->con = createDatabaseConnection(); // get database connection credentials
       	$this->Get_programs();
    }

    public function Get_programs(){
		$this->Get_Program_TestIds();
    }

	public function Set_Results(){

		foreach ($this->programs as $index => $array) {

			foreach ($array as $program => $data) {

		    	if(!empty($program) && !empty($data)){
		    		
		    		$program_array = array();

		    		foreach ($data as $section_id => $section_name) {

		    			$this->program_section_id = $section_id; 
						$this->Set_Test_Question_Ids();
						$this->Set_Total_Evaluations();

						//If nobody has done any test then skip this program
						if($this->total_no_evaluations <= 0){
							$this->Clean_data();
							continue 2;
						}

						$this->Get_All_Data();
						$results = $this->Get_Results();
						$program_array[] = $results;
						$this->Clean_data();
						
		    		}
					
					$program_array['participants'] = $this->total_no_evaluations;
					$program_array['program_title'] = $program;

		    		array_push($this->charts, $program_array);
		    		$program_count = count($this->charts);
		    		$this->Generate_Results($program_array['program_title'], $program_array['participants'], $program_count);
		    	
		    	}
		    	
		    }
		    
		}

		$this->Close_DB_connection();

		return $this->charts;

	}

	private function Clean_data(){
		$this->total_correct_answers = array();
		$this->questions = array();
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

	public function Get_Total_Evaluations(){
        	
	    return $this->total_no_evaluations;
	}

	/* GET ALL TEST SECTIONS COMPLETED FOR ALL PROGRAMS */
	public function Get_Program_TestIds(){
        
		$sql = 'SELECT program_id, program_title FROM programs ORDER BY program_id DESC';
        $query = $this->con->prepare($sql);
        $query->execute();

        while($program = $query->fetch(PDO::FETCH_ASSOC) ){

        	$title = $program['program_title'];
			$program_title = array();	//This will be the program title index in the global array
			$temp_section_array = array();   //This will be the section array in the global array

        	$sql2 = 'SELECT program_section_id, program_section_name FROM program_sections WHERE program_id = :program_id AND program_section_type = :section_type ORDER BY program_section_id DESC';
        	$query2 = $this->con->prepare($sql2);
        	$query2->bindValue(':program_id', $program['program_id']);
        	$query2->bindValue(':section_type', 'Test form');
        	$query2->execute();
			
        	while($section = $query2->fetch(PDO::FETCH_ASSOC) ){
        		$section_id = $section['program_section_id'];
        		$program_section_name = $section['program_section_name'];
	       	 	$temp_section_array[$section_id] = $program_section_name; 
	       	 } 
	       	 	
	       	$program_title[$title] = $temp_section_array;
	       	array_push($this->programs, $program_title);	
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
		        		array_push($this->total_correct_answers, $total['answers']);  
			        	return true;
		        }
 
        }

		return false;	
	}

	private function Generate_Results($program_title, $participants, $program_count){
		$this->output .= "<div class='row'>\n
                  			<h2 class='sub-header section'> {$program_title} ( total participants {$participants} )</h2>\n
              			</div>\n
              			<div class='row chart_row' >
                			<div class='col-sm-6 col-md-7'>
                 		 		<canvas id='canvas{$program_count}' height='200' width='400'></canvas>
                			</div>
                		<div class='col-sm-6 col-md-3'>
                  			<div id='lineLegend{$program_count}'></div>
                		</div>
              			</div>";
	}

	private function Get_Results(){

		if(isset($this->total_correct_answers) && !empty($this->total_correct_answers)) return $this->total_correct_answers;

		else return false;
	}

	//this public function gets all data for the table
	private function Get_All_Data(){
		$question = 1;

		//loop through each question to see correct
		foreach ($this->questions as $questions => $q_id) {	
			$this->Get_No_Correct_Answers($q_id);
		    $question++;
		}

		return true;
	}

} //ends class

?>