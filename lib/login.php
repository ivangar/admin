<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/PasswordHash.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/FirePHP.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/FirePHPCore/fb.php');
ob_start();
/**
 * Class OneFileLoginApplication
 *
 * An entire php application with user registration, login and logout in one file.
 * Uses very modern password hashing via the PHP 5.5 password hashing functions.
 * This project includes a compatibility file to make these functions available in PHP 5.3.7+ and PHP 5.4+.
 */
class OneFileLoginApplication
{
    /**
     * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
     */
    private $db_type = "sqlite"; //

    /**
     * @var string Path of the database file (create this with _install.php)
     */
    private $db_sqlite_path = "../users.db";

    /**
     * @var object Database connection
     */
    private $db_connection = null;

    /**
     * @var bool Login status of user
     */
    private $user_is_logged_in = false;

    /**
     * @var bool Login status of user
     */
    private $hash_salt = "";

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";


    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
        $this->runApplication();
    }

    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
        // start the session, always needed!
        $this->doStartSession();

        // check for possible user interactions (login with session/post data or logout)
        $this->performUserLoginAction();
    }

    /**
     * Creates a PDO database connection (in this case to a SQLite flat-file database)
     * @return bool Database creation success status, false by default
     */
    private function createDatabaseConnection()
    {
        try {
            $this->db_connection = new PDO($this->db_type . ':' . $this->db_sqlite_path);
            return true;
        } catch (PDOException $e) {
            $this->feedback = "PDO database connection problem: " . $e->getMessage();
        } catch (Exception $e) {
            $this->feedback = "General problem: " . $e->getMessage();
        }
        return false;
    }

    /**
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        }
        elseif (isset($_POST["login_submitted"])) {
            $this->doLoginWithPostData();
        }
    }

    /**
     * Simply starts the session.
     * It's cleaner to put this into a method than writing it directly into runApplication()
     */
    private function doStartSession()
    {
        session_start();
    }

    /**
     * Process flow of login with POST data
     */
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                if(!$this->checkPasswordCorrectnessAndLogin())
                {
                    return false;
                }
                else
                    echo $this->feedback;
            }
        }
        return true;
    }

    /**
     * Logs the user out
     */
    private function doLogout()
    {
        if(isset($_COOKIE['remember_dxLinkAdmin'])){
            setcookie ('remember_dxLinkAdmin', "", time() - 3600, "/", ".dxlink.ca", 1,1);
            setcookie(session_name(), "");
        }

        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        header("Location: index.php");
    }

    /**
     * The registration flow
     * @return bool
     */
    private function doRegistration()
    {
        if ($this->createDatabaseConnection()) {
            $this->createNewUser();
        }
        // default return
        return false;
    }

    /**
     * Validates the login form data, checks if username and password are provided
     * @return bool Login form data check success state
     */
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->feedback = "Password field was empty.";
        }
        // default return
        return false;
    }

    /**
     * Checks if user exits, if so: check if provided password matches the one in the database
     * @return bool User login success status
     */
    private function checkPasswordCorrectnessAndLogin()
    {
        $uname = strtolower($_POST['user_name']);

        // remember: the user can log in with username or email address
        $sql =  "SELECT user_name, user_email, user_password, hash_salt
                FROM users
                WHERE user_email = :user_name
                LIMIT 1";

        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $uname);
        $query->execute();

        $result_row = $query->fetchObject();

        if ($result_row) {
            // using PHP 5.5's password_verify() function to check password

            if($this->ValidateHashSalt($_POST['user_password'], $result_row->user_password, $result_row->hash_salt))
            {
                $_SESSION['user_name'] = $result_row->user_name;
                $_SESSION['user_email'] = $result_row->user_email;
                $_SESSION['user_is_logged_in'] = true;
                $this->rememberUser();
                $this->user_is_logged_in = true;
                $this->feedback = 'access';
                return true;
            } else {
                $_SESSION['message'] = "Wrong password.";
            }
        } else {
            $_SESSION['message'] = "This user does not exist.";
        }
        // default return
        return false;
    }

    /**
     * Check remember me submission and set a cookie
     */
    public function rememberUser(){

        if(isset($_POST['remember_submitted']) && $_POST['remember_submitted']){
            $cookie = 'saved';
            setcookie('remember_dxLinkAdmin', $cookie, time() + (86400 * 150),"/", ".dxlink.ca", 1,1);//Cookie should be user:random_key:keyed_hash
        }

    }

    /**
     * Simply returns the current status of the user's login
     * @return bool User's login status
     */
    public function getUserLoginStatus()
    {
        return $this->user_is_logged_in;
    }

    private function ValidateHashSalt($pass, $hash, $salt){
        $Passhash = new PasswordHash();
        $hashArray = $Passhash->Create_Custom_Hash($hash, $salt);
        if($Passhash->validate_password($pass, $hashArray)){
            return true;
        }

        else return false;
    }
}

if(isset($_POST['login_submitted']))
{
    // run the application
    $application = new OneFileLoginApplication();
}

if(isset($_COOKIE['remember_dxLinkAdmin']) && !empty($_COOKIE['remember_dxLinkAdmin']) ){
    header("Location: users.php");
}
