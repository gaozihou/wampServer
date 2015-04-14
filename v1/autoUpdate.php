<?php


require_once 'C:\wamp\www\task_manager\include\DbHandler.php';

/*
$message = "Test Message";
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
 
*/

$currentTime = time();

file_put_contents('C:\wamp\www\task_manager\v1\1.txt',$currentTime);
          
?>
                


