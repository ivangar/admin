<?php 
require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/connect_db.php");

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

class Summary{

	// define properties
    private $con = null;	//db connection
	public $charts = array();
	public $output;
	public $programs_completed = array();
	public $programs = array();
	public $pieChart_id = 0;

	public function __construct()
    {
       	$this->con = createDBConnection(); // get database connection credentials
    }

	public function Set_data(){

		$this->Get_Program_Ids();
				
		foreach($this->programs as $index => $program){
			$this->Get_Programs_completed($program);
		}

		$this->set_chart_data();
		$this->Generate_Results();

	    $this->Clean_data();
	 
		$this->Close_DB_connection();

		return $this->charts;

	}

	private function Clean_data(){
		$this->programs_completed = array();
	}

	private function Close_DB_connection(){
    	$this->con = null;
	}


    function Get_Programs_completed($program_array){
    	
    	$program_id = $program_array[0];
    	$program_title = $program_array[1];

    	$sql = "SELECT COUNT(*) AS programs_completed FROM (SELECT DISTINCT doctor_id, date_of_completion FROM doctor_profiles WHERE program_status = 1 AND program_id = :program_id AND duplicate = :duplicate GROUP BY doctor_id, date_of_completion) AS t";
        $query = $this->con->prepare($sql);
        $query->bindValue(':program_id', $program_id);
        $query->bindValue(':duplicate', 0);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	if(	!empty($result_row['programs_completed']) )
	        	array_push($this->programs_completed, array($result_row['programs_completed'], $program_id, $program_title));
        }

		return true;
    }

	public function Get_Program_Ids(){
        
		$sql = 'SELECT DISTINCT program_id, program_title, program_subtitle FROM programs ORDER BY program_id ASC';
        $query = $this->con->prepare($sql);
        $query->execute();

        while($programs = $query->fetch(PDO::FETCH_ASSOC) ){
        	$title = $programs['program_title'] . " (" . $programs['program_subtitle'] . ")";
        	//array_push($this->programs, array($programs['program_id'], $programs['program_title']));
        	array_push($this->programs, array($programs['program_id'], $title));
        }
	
		return true;
	}

	public function set_chart_data(){
		
		array_push($this->charts, $this->programs_completed);
		
		return true;
	}

	private function Generate_Results(){

		$this->output .= "
						<div class='row chart_row' >
							<div class='row' style='margin-left:0;'> 
						       <h2 class='sub-header section'> All Programs Completed </h2>
						    </div>
	        				<div class='row' style='margin-left:20px;'> 
	        					<div class='col-xs-12 col-lg-7' >
						        	<canvas id='canvasSummary'></canvas>
						       	</div>
						        <div class='col-xs-12 col-lg-5' >
						        	<div id='BarLegend' style='margin: 20px auto;'></div>
						       	</div>
						    </div>
						</div>
						";
	}

} //ends class

$summary_programs = new Summary();
$summary = $summary_programs->Set_data();
$summary = json_encode($summary);

?>