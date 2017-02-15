<?php 
if(!isset($_SESSION)){session_start();} 
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/FirePHP.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/fb.php');

require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/connect_db.php");
//require_once "inc/php/formvalidator.php"; //include the main validation script
ob_start();

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

class Search
{
    /**
     * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
     */
    private $con = null;    //db connection

    /**
     * @var string Path of the database file (create this with _install.php)
     */
    private $search_query;    //query to be searched

    /**
     * @var object of posts
     */
    private $posts;    //posts

    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct($query)
    {
        $this->runApplication($query);
    }

    public function Set_Connection(){

        $this->con = createDatabaseConnection(); // get database connection credentials
    }

    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication($query)
    {
        // set connection with database
        $this->Set_Connection();

        // set the Search query
        $this->search_query = $query;

        // start the session, always needed!
        $this->getRow();

    }


    private function getRow() {

        if($this->Match_Query() ){
            $this->Get_Post();
        }

        else{
            $_SESSION['error'] = 'We are sorry, your query does not match any record. Please try again';
        }

    }

    private function Match_Query(){
        
        $sql = "SELECT * FROM posts, doctors WHERE doctors.doctor_id = posts.doctor_id AND (posts.message LIKE :search_query OR posts.topic_id LIKE :search_query OR posts.date_posted LIKE :search_query OR CONCAT(`first_name`, ' ', `last_name`) LIKE :search_query)";
        $query = $this->con->prepare($sql);
        $query->bindValue(':search_query', '%'.$this->search_query.'%');
        $query->execute();

        $result_row = $query->fetchObject();

        if ($result_row) {
            return true;
        }

        else return false;
        
    }

    //Gets all the topics per specific topic
    private function Get_Post(){

        $sql = "SELECT posts.post_id, posts.topic_id, posts.province, posts.profession, posts.message, DATE_FORMAT(posts.date_posted,'%Y-%m-%d') AS date_posted, doctors.first_name, doctors.last_name FROM posts, doctors WHERE doctors.doctor_id = posts.doctor_id AND (posts.message LIKE :search_query OR posts.topic_id LIKE :search_query OR posts.date_posted LIKE :search_query OR CONCAT(`first_name`, ' ', `last_name`) LIKE :search_query)";

        $query = $this->con->prepare($sql);
        $query->bindValue(':search_query', '%'.$this->search_query.'%');
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


    private function Generate_Posts($post_id , $topic_id, $province, $profession, $topic, $message, $date_posted, $doctor){
        //<td style='cursor:pointer;' ><a data-toggle='collapse' href='#topic_$post_id'>$topic_id</a></td>
        $this->posts .= "<tr>\n
                            <td style='cursor:pointer;color: #428bca;'><span class='topic' title=\"Province is: $province. &emsp; Profession is: $profession\" >$doctor</span></td>\n
                            <td style='cursor:pointer;color: #428bca;'><span class='topic' title=\"$topic\">$topic_id</span ></td>\n
                            <td style='width:60%;max-width:60%;'>$message</td>\n
                            <td>$date_posted</td>\n
                            <td><a href='#' class='btn btn-danger btn-sm' id='$post_id' ><i class='fa fa-trash-o fa-lg'></i>&nbsp; Delete</a></td>\n
                        </tr>\n    
                             ";
    }

    public function Print_Posts(){
        if(empty($this->posts)) { $this->posts = "<tr>\n<td></td>\n<td></td>\n<td></td>\n<td></td>\n<td></td>\n</tr>\n"; }
        $this->forum_thread = $this->posts;

        $this->Clear_Posts(); //Clear all concatenated rows
        return $this->forum_thread;
    }

    private function Clear_Posts(){
        $this->posts = '';
    }

    private function Get_Topic($topic_id){

        $sql = 'SELECT topic_content FROM forum_topics WHERE topic_id = :topic_id';
        $query = $this->con->prepare($sql);
        $query->bindValue(':topic_id', $topic_id);
        $query->execute();

        $result_row = $query->fetchObject();
        return $result_row->topic_content;

    }

}

if(isset($_POST['search_q']))
{
    // run the application
    $qry = trim($_POST['search_q']);
    $search = new Search($qry);
    $posts = $search->Print_Posts();
    $_SESSION['result'] = $posts;

    if(isset($_SESSION['error']))
        header("Location: ../forum.php");

    else
       header("Location: ../forum.php?search=result");
}

?>