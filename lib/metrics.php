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
    public $years = array();
    public $label_months = array();
    public $digit_months = array();
    public $users = array();
    public $year;
	public $charts = array();
	public $output;
	public $programs_completed = array();
	public $programs = array();
	public $pieChart_id = 0;

	public function __construct()
    {
       	$this->con = createDatabaseConnection(); // get database connection credentials
       	$this->Get_Years();
    }

    public function Get_Years(){
		$this->Get_Registration_Years();
 		
    }

	public function Set_data(){

		$this->Get_Program_Ids();
		
		foreach ($this->years as $index => $year) {
	
			$this->year = $year;
			$this->Set_Months();
			
			foreach($this->programs as $index => $program){
				$this->Get_Programs_completed($program);
			}

			foreach ($this->digit_months as $index => $month) {
					$users = $this->get_no_users($month);
					array_push($this->users, $users);
			}
			
			$this->set_chart_data();
			$this->Generate_Results();

		    $this->Clean_data();
		}

		$this->Close_DB_connection();

		return $this->charts;

	}

	private function Clean_data(){
		$this->label_months = array();
		$this->digit_months = array();
		$this->users = array();
		$this->programs_completed = array();
	}

	private function Close_DB_connection(){
    	$this->con = null;
	}

	private function Set_Months(){
        	
		$sql = "SELECT DISTINCT MONTHNAME(registration_date) AS month, MONTH(registration_date) AS digit_month FROM doctors WHERE YEAR(registration_date) = :registration_year ORDER BY digit_month ASC";
        $query = $this->con->prepare($sql);
        $query->bindValue(':registration_year', $this->year);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        array_push($this->label_months, $result_row['month']); 
	        array_push($this->digit_months, $result_row['digit_month']);
        }

        if( !empty($this->label_months) )
			return true;

		else		
			return false;
	}

	/* GET ALL YEARS FOR WICH THERE ARE REGISTRATIONS */
	public function Get_Registration_Years(){
        
		$sql = "SELECT DISTINCT YEAR(registration_date) AS date_registered FROM doctors ORDER BY date_registered DESC";
        $query = $this->con->prepare($sql);
        $query->execute();

        while($date_set = $query->fetch(PDO::FETCH_ASSOC) ){
        	array_push($this->years, $date_set['date_registered']);
        }

		return true;
	}


    function Get_Programs_completed($program_array){
    	
    	$program_id = $program_array[0];
    	$program_title = $program_array[1];

    	$sql = "SELECT COUNT(*) AS programs_completed FROM (SELECT DISTINCT doctor_id, date_of_completion FROM doctor_profiles WHERE YEAR(date_of_completion) = :completion_year AND program_status = 1 AND program_id = :program_id AND duplicate = :duplicate GROUP BY doctor_id, date_of_completion) AS t";
        $query = $this->con->prepare($sql);
        $query->bindValue(':completion_year', $this->year);
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

	public function get_no_users($month){
		$users = 0;

		$sql = 'SELECT COUNT(*) AS users FROM doctors WHERE YEAR(registration_date) = :registration_year AND MONTH(registration_date) = :month AND active = 1 ORDER BY users';
        $query = $this->con->prepare($sql);
        $query->bindValue(':registration_year', $this->year);
        $query->bindValue(':month', $month);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$users = $result_row['users'];  
        }

		return $users;
	}

	public function set_chart_data(){
		if( !empty($this->programs_completed) ) 
			array_push($this->charts, array($this->label_months, $this->users, $this->year, $this->programs_completed));

		else 
			array_push($this->charts, array($this->label_months, $this->users, $this->year));
		
		return true;
	}

	private function Generate_Results(){
		$no_users = array_sum($this->users);
		$this->pieChart_id = ++$this->pieChart_id;

		if( !empty($this->programs_completed) )
		{
			$this->output .= "<div class='row'>\n
          			<h2 class='sub-header section'> {$no_users} users registered in {$this->year} </h2>\n
      			</div>\n
      			<div class='row chart_row' >
        			<div class='col-sm-6 '>
         		 		<canvas id='canvas{$this->year}' height='200' width='400'></canvas>
        			</div>
        			<div class='col-sm-6 ' >
        				<div class='row' style='margin-left:80px;'> 
					       <h2 class='sub-header section'> Programs Popularity in {$this->year} </h2>
					    </div>
        				<div class='row' style='margin-left:20px;margin-top:20px;'> 
        					<div class='col-xs-12 col-lg-7' >
					        	<canvas id='canvasPie{$this->pieChart_id}' width='300' height='250' ></canvas> 
					       	</div>
					        <div class='col-xs-12 col-lg-5' >
					        	<div id='PieLegend{$this->pieChart_id}'></div>
					       	</div>
					    </div>
        			</div>
      			</div>";
		} 

		else{
			$this->output .= "<div class='row'>\n
          			<h2 class='sub-header section'> {$no_users} users registered in {$this->year} </h2>\n
      			</div>\n
      			<div class='row chart_row' >
        			<div class='col-sm-6 col-md-7'>
         		 		<canvas id='canvas{$this->year}' height='200' width='400'></canvas>
        			</div>
      			</div>";
		}


	}

} //ends class

if(isset($_COOKIE['remember_dxLinkAdmin']) && !empty($_COOKIE['remember_dxLinkAdmin']) ){
  	$_SESSION['user_is_logged_in'] = true;
}

$metrics = new Metrics();
$results = $metrics->Set_data();
$results = json_encode($results);

?>