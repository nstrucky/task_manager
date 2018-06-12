<?php

require_once __DIR__.'/strings_vars.php';

//TODO create string constants
//$dsn = 'mysql:host='.DB_SERVER.';'
//        .'dbname='.DB_DATABASE;

try {
    $db = new PDO($dsn, DB_USER, DB_PASSWORD);
} catch (PDOException $ex) {
    $error_message = $ex->getMessage();
    include('./view/database_error.php');
    exit();
}


?>