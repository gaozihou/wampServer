<?php

require_once 'C:\wamp\www\task_manager\include\DbHandler.php';
 
$db = new DbHandler();
$result = $db -> getEndedActiveTesk();
if($result != NULL){
    while($tmp = $result -> fetch_assoc()){
        if($tmp["buyer_id"] == NULL){
            $db -> setBidFinished($tmp["id"]);
            pushMessageToSingleUser("Your item: ".$tmp["name"]." times out, no body buy it. Please try again!", $tmp["user_id"]);
        }else{
            $db -> setBidFinished($tmp["id"]);            
            pushMessageToSingleUser("Your item: ".$tmp["name"]." successhully sold with price of ".$tmp["current_price"], $tmp["user_id"]);
            $usersRelated = $db ->getSpecificUserBuy($tmp["id"]);
            while($tmpUser = $usersRelated -> fetch_assoc()){
                pushMessageToSingleUser("Item: ".$tmp["name"]." is sold out to buyer ".$tmp["buyer_name"]." with price of ".$tmp["current_price"].", click to see detail", $tmpUser["user_id"]);
            }
        }
    }
}

writeLogFile();
          
function writeLogFile(){
    $currentTime = time();
    $myfile = 'C:\wamp\www\task_manager\v1\UpdateLog.txt';
    $file_pointer = fopen($myfile,"a");
    fwrite($file_pointer,"hehe".$currentTime."\r\n");
    fclose($file_pointer);
}

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

?>