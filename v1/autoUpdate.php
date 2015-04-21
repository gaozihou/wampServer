<?php

require_once 'C:\wamp\www\task_manager\include\DbHandler.php';
 


writeLogFile();
          
function writeLogFile(){
    $currentTime = time();
    $myfile = 'C:\wamp\www\task_manager\v1\UpdateLog.txt';
    $file_pointer = fopen($myfile,"a");
    fwrite($file_pointer,"hehe".$currentTime."\r\n");
    fclose($file_pointer);
}

?>