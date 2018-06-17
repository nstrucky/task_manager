<?php

 require_once '../include/DbHandler.php';
 require_once '../include/PassHash.php';
 require_once '.././libs/Slim/Slim.php';

  \Slim\Slim::registerAutoLoader();
  
  $app = new \Slim\Slim();
  
  //User id from db - Global Variable
  $user_id = NULL;
  
  function verifyRequiredParams($required_fields) {
      
      $error = FALSE;
      $error_fields = "";
      
      //why do we assign to this variable twice?
      $request_params = array();
      $request_params = $_REQUEST;
      
      if ($_SERVER['REQUEST_METHOD'] == 'PUT') { // really shouldn't do it this way
          $app = \Slim\Slim::getInstance(); //why getInstance here when it's already instantiated above?
          parse_str($app->request()->getBody(), $request_params);
          
      }
      
      foreach($required_fields as $field) {
          if (!isset($request_params[$field]) || 
                  strlen($request_params[$field]) <= 0) {
              $error = TRUE;
              $error_fields .= $field . ', ';
          }
      }
      
      if ($error) {
          $response = array();
          $app = \Slim\Slim::getInstance();
          $response["error"] = true;
          $response["message"] = 'Required field(s) ' . 
                  substr($error_fields,0, -2) . ' is missing or empty.';
          echoResponse(400, $response);
          $app->stop();
      }
  }
  
  

?>