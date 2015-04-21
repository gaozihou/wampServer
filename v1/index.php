<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;
//This is really interesting                

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(/*\Slim\Route $route*/) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

        
 $app->post('/searchItems', function() use ($app) {
  
            $response = array();
            $db = new DbHandler();
           
            
            $status = $app->request()->post('status');
            $category = $app->request()->post('category');
            $keywords = $app->request()->post('keywords');
            $order = $app->request()->post('order');
            
            
            
            // fetching all user tasks
            $result = $db->getTargrtTasks($status, $category, $keywords, $order);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["name"] = $task["name"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                $tmp["description"] = $task["description"];
                $tmp["conditionName"] = $task["condition_name"];
                $tmp["categoryName"] = $task["category_name"];
                $tmp["timeLimit"] = $task["time_limit"];
                $tmp["directBuyPrice"] = $task["direct_buy_price"];
                $tmp["currentPrice"] = $task["current_price"];
                $tmp["imageFileName"] = $task["image_file_name"];
                $tmp["userName"] = $task["user_name"];
                $tmp["userID"] = $task["user_id"];
                $tmp["timeLeft"] = $task["end_time"] - time();
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });   
        
$app->post('/upload', function() {
    
            $array = array();
    
            $base_path = "./uploads/"; //接收文件目录  
            $target_path = $base_path . basename ( $_FILES ['uploadfile'] ['name'] ); 
            if (move_uploaded_file ( $_FILES ['uploadfile'] ['tmp_name'], $target_path )) {  
                $array = array ("code" => "1", "message" => "Image uploaded successfully!" );  
            } else {  
                $array = array ("code" => "0", "message" => "There was an error uploading the image, please try again!" );  
            }  
            echoRespnse(201, $array);
        });
        
$app->post('/uploadPortrait', function() {
    
            $array = array();
    
            $base_path = "./portrait/"; //接收文件目录  
            $target_path = $base_path . basename ( $_FILES ['uploadfile'] ['name'] ); 
            if (move_uploaded_file ( $_FILES ['uploadfile'] ['tmp_name'], $target_path )) {  
                $array = array ("code" => "1", "message" => "Portrait uploaded successfully!" );  
            } else {  
                $array = array ("code" => "0", "message" => "There was an error uploading the portrait, please try again!" );  
            }  
            echoRespnse(201, $array);
        });
        
$app->post('/userPortrait', 'authenticate', function() use ($app) {

            global $user_id;
            $response = array();

            // reading post params
            $file_name = $app->request->post('file_name');

            $db = new DbHandler();
            $res = $db->createUserPortrait($user_id, $file_name);

            if ($res == true) {
                $response["error"] = false;
                $response["message"] = "UserPortrait created successfully";
            } else {
                $response["error"] = true;
                $response["message"] = "Oops! UserPortrait Failed to create";
            }
            // echo json response
            echoRespnse(201, $response);
        });
        
$app->post('/registerGCM', 'authenticate', function() use ($app)  {
    
            global $user_id;
    
            $regId = $app->request->post('regId'); 
            $response = array();
            $db = new DbHandler();
            $res = $db->registerGCM($regId, $user_id);
            
            if ($res == true) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered for GCM";
            } else {
                $response["error"] = true;
                $response["message"] = "Oops! User Already Registered...";
            }
            // echo json response
            echoRespnse(201, $response);
        });

$app->post('/getSingleUser',function() use($app){
            $response = array();
            $db = new DbHandler();
           
            
            $user_id = $app->request()->post('user_id');
                   
            // fetching all user tasks
            $result = $db->getSingleUser($user_id);

            $response["error"] = false;
            
            if($user = $result->fetch_assoc()){
            
            $response["phoneNumber"] = $user["phone_number"];
            $response["email"] = $user["email"];
            $response["portrait"] = $user["portrait"];
            }
            
            echoRespnse(200, $response);
            
    
});
        
$app->post('/getSingleItem',function() use($app){
            $response = array();
            $db = new DbHandler();
           
            
            $task_id = $app->request()->post('task_id');
                   
            // fetching all user tasks
            $result = $db->getSingleItem($task_id);

            // looping through result and preparing tasks array
            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["name"] = $task["name"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                $tmp["description"] = $task["description"];
                $tmp["conditionName"] = $task["condition_name"];
                $tmp["categoryName"] = $task["category_name"];
                $tmp["timeLimit"] = $task["time_limit"];
                $tmp["directBuyPrice"] = $task["direct_buy_price"];
                $tmp["currentPrice"] = $task["current_price"];
                $tmp["imageFileName"] = $task["image_file_name"];
                $tmp["userName"] = $task["user_name"];
                $tmp["userID"] = $task["user_id"];
                $tmp["timeLeft"] = $task["end_time"] - time();
                array_push($response["tasks"], $tmp);
            }
              
            

            echoRespnse(200, $response);
    
});
        
//This is only for testing!!!
$app->post('/pushMessages', function() use ($app) {
            
            $googleResult = pushMessageToAllUsers("This is the new way to post the notification. OH MY GOD WHY THERE IS STILL BUG HERE!");
            // echo json response
            echoRespnse(201, $googleResult);
                
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['user_id'] = $user['user_id'];
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */
        
$app->get('/serverTest', function() {
            $response = array();
            $response["error"] = false;
            $response["message"] = "Server OK";
            echoRespnse(200, $response);
        });
             
$app->get('/logout', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            $result = $db->logout($user_id);
            if($result){
                $response["error"] = false;
                $response["message"] = "You have logged out successfully";
            }else{
                $response["error"] = true;
                $response["message"] = "Oh something wrong happened...";
            }
            echoRespnse(200, $response);
        });
        
        
$app->post('/directBuy', 'authenticate', function() use ($app) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            
            $buy_price = $app->request()->post('buy_price');
            $item_id = $app->request()->post('item_id');

            $result = $db->directBuy($user_id,$buy_price,$item_id);

            if ($result) {
                $response["error"] = false;
                $response["message"] = "Item bought successfully";
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to buy the item. Please try again";
                echoRespnse(200, $response);
            }            

        });
 
$app->post('/placeBid', 'authenticate', function() use ($app) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            
            $bid_price = $app->request()->post('bidPrice');
            $item_id = $app->request()->post('item_id');

            $result = $db->updatePrice($user_id,$bid_price,$item_id);

            if ($result) {
                $response["error"] = false;
                $response["message"] = "Item bidded successfully";
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to place bid. Please try again";
                echoRespnse(200, $response);
            }            

        });
        
$app->get('/getPortrait', 'authenticate', function() {
            global $user_id;
            //$response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getPortrait($user_id);
            $response["error"] = true;
            while ($single = $result->fetch_assoc()) {
                $response["error"] = false;
                $response["file_name"] = $single["portrait"];
            }
            echoRespnse(200, $response);
        });

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
//TODO
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });
        
$app->get('/conditionAndCategory', function() {
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $resultCondition = $db->getAllCondition();
            $resultCategory = $db->getAllCategory();

            $response["error"] = false;
            $response["condition"] = array();
            $response["category"] = array();

            // looping through result and preparing tasks array
            while ($single = $resultCondition->fetch_assoc()) {
                array_push($response["condition"], $single);
            }
            
            while ($single = $resultCategory->fetch_assoc()) {
                array_push($response["category"], $single);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/postItem', 'authenticate', function() use ($app) {
            // check for required params
            // verifyRequiredParams(array('task'));

            $response = array();
            $name = $app->request->post('name');
            $description = $app->request->post('description');
            $condition_name = $app->request->post('condition_name');
            $category_name = $app->request->post('category_name');
            $time_limit = $app->request->post('time_limit');
            $direct_buy_price = $app->request->post('direct_buy_price');
            $current_price = $app->request->post('current_price');
            $image_file_name = $app->request->post('image_file_name');
            $user_name = $app->request->post('user_name');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->postItem($user_id, $name, $description, $condition_name,
                    $category_name, $time_limit, $direct_buy_price, $current_price, 
                    $image_file_name, $user_name);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Item posted successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to post item. Please try again";
                echoRespnse(200, $response);
            }            
        });

$app->post('/itemsByUser', 'authenticate', function() use($app) {
    
        global $user_id;
        $db = new DbHandler();

        $status = $app->request()->post('status');
        $identity = $app->request()->post('identity');
        $order = $app->request()->post('order');
        
        $result = $db->getUserItem($user_id, $status, $identity, $order);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["name"] = $task["name"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                $tmp["description"] = $task["description"];
                $tmp["conditionName"] = $task["condition_name"];
                $tmp["categoryName"] = $task["category_name"];
                $tmp["timeLimit"] = $task["time_limit"];
                $tmp["directBuyPrice"] = $task["direct_buy_price"];
                $tmp["currentPrice"] = $task["current_price"];
                $tmp["imageFileName"] = $task["image_file_name"];
                $tmp["userName"] = $task["user_name"];
                $tmp["userID"] = $task["user_id"];
                $tmp["timeLeft"] = $task["end_time"] - time();
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
    
    
    
});

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    //$request_params = $_REQUEST;
    $request_params = filter_input_array(\INPUT_POST);
    // Handling PUT request params
    //if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    if (filter_input_array(\INPUT_SERVER)['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();

function pushMessageToAllUsers($rawMessage){
            $message = $rawMessage;
            $messageToSend = array("Notice" => $message);
            $registration_ids = array();
     
            $db = new DbHandler();
            $result = $db->getAllGCMIds();     
            while($row = $result->fetch_assoc()){
                array_push($registration_ids, $row['registration_id']);
            }
            // Set POST variables
            $url = 'https://android.googleapis.com/gcm/send';
   
            $fields = array(
                'registration_ids' => $registration_ids,
                'data' => $messageToSend,
            );
            $headers = array(
                'Authorization: key=AIzaSyDdZD8LfeHc3qRHTwgJnXH1ccD4WqiKtNM',
                'Content-Type: application/json'
            );
            
            // Open connection
             $ch = curl_init();
   
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
   
            // Execute post
            $googleResult = curl_exec($ch);
            if ($googleResult === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
   
            // Close connection
            curl_close($ch);
            return $googleResult;
}

function pushMessageToSingleUser($rawMessage, $user_id){
            $message = $rawMessage;
            $messageToSend = array("Notice" => $message);
            $registration_ids = array();
     
            $db = new DbHandler();
            $result = $db->getUserGCMId($user_id);     
            while($row = $result->fetch_assoc()){
                array_push($registration_ids, $row['registration_id']);
            }
            // Set POST variables
            $url = 'https://android.googleapis.com/gcm/send';
   
            $fields = array(
                'registration_ids' => $registration_ids,
                'data' => $messageToSend,
            );
            $headers = array(
                'Authorization: key=AIzaSyDdZD8LfeHc3qRHTwgJnXH1ccD4WqiKtNM',
                'Content-Type: application/json'
            );
            
            // Open connection
             $ch = curl_init();
   
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
   
            // Execute post
            $googleResult = curl_exec($ch);
            if ($googleResult === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
   
            // Close connection
            curl_close($ch);    
            return $googleResult;
}
