<?php
$ch = curl_init();  
			$curl_opt = array(  
    		CURLOPT_URL, 'http://localhost:8000/task_manager/include/TCP.php', 
    		CURLOPT_RETURNTRANSFER,1,  
    		CURLOPT_TIMEOUT,1  
			);  
			curl_setopt_array($ch, $curl_opt);  
			curl_exec($ch);  
			curl_close($ch);  