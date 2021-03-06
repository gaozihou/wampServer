

<?php


// COMP4521
// ZHOU Xutong    20091184    xzhouaf@connect.ust.hk
// GAO Zihou          20090130    zgao@connect.ust.hk

class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();
        $password_hash = null;
        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    private function isGCMUserExists($regId, $user_ID) {
        $stmt = $this->conn->prepare("SELECT user_id from tblregistration WHERE user_id = '$user_ID' and registration_id = '$regId'");
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    private function isGCMDeviceChanged($regId, $user_ID) {
        $stmt = $this->conn->prepare("SELECT user_id from tblregistration WHERE user_id = '$user_ID' and registration_id <> '$regId'");
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    private function isGCMSwitchUser($regId, $user_ID) {
        $stmt = $this->conn->prepare("SELECT user_id from tblregistration WHERE user_id <> '$user_ID' and registration_id = '$regId'");
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, name, email, api_key, status, created_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $id = null; $name = null; $api_key = null; $status = null; $created_at = null;
            $stmt->bind_result($id, $name, $email, $api_key, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["user_id"] = $id;
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $api_key = null;
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $user_id = null;
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function postItem($user_id, $name, $description, $condition_name,
                    $category_name, $time_limit, $direct_buy_price, $current_price, 
                    $image_file_name, $user_name, $status) {
        
        //For calculating the create time and end time
        $now_time = time();
        $end_time = $now_time + (int)$time_limit;
        
        $stmt = $this->conn->prepare("INSERT INTO tasks(name, description, condition_name, category_name, time_limit, "
                . "direct_buy_price, current_price, image_file_name, user_name, user_id, create_time, end_time, status) VALUES ('$name', '$description', '$condition_name', "
                . "'$category_name', '$time_limit', '$direct_buy_price', '$current_price', "
                . "'$image_file_name', '$user_name', '$user_id', '$now_time', '$end_time', '$status')");
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
    
    public function createUserPortrait($user_id, $file_name) {
        
        $stmt = $this->conn->prepare("SELECT user_id from portrait WHERE user_id = '$user_id'");
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if($num_rows > 0){
            $stmt = $this->conn->prepare("UPDATE portrait p set p.portrait = '$file_name' WHERE p.user_id = '$user_id'");
            $result = $stmt->execute();
            $stmt->close();
        }else{
            $stmt = $this->conn->prepare("INSERT INTO portrait(user_id, portrait) VALUES('$user_id', '$file_name')");
            $result = $stmt->execute();
            $stmt->close();
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $task = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
    }
    
    public function getEndedActiveTask() {
        $currentTime = time();
        $stmt = $this->conn->prepare("SELECT t.* from tasks t WHERE t.end_time < '$currentTime' AND t.status = 0");
        if ($stmt->execute()) {
            $task = $stmt->get_result();
            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
    }
    
    public function setBidFinished($task_id){
        $time = time();
        $stmt = $this->conn->prepare("UPDATE tasks t set t.status = 1, t.end_time = '$time' WHERE t.id = '$task_id'");
        $stmt->execute();
        $stmt->close();
    }
    
    public function getSpecificUserBuy($task_id){
        $stmt = $this->conn->prepare("SELECT * from user_buy WHERE task_id = '$task_id'");
        if ($stmt->execute()) {
            $users = $stmt->get_result();
            $stmt->close();
            return $users;
        } else {
            return NULL;
        }        
    }
    
    public function getTargrtTasks($status, $category, $keywords, $order) {
        
        $prepare = "SELECT * from tasks WHERE id is not null";
        if($status != NULL){
            $prepare = $prepare . " and status = '$status'";
        }
        if($category != NULL){
            $prepare = $prepare . " and category_name = '$category'";
        }
        if($keywords != NULL){
            $prepare = $prepare . " and name like '%$keywords%'";
        }
        
        if($order == 0){
             $prepare = $prepare . " order by current_price asc";
        }
        if($order == 1){
             $prepare = $prepare . " order by current_price desc";
        }
        if($status==0&&$order == 2){
             $prepare = $prepare . " order by end_time asc";
        }
        if($status==0&&$order == 3){
             $prepare = $prepare . " order by end_time desc";
        }
        $stmt = $this->conn->prepare($prepare);
         
        if ($stmt->execute()) {
            $task = $stmt->get_result();
            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
    }
    public function getUserItem($user_id, $status, $identity, $order){
        
        if($identity == 0){
        
        $prepare = "SELECT distinct t.* from tasks t, user_tasks ut WHERE t.id = ut.task_id and ut.user_id = '$user_id'";
        
        if($status != NULL){
            $prepare = $prepare . " and t.status = '$status'";
        }
        }
        if($identity == 1){
        
        $prepare = "SELECT distinct t.* from tasks t, user_buy ub WHERE t.id = ub.task_id and ub.user_id = '$user_id'";
        
        if($status != NULL){
            $prepare = $prepare . " and t.status = '$status'";
        }
        }
         if($order == 0){
             $prepare = $prepare . " order by current_price asc";
        }
         if($order == 1){
             $prepare = $prepare . " order by current_price desc";
        }
         if($status==0&&$order == 2){
             $prepare = $prepare . " order by category_name asc";
        }
        if($status==0&&$order == 3){
             $prepare = $prepare . " order by category_name desc";
        }
        $stmt = $this->conn->prepare($prepare);
         
        if ($stmt->execute()) {
            $task = $stmt->get_result();
            $stmt->close();
            return $task;
        } else {
            return NULL;
        }
        
    }
 
    
    public function registerGCM($regId, $user_ID) {
        
        if (!$this->isGCMUserExists($regId, $user_ID)){ 
            if ($this->isGCMDeviceChanged($regId, $user_ID)){ 
                //$stmt = $this->conn->prepare("UPDATE tblregistration t set t.registration_id = '$regId' WHERE t.user_id = '$user_ID'");
                $stmt = $this->conn->prepare("INSERT INTO tblregistration (registration_id, user_id) values ('$regId','$user_ID')");
                $stmt->execute();
                $stmt->close(); 
            } else if ($this->isGCMSwitchUser($regId, $user_ID)){
                $stmt = $this->conn->prepare("UPDATE tblregistration t set t.user_id = '$user_ID' WHERE t.registration_id = '$regId'");
                $stmt->execute();
                $stmt->close(); 
            }else{
                $stmt = $this->conn->prepare("INSERT INTO tblregistration (registration_id, user_id) values ('$regId','$user_ID')");
                $stmt->execute();
                $stmt->close();
            }
            return true;
        }else{
            return false;
        }
    }
    public function getSingleUser($user_id){
        $stmt = $this->conn->prepare("SELECT u.phone_number, u.email, p.portrait from users u, portrait p WHERE u.id = '$user_id' and p.user_id = '$user_id'");
        $stmt->execute();
        $user= $stmt->get_result();
        $stmt->close();
        return $user;
        
    }
    public function getSingleItem($task_id){
        $stmt = $this->conn->prepare("SELECT * from tasks WHERE id = '$task_id'");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
        
        
        
        
    }
    
    public function getPortrait($user_id) {
        $stmt = $this->conn->prepare("SELECT p.* FROM portrait p WHERE p.user_id = '$user_id'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result;
        
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    
    public function getAllCondition() {
        $stmt = $this->conn->prepare("SELECT * FROM `condition`");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    
    public function getAllCategory() {
        $stmt = $this->conn->prepare("SELECT * FROM `category`");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    
    public function getAllGCMIds() {
        $stmt = $this->conn->prepare("SELECT * FROM tblregistration");
        $stmt->execute();
        $ids = $stmt->get_result();
        $stmt->close();
        return $ids;
    }
    
    public function getUserGCMId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM tblregistration WHERE user_id = '$id'");
        $stmt->execute();
        $ids = $stmt->get_result();
        $stmt->close();
        return $ids;
    }
       
    static public function placeBid($user_id,$bid_price,$item_id,$that){
        $date = date('Y-m-d H:i:s');
        $stmt = $that->conn->prepare("SELECT * FROM users WHERE id = '$user_id'");
        $stmt->execute();
        $user= $stmt->get_result();
        $stmt->close();
        $tmp = $user->fetch_assoc();
        $user_name = $tmp["name"];
        $stmt = $that->conn->prepare("INSERT INTO user_buy (user_id, task_id, bid_price, user_name, time) VALUES ('$user_id', '$item_id', '$bid_price', '$user_name','$date')");
        $result = $stmt->execute();
        $stmt->close();
        return $result;
        
    }
    
    public function updatePrice($user_id,$bid_price,$item_id){
        $user_name = DbHandler::findUserName($user_id, $this)["name"];
        if ($user_name == NULL){
            return FALSE;
        }
        $stmt = $this->conn->prepare("UPDATE tasks SET buyer_name = '$user_name', buyer_id = '$user_id', current_price = '$bid_price' WHERE id = '$item_id'");
        $result = $stmt->execute();
        $stmt->close();
        
        if($result){
            $bid_result = DbHandler::placeBid($user_id,$bid_price,$item_id,$this);
            
            return $bid_result;
            
            
        }
        
        return $result;
      
    }
    public function updateProfile($user_id,$phone_number, $new_password,$user_name){
              
        $prepare = "UPDATE users SET phone_number = '$phone_number'";
        
        if($new_password != NULL){
            $api_key = $this->generateApiKey();
            $password_hash = PassHash::hash($new_password);
            $prepare = $prepare . ", password_hash = '$password_hash', api_key = '$api_key'";
        }
        if($user_name != NULL){
            $prepare = $prepare . ", name = '$user_name'";
        }
        
        $prepare = $prepare . " WHERE id = '$user_id'";
        
        $stmt = $this->conn->prepare($prepare);
         
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
        
 
    
    public function validatePassword($user_id,$password) {
        
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE id = '$user_id'");

        $stmt->execute();
        $password_hash = null;
        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
        
    }
    
    public function validateBuyer($user_id,$item_id){
        $stmt = $this->conn->prepare("SELECT user_id FROM tasks WHERE id = '$item_id'");
        $stmt->execute();
        $result = $stmt->get_result();
        $owner_id = $result->fetch_assoc()["user_id"];
        $stmt->close();
        if($owner_id == $user_id){
            return FALSE;
        }
        return TRUE;
        
    }
    
    public function directBuy($user_id,$buy_price,$item_id){
        $user_name = DbHandler::findUserName($user_id, $this)["name"];
        if ($user_name == NULL){
            return FALSE;
        }
        $current_time = time();
        $stmt = $this->conn->prepare("UPDATE tasks SET buyer_name = '$user_name', buyer_id = '$user_id', current_price = '$buy_price', status = 1, end_time = '$current_time' WHERE id = '$item_id' AND status = 0");
        $result = $stmt->execute();
        $stmt->close();
        
        if($result){
            $buy_result = DbHandler::placeBid($user_id,$buy_price,$item_id,$this);
            
            return $buy_result;
            
            
        }
        
        return $result;
      
    }
    
    static public function findUserName($user_id, $that){

        $stmt = $that->conn->prepare("SELECT name FROM users WHERE id = '$user_id' ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
         
     }
     /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
    
    public function logout($user_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tblregistration t WHERE t.user_id = '$user_id'");
        $response = $stmt->execute();
        $stmt->close();
        return $response;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}
