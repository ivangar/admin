<?php 

require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/FirePHP.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/fb.php');
ob_start();

require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/connect_db.php");
include_once($_SERVER['DOCUMENT_ROOT'] . '/inc/php/swift/swift_required.php');
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

class ForumTopic{

	// define properties
    public $con;	//db connection
    public $post_id;  //topic id
    public $posts; //String containing html talbe of posts
    public $forum_thread;
    public $no_total_posts;  //number of total posts per topic to paginate for

	function Set_Forum(){
    	$this->con = createDatabaseConnection(); // get database connection credentials
	}

	function Set_Post($post_id){
		$this->post_id = $post_id;
	}

	function Get_Topic($topic_id){

		//Limit the result object from start row passed (0, 10, 20, 30 ...) in chunks of 10

		$sql = 'SELECT topic_content FROM forum_topics WHERE topic_id = :topic_id';
        $query = $this->con->prepare($sql);
        $query->bindValue(':topic_id', $topic_id);
        $query->execute();

        $result_row = $query->fetchObject();
        return $result_row->topic_content;

	}

	function getRows() {

		$this->Get_Posts();
		$this->Print_Posts();

	}

	//Get number of total rows
	function getRowCount() {

		$sql = "SELECT COUNT(*) AS count FROM posts";

        $query = $this->con->prepare($sql);
        $query->execute();

        while($result_row = $query->fetch(PDO::FETCH_ASSOC) ){
	        	$this->no_total_posts = $result_row['count'];  
	        	return true;
        }

        return false;

	}

	function Get_Posts(){

		if(isset($_POST['offset']) && isset($_POST['number'])){
			$offset = is_numeric($_POST['offset']) ? $_POST['offset'] : die();  //The number of the row at which it starts
			$postnumbers = is_numeric($_POST['number']) ? $_POST['number'] : die();  //Number of rows to load

			$sql = "SELECT posts.post_id, posts.topic_id, posts.province, posts.profession, posts.message, DATE_FORMAT(posts.date_posted,'%Y-%m-%d') AS date_posted, doctors.first_name, doctors.last_name FROM posts, doctors WHERE doctors.doctor_id = posts.doctor_id ORDER BY posts.date_posted DESC LIMIT ".$postnumbers." OFFSET ".$offset;
		}


        else {
        	$sql = "SELECT posts.post_id, posts.topic_id, posts.province, posts.profession, posts.message, DATE_FORMAT(posts.date_posted,'%Y-%m-%d') AS date_posted, doctors.first_name, doctors.last_name FROM posts, doctors WHERE doctors.doctor_id = posts.doctor_id ORDER BY posts.date_posted DESC LIMIT 10";
        }

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
				$this->Generate_Posts($post_id , $topic_id, $province, $profession, $topic, $message, $date_posted, $doctor);
        }

		return true;

	}


	function delete_post(){
		$sql = "DELETE FROM posts WHERE post_id = :post";
		$stmt = $this->con->prepare($sql);
		$stmt->bindParam(':post', $this->post_id, PDO::PARAM_INT);   
		$stmt->execute();

		if($stmt){
			$_SESSION['deleted'] = true;
			echo 'deleted';
		}

		return true;

	}

	function get_status(){
		if(isset($_SESSION['deleted']) && $_SESSION['deleted']){
			unset($_SESSION['deleted']);
			return true;
		}
			
		else return false;
	}

	function Validate_data($comment){
		
		if(!isset($comment) || empty($comment)){
			return false;
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

	function SanitizeForSQL($str)
	{
	    if( function_exists( "mysqli_real_escape_string" ) )
	    {
	          $ret_str = mysqli_real_escape_string($this->con, $str);
	    }
	    else
	    {
	          $ret_str = addslashes( $str );
	    }
	    return $ret_str;
	}

	function Generate_Posts($post_id , $topic_id, $province, $profession, $topic, $message, $date_posted, $doctor){

		$this->posts .= "<tr >\n
							<td style='cursor:pointer;color: #428bca;'><span class='topic' title=\"Province is: $province. &emsp; Profession is: $profession\" >$doctor</span ></td>\n
							<td style='cursor:pointer;color: #428bca;' ><span class='topic' title=\"$topic\">$topic_id</span ></td>\n
							<td >$message</td>\n
							<td >$date_posted</td>\n
							<td><a href='#' class='btn btn-danger btn-sm' id='$post_id' ><i class='fa fa-trash-o fa-lg'></i>&nbsp; Delete</a></td>\n
						</tr>\n
							 ";
	}

	/**
	 * Use this helper function to generate dummy post rows
	 */
	function Generate_Dummy_post(){
		// new data
		$topic_id = 'AIT_topic_02';  
		//$doctor_id = 95; 
		$doctor_id = 183; 
		$province = 'Saskatchewan';
		$message = 'This is a dummy text';  
		$profession = 'General Practicioner';

		$insert_post_query = "INSERT INTO posts (topic_id,doctor_id,province,profession,message,date_posted) VALUES (:topic_id,:doctor_id,:province,:profession,:message,NOW())"; 

		for ($x=0; $x<=50; $x++) {
			$stmt = $this->con->prepare($insert_post_query);
			$stmt->execute(array(':topic_id'=>$topic_id,
		 	                  ':doctor_id'=>$doctor_id,
							  ':province'=>$province,
							  ':profession'=>$profession,
							  ':message'=>$message
							  ));
			}
	}

	function Clear_Posts(){
		$this->posts = '';
	}

	function Print_Posts(){
		if(empty($this->posts)) { return false; }
		$this->forum_thread = $this->posts;

		$this->Clear_Posts(); //Clear all concatenated rows
		echo $this->forum_thread;
	}

	function CreateExcelReport(){
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		$rows = array();
		$row_count = 2;  //counter to loop through the Excel spreadsheet rows

		$objPHPExcel->getProperties()->setCreator("dxLink")
							 ->setLastModifiedBy("dxLink")
							 ->setTitle("dxLink Discussion Forum")
							 ->setSubject("dxLink Discussion Forum")
							 ->setDescription("Generates all data from dxLink discussion forum comments")
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
	}

} //ends class

if(isset($_COOKIE['remember_dxLinkAdmin']) && !empty($_COOKIE['remember_dxLinkAdmin']) ){
  	$_SESSION['user_is_logged_in'] = true;
}

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
	$post_id = $_REQUEST['comment_id'];

		//Instance of forum topic
	$forum = new ForumTopic();

    $forum->Set_Forum();  //This should include topic id to be sent
	$forum->Set_Post($post_id);
	
	switch ($action) {
		case 'get_rows':
			$forum->getRows();
			break;
		case 'delete_post':
			$forum->delete_post();
			break;
		case 'exportForum':
			$forum->CreateExcelReport();
			break;
		case 'scrollpagination':
			$forum->getRows();
			$forum->Print_Posts();
			break;	
		default;
			break;
	}
	

	exit;
} else {
	return false;
}
?>