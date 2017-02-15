<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/inc/php/PasswordHash.php");
/**
 * This is the installation file for the 0-one-file version of the php-login script.
 * It simply creates a new and empty database.
 */

// error reporting config
error_reporting(E_ALL);

// config
$db_type = "sqlite";
$db_sqlite_path = "../users.db";

// create new database file / connection (the file will be automatically created the first time a connection is made up)
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);

// create new empty table inside the database (if table does not already exist)
$sql = 'CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INTEGER PRIMARY KEY,
        `user_name` varchar(64),
        `user_password` varchar(255),
        `hash_salt` varchar(255),
        `user_email` varchar(64));
        CREATE UNIQUE INDEX `user_name_UNIQUE` ON `users` (`user_name` ASC);
        CREATE UNIQUE INDEX `user_email_UNIQUE` ON `users` (`user_email` ASC);
        ';

// execute the above query
$query = $db_connection->prepare($sql);
$query->execute();

// check for success
if (file_exists($db_sqlite_path)) {
    echo "Database $db_sqlite_path was created, installation was successful.";
} else {
    echo "Database $db_sqlite_path was not created, installation was NOT successful. Missing folder write rights ?";
}

//Call hash function and create hash and salt
    $Passhash = new PasswordHash();
    $Passhash->SetPassword("0000");
    $hash_salt = $Passhash->GetSalt();
    $user_password = $Passhash->GetHash();

//Insert new admin 
$sql = 'INSERT INTO users (user_name, user_password, hash_salt, user_email)
        VALUES(:user_name, :user_password, :hash_salt, :user_email)';
$query = $db_connection->prepare($sql);
$query->bindValue(':user_name', 'admin');
$query->bindValue(':user_password', $user_password);
$query->bindValue(':hash_salt', $hash_salt);
$query->bindValue(':user_email', "dxlink@sta.ca");
// PDO's execute() gives back TRUE when successful, FALSE when not
// @link http://stackoverflow.com/q/1661863/1114320
$registration_success_state = $query->execute();

if ($registration_success_state) {
    echo "Your account has been created successfully. You can now log in.";
    return true;
} else {
    echo "Sorry, your registration failed. Please go back and try again.";
}