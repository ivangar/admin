<?php 

require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/FirePHP.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/fb.php');
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/connect_db.php");
require_once dirname(__FILE__) . '/../../Classes/PHPExcel.php';

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

class Feedback{

	// define properties
    private $con = null;	//db connection
    private $comments; 
	private $feedback_thread;
	private $no_total_comments = 0;  
    private $query;  //search query
    

	public function __construct()
    {
       	$this->con = createDBConnection(); // get database connection credentials
    }

    public function Set_Qry($qry) {
    	$this->query = $qry;

        if($this->Match_Query() ){
            return true;
        }

        else{
            $_SESSION['error'] = 'We are sorry, your query does not match any record. Please try again';
            return false;
        }

    }

    private function Match_Query(){
        
        $sql = "SELECT COUNT(*) AS evaluations FROM doctor_answers, doctors, program_sections, questions
        		WHERE doctors.doctor_id = doctor_answers.doctor_id
        		    AND program_sections.program_section_id = doctor_answers.program_section_id
        			AND program_sections.program_section_type = 'Evaluation form'
        			AND questions.question_id = doctor_answers.question_id
        			AND (doctor_answers.question_id LIKE :search_query 
        				 OR doctor_answers.doctor_answer LIKE :search_query 
        				 OR doctor_answers.date_of_answer LIKE :search_query 
        				 OR doctor_answers.program_section_id LIKE :search_query 
        				 OR CONCAT(`first_name`, ' ', `last_name`) LIKE :search_query
        				 OR program_sections.program_id LIKE :search_query 
        				 )";
        
        $query = $this->con->prepare($sql);
        $query->bindValue(':search_query', '%'.$this->query.'%');
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$evaluations = $result_row['evaluations'];

	        	if($evaluations > 0)
	        	{
	        		$this->no_total_comments = $evaluations;
	        		return true;
	        	}  	
        }

        return false;
        
    }

	public function Set_Total(){
        	
		$sql = "SELECT COUNT(*) AS evaluations FROM doctor_answers, doctors, program_sections, questions WHERE doctors.doctor_id = doctor_answers.doctor_id AND program_sections.program_section_id = doctor_answers.program_section_id AND program_sections.program_section_type = 'Evaluation form' AND questions.question_id = doctor_answers.question_id ORDER BY doctor_answers.date_of_answer DESC";

        $query = $this->con->prepare($sql);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$this->no_total_comments = $result_row['evaluations'];  
	        	return true;
        }

        return false;

	}

	public function Get_Total(){
        	
        return $this->no_total_comments;

	}

	public function Get_Query_result(){
        	
        return $this->comments;

	}

	private function Get_Program($program_section_id){

		//Limit the result object from start row passed (0, 10, 20, 30 ...) in chunks of 10

		$sql = 'SELECT program_title FROM programs WHERE program_id = :program_id';
        $query = $this->con->prepare($sql);
        $query->bindValue(':program_id', $program_section_id);
        $query->execute();

        $result_row = $query->fetchObject();
        return $result_row->program_title;

	}

	private function Get_question($question_id){

		$sql = 'SELECT question FROM questions WHERE question_id = :question_id';
        $query = $this->con->prepare($sql);
        $query->bindValue(':question_id', $question_id);
        $query->execute();

        $result_row = $query->fetchObject();
        return $result_row->question;
	}

	public function getRows() {

		if(isset($this->query) && !empty($this->query)){
			$this->Get_Result();
		}
			

		else{
			$this->Get_Comments();
			$this->Print_Comments();
		}

		$this->Close_DB_connection();
	}

	private function Get_Comments(){
		if(isset($_POST['offset']) && isset($_POST['number'])){
			$offset = is_numeric($_POST['offset']) ? $_POST['offset'] : die();  //The number of the row at which it starts
			$comment_no = is_numeric($_POST['number']) ? $_POST['number'] : die();  //Number of rows to load

			$sql = "SELECT doctor_answers.question_id, doctor_answers.doctor_answer, DATE_FORMAT(doctor_answers.date_of_answer,'%Y-%m-%d') AS date_of_answer, doctor_answers.comments, doctors.first_name, doctors.last_name, program_sections.program_id 
					FROM doctor_answers, doctors, program_sections, questions 
					WHERE doctors.doctor_id = doctor_answers.doctor_id AND program_sections.program_section_id = doctor_answers.program_section_id AND program_sections.program_section_type = 'Evaluation form' AND questions.question_id = doctor_answers.question_id
					ORDER BY doctor_answers.date_of_answer 
					DESC LIMIT ".$comment_no." OFFSET ".$offset;
		}


        else {
        	$sql = "SELECT doctor_answers.question_id, doctor_answers.doctor_answer, DATE_FORMAT(doctor_answers.date_of_answer,'%Y-%m-%d') AS date_of_answer, doctor_answers.comments, doctors.first_name, doctors.last_name, program_sections.program_id 
        			FROM doctor_answers, doctors, program_sections, questions 
        			WHERE doctors.doctor_id = doctor_answers.doctor_id AND program_sections.program_section_id = doctor_answers.program_section_id AND program_sections.program_section_type = 'Evaluation form' AND questions.question_id = doctor_answers.question_id
        			ORDER BY doctor_answers.date_of_answer 
        			DESC LIMIT 10";
        }

        $query = $this->con->prepare($sql);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        		$fisrt_name = $result_row['first_name'];
        		$last_name = $result_row['last_name'];
        		$doctor = $fisrt_name . ' ' . $last_name;
				$program_id = $result_row['program_id'];
				$question_id = $result_row['question_id'];    
				$answer = $result_row['doctor_answer'];
				$date_of_answer = $result_row['date_of_answer'];
				$comment = $result_row['comments'];
				$program_title = $this->Get_Program($program_id);
				$question = $this->Get_question($question_id);
				$this->Generate_Comments($question_id, $question, $program_title, $program_id, $answer, $comment, $date_of_answer, $doctor);
        }

		return true;

	}

	private function Get_Result(){

        $sql = "SELECT doctor_answers.question_id, doctor_answers.doctor_answer, DATE_FORMAT(doctor_answers.date_of_answer,'%Y-%m-%d') AS date_of_answer, doctor_answers.comments, doctors.first_name, doctors.last_name, program_sections.program_id
        		FROM doctor_answers, doctors, program_sections, questions 
        		WHERE doctors.doctor_id = doctor_answers.doctor_id 
        			AND program_sections.program_section_id = doctor_answers.program_section_id
        			AND program_sections.program_section_type = 'Evaluation form'  
        			AND questions.question_id = doctor_answers.question_id 
        			AND (doctor_answers.question_id LIKE :search_query 
        				 OR doctor_answers.doctor_answer LIKE :search_query 
        				 OR doctor_answers.date_of_answer LIKE :search_query 
        				 OR doctor_answers.program_section_id LIKE :search_query 
        				 OR CONCAT(`first_name`, ' ', `last_name`) LIKE :search_query
        				 OR program_sections.program_id LIKE :search_query 
        				 )
				ORDER BY doctor_answers.date_of_answer DESC";

        $query = $this->con->prepare($sql);
        $query->bindValue(':search_query', '%'.$this->query.'%');
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        		$fisrt_name = $result_row['first_name'];
        		$last_name = $result_row['last_name'];
        		$doctor = $fisrt_name . ' ' . $last_name;
				$program_id = $result_row['program_id'];
				$question_id = $result_row['question_id'];    
				$answer = $result_row['doctor_answer'];
				$date_of_answer = $result_row['date_of_answer'];
				$comment = $result_row['comments'];
				$program_title = $this->Get_Program($program_id);
				$question = $this->Get_question($question_id);
				$this->Generate_Comments($question_id, $question, $program_title, $program_id, $answer, $comment, $date_of_answer, $doctor);
        }

		return true;

	}

	public function Validate_data($comment){
		
		if(!isset($comment) || empty($comment)){
			return false;
		}

		return true;
	}

	private function Generate_Comments($question_id, $question, $program_title, $program_id, $answer, $comment, $date_of_answer, $doctor){

		$this->comments .= "<tr >\n
							<td>$doctor</td>\n
							<td style='cursor:pointer;color: #428bca;' ><span class='topic' title=\"$program_title\">$program_id</span ></td>\n
							<td style='cursor:pointer;color: #428bca;' ><span class='topic' title=\"$question\">$question_id</span ></td>\n
							<td >$answer</td>\n
							<td >$comment</td>\n
							<td >$date_of_answer</td>\n
						</tr>\n
							 ";
	}

	private function Clear_Comments(){
		$this->comments = '';
	}

	private function Close_DB_connection(){
    	$this->con = null;
	}

	private function Print_Comments(){
		if(empty($this->comments)) { return false; }
		$this->feedback_thread = $this->comments;

		$this->Clear_Comments(); //Clear all concatenated rows
		echo $this->feedback_thread;
	}

	public function CreateExcelReport(){
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		$rows = array();
		$row_count = 2;  //counter to loop through the Excel spreadsheet rows

		$objPHPExcel->getProperties()->setCreator("dxLink")
							 ->setLastModifiedBy("dxLink")
							 ->setTitle("dxLink program evaluation feedback")
							 ->setSubject("dxLink program evaluation feedback")
							 ->setDescription("Generates all data from dxLink evaluation program form")
							 ->setKeywords("dxLink evaluation program form feedback")
							 ->setCategory("evaluation result file");

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'First Name')
				            ->setCellValue('B1', 'Last Name')
				            ->setCellValue('C1', 'Program')
				            ->setCellValue('D1', 'Question')
				            ->setCellValue('E1', 'Answer')
				            ->setCellValue('F1', 'Comment')
				            ->setCellValue('G1', 'Date Posted');

    	$sql = "SELECT doctor_answers.question_id, doctor_answers.doctor_answer, DATE_FORMAT(doctor_answers.date_of_answer,'%Y-%m-%d') AS date_of_answer, doctor_answers.comments, doctors.first_name, doctors.last_name, program_sections.program_id 
		FROM doctor_answers, doctors, program_sections, questions 
		WHERE doctors.doctor_id = doctor_answers.doctor_id AND program_sections.program_section_id = doctor_answers.program_section_id AND program_sections.program_section_type = 'Evaluation form' AND questions.question_id = doctor_answers.question_id
		ORDER BY doctor_answers.date_of_answer DESC";

        $query = $this->con->prepare($sql);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	$fisrt_name = $result_row['first_name'];
        	$last_name = $result_row['last_name'];
        	$program_id = $result_row['program_id'];  
			$question_id = $result_row['question_id'];  
			$answer = $result_row['doctor_answer'];  
			$date_posted = $result_row['date_of_answer'];
			$comment = $result_row['comments'];
			$program = $this->Get_Program($program_id);
			$question = $this->Get_question($question_id);
			array_push($rows, array($fisrt_name, $last_name, $program, $question, $answer, $comment, $date_posted));
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
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(60);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(60);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(110);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(110);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);

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
		$objWriter->save('../Reports/dxLink_Evaluation_feedback_report.xlsx');
		
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

  	$feedback = new Feedback();

	if($feedback->Set_Qry($qry)){
		$feedback->getRows();
		$_SESSION['result'] = $feedback->Get_Query_result();
		$total = $feedback->Get_Total();
		$_SESSION['total_comments'] = $total;
		//setcookie('total', $total, time() + (86400 * 120),"/", ".dxlink.ca", 1,1);//Cookie should be user:random_key:keyed_hash
		header("Location: ../evaluation_feedback.php?search=result"); 
	}
	
	else
		header("Location: ../evaluation_feedback.php"); 
    

}

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];

	$feedback = new Feedback();

	switch ($action) {
		case 'get_rows':
			$feedback->getRows();
			break;
		case 'exportfeedback':
			$feedback->CreateExcelReport();
			break;
		case 'scrollpagination':
			$feedback->getRows();
			break;	
		default;
			break;
	}
	

	exit;
} else {
	return false;
}
?>