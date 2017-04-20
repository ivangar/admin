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

class Accounts{

	// define properties
    public $con;	//db connection
    public $post_id;  //topic id
    public $users; //String containing html talbe of posts
    public $user_thread;
    public $total_no_users;  //number of total posts per topic to paginate for
    public $search_query;
    public $filter_search;
    public $report = false;

	function Set_Accounts(){
    	$this->con = createDatabaseConnection(); // get database connection credentials
	}

	function getRows() {

		$this->Get_Users();
		$this->Print_Users();

	}

	function Get_Users(){

		if(isset($_POST['offset']) && isset($_POST['number'])){
			$offset = is_numeric($_POST['offset']) ? $_POST['offset'] : die();  //The number of the row at which it starts
			$postnumbers = is_numeric($_POST['number']) ? $_POST['number'] : die();  //Number of rows to load

			$sql = "SELECT doctor_id, first_name, last_name, email, country, province, postal_code, profession, specialty, DATE_FORMAT(registration_date,'%Y-%m-%d') AS date_completed, DATE_FORMAT(last_visit,'%Y-%m-%d') AS last_visit FROM doctors WHERE active = :active_user ORDER BY last_visit DESC LIMIT ".$postnumbers." OFFSET ".$offset;
		}

        else {
        	$sql = "SELECT doctor_id, first_name, last_name, email, country, province, postal_code, profession, specialty, DATE_FORMAT(registration_date,'%Y-%m-%d') AS date_completed, DATE_FORMAT(last_visit,'%Y-%m-%d') AS last_visit FROM `doctors` WHERE active = :active_user ORDER BY last_visit DESC LIMIT 10 ";
        }

        $query = $this->con->prepare($sql);
        $query->bindValue(':active_user', 1);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$first_name = $result_row['first_name'];  
				$last_name = $result_row['last_name'];  
				$email = $result_row['email']; 
				$country = $result_row['country']; 
				$province = $result_row['province'];  
				$postal_code = $result_row['postal_code'];
				(strcmp($result_row['profession'],'Other') == 0) ? $profession = 'Healthcare Professional'  : $profession = $result_row['profession'];
				$specialty = $result_row['specialty'];
				$registration_date = $result_row['date_completed'];
				$last_visit = $result_row['last_visit'];
				$no_completed_programs = $this->Get_Programs_completed($result_row['doctor_id']);

				$this->Generate_Users($first_name , $last_name, $email, $country, $province, $postal_code, $profession, $specialty, $registration_date, $no_completed_programs, $last_visit);
        }

		return true;

	}

    function Get_Programs_completed($user_id){
    	
    	$programs_completed = '';

    	$sql = "SELECT DISTINCT doctor_profiles.date_of_completion, doctor_profiles.program_id, programs.program_title, programs.program_subtitle FROM doctor_profiles, programs WHERE doctor_id = :user_id AND program_status = 1 AND doctor_profiles.program_id = programs.program_id GROUP BY date_of_completion";
        $query = $this->con->prepare($sql);
        $query->bindValue(':user_id', $user_id);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	$program = $result_row['program_title'] . " (" . $result_row['program_subtitle'] . ")";
        	//$program = $result_row['program_title'];
        	if($this->report)
        		$programs_completed .= $program . '/';
        	else
	        	$programs_completed .= "<span style='cursor:pointer;color: #428bca;font-weight:bold;' class='program' title=\"$program\" >" . $result_row['program_id'] . '</span><br/>';
        }

		return $programs_completed;
    }

    function Get_All_Programs(){
    	
    	$program_type = 'accredited';
    	$accredited_programs = array();

    	$sql = "SELECT DISTINCT program_id FROM programs WHERE program_type = :type";
        $query = $this->con->prepare($sql);
        $query->bindValue(':type', "accredited");
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	array_push($accredited_programs, $result_row['program_id']);
        }

		return $accredited_programs;
    }

    function Get_Excel_Programs_completed($user_id){
    	
    	$programs_completed = array();

    	$sql = "SELECT DISTINCT doctor_profiles.date_of_completion, doctor_profiles.program_id, programs.program_title, programs.program_subtitle FROM doctor_profiles, programs WHERE doctor_id = :user_id AND program_status = 1 AND doctor_profiles.program_id = programs.program_id GROUP BY date_of_completion";
        $query = $this->con->prepare($sql);
        $query->bindValue(':user_id', $user_id);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	$program = $result_row['program_title'] . " (" . $result_row['program_subtitle'] . ")";
        	$programs_completed[$result_row['program_id']] = $program;
        }

		return $programs_completed;
    }

	function setQuery($query){
		$this->search_query = $query;
	}

	function setFilter($filter){
		$this->filter_search = $filter;
	}

	function Match_Query(){
		if(isset($this->search_query) && !empty($this->search_query)){
			$records_no = 0;
			
			if(strcmp($this->search_query,'Healthcare Professional') == 0) $this->search_query = 'Other';

			if(strcmp( $this->Translate_profession($this->search_query), 'false') == 0) $french_profession = 'not found'; 
			else $french_profession = $this->Translate_profession($this->search_query);

			if(strcmp( $this->Translate_specialty($this->search_query), 'false') == 0) $french_specialty = 'not found';
			else $french_specialty = $this->Translate_specialty($this->search_query);

			if(strcmp( $this->Translate_province($this->search_query), 'false') == 0) $french_province = 'not found'; 
			else $french_province = $this->Translate_province($this->search_query);

        	$sql = "SELECT doctors.doctor_id, doctors.first_name, doctors.last_name, doctors.email, doctors.country, doctors.province, doctors.postal_code, doctors.profession, doctors.specialty, DATE_FORMAT(doctors.registration_date,'%Y-%m-%d') AS date_completed, DATE_FORMAT(doctors.last_visit,'%Y-%m-%d') AS last_visit  
	    		FROM doctors, doctor_profiles
	    		WHERE (first_name LIKE :search_query 
	    			OR last_name LIKE :search_query 
	    			OR CONCAT(`first_name`, ' ', `last_name`) LIKE :search_query 
	    			OR email LIKE :search_query
	    			OR country LIKE :search_query
	    			OR province LIKE :search_query 
	    			OR postal_code LIKE :search_query
	    			OR profession LIKE :search_query
	    			OR specialty LIKE :search_query ";

	    	if(strcmp( $this->Translate_profession($this->search_query), 'not found') !== 0){
	    		$sql .= " OR profession LIKE :french_profession ";
	    	}
	    	if(strcmp( $this->Translate_province($this->search_query), 'not found') !== 0){
	    		$sql .= " OR province LIKE :french_province ";
	    	}
	    	if(strcmp( $this->Translate_specialty($this->search_query), 'not found') !== 0){
	    		$sql .= " OR specialty LIKE :french_specialty ";
	    	}
					
	    			
	    	$sql .= " OR registration_date LIKE :search_query
	    			OR (doctor_profiles.doctor_id = doctors.doctor_id AND doctor_profiles.program_status = 1 AND doctor_profiles.program_id LIKE :search_query))
	    			AND active = :active_user ";
			 
			if(isset($this->filter_search) && !empty($this->filter_search)){

				if(strcmp($this->filter_search, 'Healthcare Professional') == 0) $this->filter_search = 'Other';

				$sql .= " AND (first_name LIKE :filter
		        		     OR last_name LIKE :filter
		        			 OR CONCAT(`first_name`, ' ', `last_name`) LIKE :filter
		        			 OR email LIKE :filter
		        			 OR country LIKE :filter
		        			 OR province LIKE :filter
		        			 OR postal_code LIKE :filter
		        			 OR profession LIKE :filter
		        			 OR specialty LIKE :filter
		        			 OR registration_date LIKE :filter
		        			 OR (doctor_profiles.doctor_id = doctors.doctor_id AND doctor_profiles.program_status = 1 AND doctor_profiles.program_id LIKE :filter)) ";
			}

			$sql .= " GROUP BY doctor_id";

	        $query = $this->con->prepare($sql);
		    $query->bindValue(':search_query', '%'.$this->search_query.'%');
		    if(strcmp( $this->Translate_specialty($this->search_query), 'not found') !== 0){
		    	$query->bindValue(':french_specialty', '%'.$french_specialty.'%');
		    }
		    if(strcmp( $this->Translate_profession($this->search_query), 'not found') !== 0){
		    	$query->bindValue(':french_profession', '%'.$french_profession.'%');
		    }
		    if(strcmp( $this->Translate_province($this->search_query), 'not found') !== 0){
		    	$query->bindValue(':french_province', '%'.$french_province.'%');
		    }
		    if(isset($this->filter_search) && !empty($this->filter_search))
		    	$query->bindValue(':filter', '%'.$this->filter_search.'%');
		    $query->bindValue(':active_user', 1);
		    $query->execute();

	        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
		        	$first_name = $result_row['first_name'];  
					$last_name = $result_row['last_name'];  
					$email = $result_row['email']; 
					$country = $result_row['country']; 
					$province = $result_row['province'];  
					$postal_code = $result_row['postal_code'];
					(strcmp($result_row['profession'],'Other') == 0) ? $profession = 'Healthcare Professional'  : $profession = $result_row['profession'];
					$specialty = $result_row['specialty'];
					$registration_date = $result_row['date_completed'];
					$no_completed_programs = $this->Get_Programs_completed($result_row['doctor_id']);
					$last_visit = $result_row['last_visit'];
					$this->Generate_Users($first_name , $last_name, $email, $country, $province, $postal_code, $profession, $specialty, $registration_date, $no_completed_programs, $last_visit);
					$records_no++;
	        }
	
	        $this->total_no_users = $records_no;
	        return true;
	    }

	    else return false;
        
    }

	function Get_Total_Users(){
        	
		$sql = 'SELECT COUNT(*) AS users FROM doctors WHERE active = :active_user ';
        $query = $this->con->prepare($sql);
         $query->bindValue(':active_user', 1);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$this->total_no_users = $result_row['users'];  
	        	return true;
        }

		return false;
	}

	function Generate_Users($first_name , $last_name, $email, $country, $province, $postal_code, $profession, $specialty, $registration_date, $no_completed_programs, $last_visit){

		$this->users .= "<tr >\n
							<td>{$first_name} {$last_name}</td>\n
							<td> {$email} </td>\n
							<td> {$country} </td>\n
							<td> {$province} </td>\n
							<td> {$postal_code} </td>\n
							<td> {$profession} </td>\n
							<td> {$specialty} </td>\n
							<td style='text-align:center;' > {$registration_date} </td>\n
							<td > {$no_completed_programs} </td>\n
							<td style='text-align:center;' > {$last_visit} </td>\n
						</tr>\n
							 ";
	}

	function Translate_specialty($specialty){

		$french_specialty = '';

		switch ($specialty) {
			case 'Allergy':
				$french_specialty = 'Allergologie';
				break;
			case 'General Practitioner':
	 			$french_specialty = 'Médecine générale';
				break;
			case 'Family Physician':
	 			$french_specialty = 'Médecine familiale';
				break;
			case 'Cardiology':
	 			$french_specialty = 'Cardiologie';
				break;
			case 'Critical-Care':
	 			$french_specialty = 'Soins critiques';
				break;
			case 'Dermatology':
	 			$french_specialty = 'Dermatologie';
				break;
			case 'Emergency Medicine':
	 			$french_specialty = 'Médecine d’urgence';
				break;
			case 'Endocrinology':
	 			$french_specialty = 'Endocrinologie';
				break;
			case 'Gastroenterology':
	 			$french_specialty = 'Gastroentérologie';
				break;
			case 'General Surgery':
	 			$french_specialty = 'Chirurgie générale';
				break;
			case 'Geriatrics':
	 			$french_specialty = 'Gériatrie';
				break;
			case 'Hematology':
	 			$french_specialty = 'Hématologie';
				break;
			case 'Hospitalist':
	 			$french_specialty = 'Médecine hospitalière';
				break;
			case 'Immunology':
	 			$french_specialty = 'Immunologie';
				break;
			case 'Infectious Disease':
	 			$french_specialty = 'Infectiologie';
				break;
			case 'Internal Medicine':
	 			$french_specialty = 'Médecine interne';
				break;
			case 'Nephrology':
	 			$french_specialty = 'Néphrologie';
				break;
			case 'Neurology':
	 			$french_specialty = 'Neurologie';
				break;
			case 'Oncology':
	 			$french_specialty = 'Oncologie';
				break;
			case 'Obstetrics and Gynecology':
	 			$french_specialty = 'Obstétrique et gynécologie';
				break;
			case 'Ophthalmology':
	 			$french_specialty = 'Ophtalmologie';
				break;
			case 'Orthopedics':
	 			$french_specialty = 'Orthopédie';
				break;
			case 'Otolaryngology':
	 			$french_specialty = 'Otorhinolaryngologie';
				break;
			case 'Palliative Care':
	 			$french_specialty = 'Soins palliatifs';
				break;
			case 'Pediatrics':
	 			$french_specialty = 'Pédiatrie';
				break;
			case 'Psychiatry':
	 			$french_specialty = 'Psychiatrie';
				break;
			case 'Respirology':
	 			$french_specialty = 'Pneumologie';
				break;
			case 'Rheumatology':
	 			$french_specialty = 'Rhumatologie';
				break;
			case 'Radiology':
	 			$french_specialty = 'Radiologie';
				break;
			case 'Urology':
	 			$french_specialty = 'Urologie';
				break;
			default: $french_specialty = 'false';
		}

		return $french_specialty;
	}

	function Translate_province($province){

		$french_province = '';

		switch ($province) {
			case 'British Columbia':
				$french_province = 'Colombie-Britannique';
				break;
			case 'New Brunswick':
	 			$french_province = 'Nouveau-Brunswick';
				break;
			case 'Newfoundland and Labrador':
	 			$french_province = 'Terre-Neuve et Labrador';
				break;
			case 'Nova Scotia':
	 			$french_province = 'Nouvelle-Écosse';
				break;
			case 'Prince Edward Island':
	 			$french_province = 'Île-du-Prince-Édouard';
				break;
			case 'Quebec':
	 			$french_province = 'Québec';
				break;
			default: $french_province = 'false';
		}

		return $french_province;

	}

	function Translate_profession($profession){

		$french_profession = '';

		switch ($profession) {
			case 'Physician':
				$french_profession = 'Médecin';
				break;
			case 'Nurse Practitioner':
	 			$french_profession = 'Infirmière praticienne';
				break;
			case 'Pharmacist':
	 			$french_profession = 'Pharmacien';
				break;
			default: $french_profession = 'false';
		}

		return $french_profession;

	}

	/**
	 * Use this helper function to generate dummy  rows
	 */
	function Generate_Dummy_users(){
		// new data
    	$first_name = 'user';  
		$last_name = 'xxx';  
		$email = 'email@mail.com'; 
		$country = 'Canada'; 
		$province = 'Ontario';  
		$postal_code = 'H4O2S3';
		$profession = 'Physician';
		$specialty = 'Emergency Medicine';

		$insert_post_query = "INSERT INTO doctors (first_name, last_name, email, country, province, postal_code, profession, specialty, registration_date) VALUES (:first_name,:last_name,:email,:country,:province,:postal_code,:profession,:specialty,NOW())"; 

		for ($x=0; $x<=50; $x++) {
			$stmt = $this->con->prepare($insert_post_query);
			$stmt->execute(array(':first_name'=>$first_name,
		 	                  ':last_name'=>$last_name,
							  ':email'=>$email,
							  ':country'=>$country,
							  ':province'=>$province,
							  ':postal_code'=>$postal_code,
							  ':profession'=>$profession,
							  ':specialty'=>$specialty
							  ));
			}
	}

	function Clear_Users(){
		$this->users = '';
	}

	function Print_Users(){
		if(empty($this->users)) { return false; }
		$this->user_thread = $this->users;

		$this->Clear_Users(); //Clear all concatenated rows
		echo $this->user_thread;
	}

	function Print_Total_no_Users(){
		if(!empty($this->total_no_users))
			$total = $this->total_no_users;

		else 
			$total = 'NA';
		
		return $total;
	}

	function Excel_Alphabet_iterate($index){
		$alphabet = array('L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
		return $alphabet[$index];
	}

	function Get_program_index($all_programs, $program_id){
		$index = 0;

		foreach ($all_programs as $pointer => $id) { if(strcmp($id, $program_id) == 0) $index = $pointer;}

		return $index;
	}

	function CreateExcelReport(){

		$this->report = true;

		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		$rows = array();
		$row_count = 2;  //counter to loop through the Excel spreadsheet rows

		$objPHPExcel->getProperties()->setCreator("dxLink")
							 ->setLastModifiedBy("dxLink")
							 ->setTitle("dxLink Users data")
							 ->setSubject("dxLink Users data")
							 ->setDescription("Generates all data from dxLink Users data")
							 ->setKeywords("dxLink Users data")
							 ->setCategory("Users data");

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'First Name')
				            ->setCellValue('B1', 'Last Name')
				            ->setCellValue('C1', 'Email')
				            ->setCellValue('D1', 'Country')
				            ->setCellValue('E1', 'Province')
				            ->setCellValue('F1', 'Postal Code')
				            ->setCellValue('G1', 'Profession')
				            ->setCellValue('H1', 'Specialty')
				            ->setCellValue('I1', 'Language')
				            ->setCellValue('J1', 'Registered')
				            ->setCellValue('K1', 'Last Visit');

		$all_programs = $this->Get_All_Programs();

		foreach ($all_programs as $index => $program_id) {
			$letter = $this->Excel_Alphabet_iterate($index);
			$objPHPExcel->getActiveSheet()->setCellValue($letter . 1, $program_id);
			$objPHPExcel->getActiveSheet()->getColumnDimension($letter)->setWidth(85);
		}

		$sql = "SELECT doctor_id, first_name, last_name, email, country, province, postal_code, profession, specialty, language, DATE_FORMAT(registration_date,'%d-%m-%Y') AS registered, DATE_FORMAT(last_visit,'%Y-%m-%d') AS last_visit FROM `doctors` WHERE active = :active_user ORDER BY registered DESC";
        $query = $this->con->prepare($sql);
        $query->bindValue(':active_user', 1);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
        	$programs_completed = array();
			$first_name = $result_row['first_name'];  
			$last_name = $result_row['last_name'];  
			$email = $result_row['email']; 
			$country = $result_row['country']; 
			$province = $result_row['province'];  
			$postal_code = $result_row['postal_code'];
			(strcmp($result_row['profession'],'Other') == 0) ? $profession = 'Healthcare Professional'  : $profession = $result_row['profession'];
			$specialty = $result_row['specialty'];
			$language = $result_row['language'];
			$registration_date = $result_row['registered'];
			$last_visit = $result_row['last_visit'];
			$no_completed_programs = $this->Get_Excel_Programs_completed($result_row['doctor_id']);
			
			foreach ($no_completed_programs as $id => $program_name) {
				$program_index = $this->Get_program_index($all_programs, $id);
				$program_cell = $this->Excel_Alphabet_iterate($program_index);
				$programs_completed[$program_cell] = $program_name;
			}	

			array_push($rows, array($first_name, $last_name, $email, $country, $province, $postal_code, $profession, $specialty, $language, $registration_date, $last_visit, $programs_completed));
        }
        
        foreach ($rows as $row => $column) {

			$objPHPExcel->getActiveSheet()->setCellValue('A' . $row_count, $column[0])
							            ->setCellValue('B' . $row_count, $column[1])
							            ->setCellValue('C' . $row_count, $column[2])
							            ->setCellValue('D' . $row_count, $column[3])
							            ->setCellValue('E' . $row_count, $column[4])
							            ->setCellValue('F' . $row_count, $column[5])
							            ->setCellValue('G' . $row_count, $column[6])
							            ->setCellValue('H' . $row_count, $column[7])
							            ->setCellValue('I' . $row_count, $column[8])
							            ->setCellValue('J' . $row_count, $column[9])
							            ->setCellValue('k' . $row_count, $column[10]);

			foreach ($column[11] as $cell => $title) {
				$objPHPExcel->getActiveSheet()->setCellValue($cell . $row_count, $title);
			}	


			$objPHPExcel->getActiveSheet()->getRowDimension($row_count)->setRowHeight(16); 

		    $row_count++;		
		}	

		//Fill design settings for first heading row
		$objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(25);
		$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(20);
		$objPHPExcel->getActiveSheet()->getStyle('A1:' . $letter . '1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FF808080');
		$objPHPExcel->getActiveSheet()->getStyle('A1:' . $letter . '1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
		$objPHPExcel->getActiveSheet()->getStyle('A1:' . $letter . '1')->getFont()->setSize(16);
		$objPHPExcel->getActiveSheet()->freezePane('A2');

		//Align all cells
		$objPHPExcel->getActiveSheet()->getStyle('A1:' . $letter . $row_count)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle('A1:' . $letter . $row_count)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('../Reports/dxLink_Users.xlsx');
		
		echo 'exported';
		return true;
	}

} //ends class

if(isset($_COOKIE['remember_dxLinkAdmin']) && !empty($_COOKIE['remember_dxLinkAdmin']) ){
  	$_SESSION['user_is_logged_in'] = true;
}

if(isset($_POST['search_q']))
{
    $qry = trim($_POST['search_q']);

    $search = new Accounts();
    $search->Set_Accounts();
    $search->setQuery($qry);

    if(isset($_POST['search_q2']) && !empty($_POST['search_q2'])){
    	$filter = trim($_POST['search_q2']);
    	$search->setFilter($filter);
    }

    if($search->Match_Query() && !empty($search->users)){
    	$user = $search->users;
    	$_SESSION['result'] = $user;
    }

    else{ $_SESSION['error'] = 'We are sorry, your query does not match any record. Please try again'; }

    if(isset($_SESSION['error']))
    	echo 'error';
        //header("Location: ../users.php");

    else{
    	$total_users = $search->total_no_users;
    	echo "$total_users";
    	//header("Location: ../users.php?search=result&users=" . $total_users);
    }
       
}

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];

		//Instance of forum topic
  	$user_accounts = new Accounts();
  	$user_accounts->Set_Accounts();

	switch ($action) {
		case 'exportUsers':
			$user_accounts->CreateExcelReport();
			break;
		case 'scrollpagination':
			$user_accounts->getRows();
			break;	
		default;
			break;
	}
	
	exit;
}

?>