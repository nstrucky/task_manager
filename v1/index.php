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
          $request_body = $app->request()->getBody();
          parse_str($request_body, $request_params);//this only seems to work for url encoded requests from client
          
      }
      
      foreach($required_fields as $field) {
          if (!isset($request_params[$field]) || 
                  strlen(trim($request_params[$field])) <= 0) {
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
  $app->post(HTTP_PATH_REGISTER, function() use ($app) {
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
        
        
        
 $app->post(HTTP_PATH_LOGIN, function() use ($app) {
    
     verifyRequiredParams(array(HTTP_PARAM_EMAIL, HTTP_PARAM_PASSWORD));
     
     $password = $app->request->post(HTTP_PARAM_PASSWORD);
     $email = $app->request->post(HTTP_PARAM_EMAIL);
     $response = array();
     
     $db_handler = new DbHandler();
     
     if ($db_handler->checkLogin($email, $password)) {
         $user = $db_handler->getUserByEmail($email);
                 
         if ($user != NULL) {
             $response['error'] = FALSE;
             $response[DB_VAR_NAME] = $user[DB_VAR_NAME];
             $response[DB_VAR_EMAIL] = $user[DB_VAR_EMAIL];
             $response[DB_VAR_API_KEY] = $user[DB_VAR_API_KEY];
             $response[DB_VAR_CREATED_AT] = $user[DB_VAR_CREATED_AT];
         } else {
             $response['error'] = TRUE;
             $response['message'] = 'An error occured. Could not retrieve'
                     . 'user from database.';
         }         
     } else {
         $response['error'] = TRUE;
         $response['message'] = 'Login failed. Credentials incorrect.';
     }  
     echoResponse(200, $response);
     
 });
  
 
 /**
 * Creates a new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
 $app->post(HTTP_PATH_TASKS, 'authenticate', function() use ($app) {
     verifyRequiredParams(array(HTTP_PARAM_TASK));
     
     $response = array();
     
     $task = $app->request->post(HTTP_PARAM_TASK);
     
     global $user_id_global;
     $db_handler = new DbHandler();
     
     $task_id = $db_handler->createTask($user_id_global, $task);
     
     if ($task_id != NULL) {
         $response['error'] = FALSE;
         $response['message'] = 'Task created successfully';
         $response['task_id'] = $task_id;
     } else {
         $response['error'] = TRUE;
         $response['message'] = 'Failed to create task.';
     }
     echoResponse(201, $response);
 });
 
 /**
 * Listing all tasks of particular user
 * method GET
 * url /tasks          
 */
 $app->get(HTTP_PATH_TASKS, 'authenticate', function() {
 
     global $user_id_global;
     $response = array();

     $db_handler = new DbHandler();
     
     $result = $db_handler->getAllUserTasks($user_id_global);
     
     $response['error'] = FALSE;
     $response['tasks'] = array();
          
     foreach ($result as $row) {
         array_push($response['tasks'], $row);
     }
     echoResponse(200, $response);
 });
 
 /**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
 $app->get(HTTP_PATH_SINGLE_TASK, 'authenticate', function($task_id) {
 
     global $user_id_global;
     
     $response = array();
     $db_handler = new DbHandler();
     
     $response['task'] = array();
     
     $task_array = $db_handler->getTask($task_id, $user_id_global);
     
     if ($task_array != NULL) {
         $response['error'] = FALSE;
         $response['message'] = 'Task successfully retrieved';
         $response['task'] = $task_array;
         echoResponse(200, $response);
     } else {
         $response['error'] = TRUE;
         $response['message'] = 'Requested task does not exist';
         echoResponse(404, $response);
     }
     

 });
 
 
 /**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
 $app->put(HTTP_PATH_SINGLE_TASK, 'authenticate', function($task_id) use($app) {
     verifyRequiredParams(array('task','status'));
     
     global $user_id_global;
     $task = $app->request->put('task');
     $status = $app->request->put('status');
     $db_handler = new DbHandler();
     $response = array();
     
     $success = 
             $db_handler->updateTask($user_id_global, $task_id, $task, $status);
     if ($success) {
         $response['error'] = FALSE;
         $response['message'] = 'Task updated successfully';
     } else {
         $response['error'] = TRUE;
         $response['message'] = 'Error: could not update specified task';
         $message = 'There was and error Task = ' . $task . 
                 ' Status = ' . $status .
                 ' User ID = ' . $user_id_global .
                 ' Task ID = ' . $task_id;
         
         $response['details'] = $message;
     }
     
     echoResponse(200, $response);
     
 });
 
 
 function authenticate(\Slim\Route $route) {
     $headers = apache_request_headers();//array
     $response = array();
     $app = \Slim\Slim::getInstance();
     
     if (isset($headers['authorization'])) {
         $db_handler = new DbHandler();
         $api_key = $headers['authorization'];

         if ($db_handler->isValidApiKey($api_key)) {
             global $user_id_global;
             $this_user_id = $db_handler->getUserId($api_key);
             if ($this_user_id != NULL) {
                 $user_id_global = $this_user_id;
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
 
 $app->delete(HTTP_PATH_SINGLE_TASK, 'authenticate', function($task_id) use($app) {
    global $user_id_global;
    $response = array();
    $db_handler = new DbHandler();
    
    $success = $db_handler->deleteTask($user_id_global, $task_id);
    
    if ($success) {
        $response['error'] = FALSE;
        $response['message'] = 'Task ' . $task_id . ' successfully deleted';
    } else {
        $response['error'] = TRUE;
        $response['message'] = 'Could not delete task ' . $task_id;
    }
    echoResponse(200, $response);
     
 });
 
 
 
  $app->run();

?>