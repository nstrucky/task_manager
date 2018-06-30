<?php

 require_once '../include/DbHandler.php';
 require_once '../include/PassHash.php';
 require_once '.././libs/Slim/Slim.php';
 require_once '../include/string_vars.php';
 
  \Slim\Slim::registerAutoLoader();
  
  $app = new \Slim\Slim();
  
  //User id from db - Global Variable
  $user_id_global = NULL;
  
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
  
  function validateEmail($email_address) {
      $app = \Slim\Slim::getInstance();
      if (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
          $response["error"] = TRUE;
          $response["message"] = 'Email address is not valid';
          echoResponse(400, $response);
          $app->stop();
                  
      }
  }
  
  
  function echoResponse($status_code, $response) {
      $app = \Slim\Slim::getInstance();  
      $app->status($status_code);
      $app->contentType('application/json');
      echo json_encode($response);
  }
  
  /**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
  $app->post('/'.HTTP_PATH_REGISTER, function() use ($app) {
            // check for required params
            verifyRequiredParams(array(HTTP_PARAM_NAME, HTTP_PARAM_EMAIL,
               HTTP_PARAM_PASSWORD));
 
            $response = array();
 
            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
 
            // validating email address
            validateEmail($email);
 
            $db_handler = new DbHandler();
            $res = $db_handler->createUser($name, $email, $password);
 
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                echoResponse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
                echoRespnse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
                echoResponse(200, $response);
            }
        });
        
        
        
 $app->post('/'.HTTP_PATH_LOGIN, function() use ($app) {
    
     verifyRequiredParams(array(HTTP_PARAM_EMAIL, HTTP_PARAM_PASSWORD));
     
     $name = $app->post(HTTP_PARAM_NAME);
     $email = $app->post(HTTP_PARAM_EMAIL);
     $response = array();
     
     $db_handler = new DbHandler();
     
     if ($db_handler->checkLogin($email, $password)) {
         $user = $db_handler->getUserByEmail($email);
                 
         if ($user != NULL) {
             $response['error'] = FALSE;
             $resposne[DB_VAR_NAME] = $user[DB_VAR_NAME];
             $resposne[DB_VAR_EMAIL] = $user[DB_VAR_EMAIL];
             $resposne[DB_VAR_API_KEY] = $user[DB_VAR_API_KEY];
             $resposne[DB_VAR_CREATED_AT] = $user[DB_VAR_CREATED_AT];
         } else {
             $response['error'] = TRUE;
             $repsonse['message'] = 'An error occured. Could not retrieve'
                     . 'user from database.';
         }         
     } else {
         $response['error'] = TRUE;
         $response['message'] = 'Login failed. Credentials incorrect.';
     }  
     echoResponse(200, $response);
     
 });
  
 
 function authenticate(\Slim\Route $route) {
     $headers = apache_request_headers();//array
     $response = array();
     $app = \Slim\Slim::getInstance();
     
     if (isset($headers['Authorization'])) {
         $db_handler = new DbHandler();
         $api_key = $headers['Authorization'];

         if ($db_handler->isValidApiKey($api_key)) {
             global $user_id_global;
             $user = $db_handler->getUserId($api_key);
             if ($user != NULL) {
                 $user_id_global = $user[DB_VAR_ID];
             }
         } else {
             $response['error'] = TRUE;
             $response['message'] = 'Invalid API Key';
             echoResponse(401, $response);
             $app->stop();
         }
     } else {
         $response['error'] = TRUE;
         $response['message'] = 'Api Key not provided in header.';
         echoResponse(400, $response);  
         $app->stop();
     }      
     
 }
 
  $app->run();

?>