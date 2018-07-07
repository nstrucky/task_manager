<?php

require_once '../include/db_connect.php';

class DbHandler {
         
    /* ------------- `users` table method ------------------ */
    
    /**
     * Creates a new user
     * @global PDO $db
     * @param String $name
     * @param String $email
     * @param String $password
     * @return array -- ?
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();
        global $db;
        
        if (!$this->userExists($email)) {
            
            $password_hash = PassHash::hash($password);
            
            $api_key = $this->generateApiKey();
            
            $query = 'INSERT INTO ' . DB_TABLE_USERS . '(' .
                    DB_VAR_NAME . ', ' .
                    DB_VAR_EMAIL . ', ' .
                    DB_VAR_PASSWORD_HASH . ', ' .
                    DB_VAR_API_KEY . ') ' .
                    'VALUES (:name, :email, :password_hash, :api_key)';
            
            $stmt = $db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password_hash', $password_hash);
            $stmt->bindValue(':api_key', $api_key);
            $result = $stmt->execute();
            $stmt->closeCursor();
            
            if ($result) {
                return USER_CREATED_SUCCESSFULLY;
            } else {
                return USER_CREATE_FAILED;
            }
            
        } else {
            return USER_ALREADY_EXISTED;
        }
        return $response; //Not sure why we are doing this...
    }
    
    /**
     * Checks user login input
     * @global PDO $db
     * @param String $email
     * @param String $password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        global $db;
        //Selecting only password_hash column here, may need to edit code below
        $query = 'SELECT ' . DB_VAR_PASSWORD_HASH .
                ' FROM ' . DB_TABLE_USERS .
                ' WHERE ' . DB_VAR_EMAIL .' = :email';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $userResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if ($userResult != NULL && count($userResult) > 0) {
            $pass_hash = $userResult[DB_VAR_PASSWORD_HASH];
             
            if (PassHash::check_password($pass_hash, $password)) {
                return TRUE;
            } else {
                return FALSE;
            }
            
        } else {
            return FALSE;
        }
    }
    
    /**
     * Gets the user's info by email
     * @param String $email
     * @return array - returns an associative array representing the user's 
     * data
     */
    public function getUserByEmail($email) {
        global $db;
        $query = 'SELECT ' . DB_VAR_PASSWORD_HASH . 
                ', ' . DB_VAR_NAME .
                ', ' . DB_VAR_EMAIL .
                ', ' . DB_VAR_API_KEY . 
                ', ' . DB_VAR_STATUS .
                ', ' . DB_VAR_CREATED_AT .                
                ' FROM ' . DB_TABLE_USERS .
                ' WHERE ' . DB_VAR_EMAIL .' = :email';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':email', $email);
        
        if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $user;
        } else {
            return NULL;
        }
    }
    
    /**
     * Retrieves the api_key stored for a given user (id).
     * @global PDO $db
     * @param String $user_id (primary key in user table)
     * @return String
     */
    public function getApiKeyById($user_id) {
        global $db;
        $query = 'SELECT ' . DB_VAR_API_KEY . 
                ' FROM ' . DB_TABLE_USERS . 
                ' WHERE ' . DB_VAR_ID . ' = :id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $user_id);
        $stmt->execute();
        $api_key_array = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (count($api_key_array) > 0) {
            return $api_key_array[DB_VAR_API_KEY];
        } else {
            return NULL;
        }
    }
    
    /**
     * Retrieve user's id by api key in users table
     * @global PDO $db
     * @param String $api_key
     * @return int
     */
    public function getUserId($api_key) {
        global $db;
        $query = 'SELECT ' . DB_VAR_ID . 
                ' FROM ' . DB_TABLE_USERS . 
                ' WHERE ' . DB_VAR_API_KEY . ' = :api_key';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':api_key', $api_key);
        $stmt->execute();
        $id_array = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (count($id_array) > 0) {
            return $id_array[DB_VAR_ID];
        } else {
            return 0;
        }
    }
    
        /**
     * Validates api key, checking whether the argument is associated with a 
     * valid id in the users table.
     * @global PDO $db
     * @param String $api_key
     * @return boolean - returns false if array returned by db has 0 rows 
     * or if the ID returned is NULL or 0.
     */
    public function isValidApiKey($api_key) {
        global $db;
        $query = 'SELECT ' . DB_VAR_ID .
                ' FROM ' . DB_TABLE_USERS . 
                ' WHERE ' . DB_VAR_API_KEY . ' = :api_key';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':api_key', $api_key);
        $stmt->execute();
        $id_array = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (count($id_array) > 0) {
            $id = $id_array[DB_VAR_ID];
            return $id != null && $id > 0;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Determines if user exists using their email address
     * @global PDO $db
     * @param string $email
     * @return boolean
     */
    private function userExists($email) {
        global $db;
        $query = 'SELECT ' . DB_VAR_ID .
                ' FROM ' . DB_TABLE_USERS . 
                ' WHERE ' . DB_VAR_EMAIL .
                ' = :userEmail';
        $statement = $db->prepare($query);
        $statement->bindValue(':userEmail', $email);
        $statement->execute();
        $thisArray = $statement->fetchAll();
        $num_rows = count($thisArray);
        $statement->closeCursor();
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
     * Creates task by creating entry in tasks table
     * @global PDO $db
     * @param String $user_id
     * @param String $task
     * @return String - returns the new task id
     */
    public function createTask($user_id, $task) {
        global $db;
        $query = 'INSERT INTO ' . DB_TABLE_TASKS . '(' . DB_VAR_TASK . ')' .
                 ' VALUES(:task)';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':task', $task);
        $result = $stmt->execute();

        if ($result) {
            $new_task_id = $db->lastInsertId();
            $stmt->closeCursor();
            
            $res = $this->createUserTask($user_id, $new_task_id);
            
            if ($res) {
                return $new_task_id;
            } else {
                return NULL;// should return 0?
            }
        } else {
            $stmt->closeCursor();
            return NULL;
        }
    }
    
    /**
     * Retrieves a single task where $task_id equals the id on tasks table,
     * the task_id on user_tasks table is equal to id on tasks table, 
     * and $user_id is equal to user_id on user_tasks table. 
     * @global PDO $db
     * @param String $task_id
     * @param String $user_id
     * @return array - returns associative array representing a task data row
     */
    public function getTask($task_id, $user_id) {
//        "SELECT t.id, t.task, t.status, t.created_at FROM tasks t, user_tasks
//         ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?"
        global $db;
        $query = 'SELECT' .
                ' t.' . DB_VAR_ID . ',' .
                ' t.' . DB_VAR_TASK . ',' .
                ' t.' . DB_VAR_STATUS . ',' .
                ' t.' . DB_VAR_CREATED_AT . 
                ' FROM ' . DB_TABLE_TASKS . ' t,' . DB_TABLE_USERTASKS . ' ut' .
                ' WHERE' .
                ' t.' . DB_VAR_ID . ' = :task_id' . 
                ' AND ' . 'ut.' . DB_VAR_TASK_ID . ' = ' . 't.' . DB_VAR_ID . 
                ' AND ' . 'ut.' . DB_VAR_USER_ID . ' = :user_id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':task_id', $task_id);
        $stmt->bindValue(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            return $task;
        } else {
            return NULL;
        }
    }
    
    /**
     * Retrieves all user's tasks based on user_id input - tasks retrieved 
     * correspond to task_id's with $user_id in user_tasks table. 
     * @global PDO $db
     * @param String $user_id
     * @return array - 
     */
    public function getAllUserTasks($user_id) {
//        "SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id 
//        AND ut.user_id = ?"
        global $db;
        $query = 'SELECT t.* FROM ' . DB_TABLE_TASKS . ' t,' .
                DB_TABLE_USERTASKS . ' ut' .
                ' WHERE ' 
                . 't.' . DB_VAR_ID . ' =' .
                ' ut.' . DB_VAR_TASK_ID . 
                ' AND' .
                ' ut.' . DB_VAR_USER_ID . ' = :user_id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $tasks;
             
    }
    
    /**
     * Updates and existing task in tasks table corresponding to the $task_id
     * which should match the task_id in user_tasks table having user_id of 
     * $user_id method input. 
     * @global PDO $db
     * @param String $user_id
     * @param String $task_id
     * @param String $task
     * @param String $status
     * @return boolean - rows updated is greater than 0
     */
    public function updateTask($user_id, $task_id, $task, $status) {
//        UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? 
//        WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?
        global $db;
        $query = 'UPDATE ' . DB_TABLE_TASKS . ' t, ' . DB_TABLE_USERTASKS . ' ut' .
                ' SET' .
                ' t.' . DB_VAR_TASK . ' = :task,' .
                ' t.' . DB_VAR_STATUS . ' = :status' .
                ' WHERE' .
                ' t.' . DB_VAR_ID . ' = :task_id' . 
                ' AND' . 
                ' t.' . DB_VAR_ID . ' = ut.' . DB_VAR_TASK_ID .
                ' AND' .
                ' ut.' .DB_VAR_USER_ID . ' = :user_id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':task', $task);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':task_id', $task_id);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
       $rows = $stmt->rowCount();
       $stmt->closeCursor();
       return $rows > 0;
    }
    
    /**
     * Deletes a task from tasks table.  Task deleted corresponds to the 
     * $task_id entered as an argument that is equal to the task_id on 
     * user_tasks table with the $user_id input.  
     * @global PDO $db
     * @param type $user_id
     * @param type $task_id
     * @return boolean - success if rows affected > 0
     */
    public function deleteTask($user_id, $task_id) {
//            "DELETE t FROM tasks t, user_tasks ut WHERE t.id = ?
//             AND ut.task_id = t.id AND ut.user_id = ?"
        
        global $db;
        $query = 'DELETE t FROM ' . DB_TABLE_TASKS . ' t,' . 
                DB_TABLE_USERTASKS . ' ut' .
                ' WHERE' .
                ' t.' . DB_VAR_ID . ' = :task_id' . 
                ' AND' .
                ' ut.' . DB_VAR_TASK_ID . ' = t.' . DB_VAR_ID . 
                ' AND' . 
                ' ut.' . DB_VAR_USER_ID . ' = :user_id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':task_id', $task_id);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $deleted_rows = $stmt->rowCount();
        $stmt->closeCursor();
        return $deleted_rows > 0;
        
    }
    
    /* ------------- `user_tasks` table method ------------------ */
    /**
     * Assigns a task to a user by creating entry in user_tasks table
     * @global PDO $db
     * @param String $user_id
     * @param String $task_id
     * @return boolean - success or failure
     */
    public function createUserTask($user_id, $task_id) {
        global $db;
        $query = 'INSERT INTO ' . DB_TABLE_USERTASKS .
                '(' . DB_VAR_USER_ID . ', ' . DB_VAR_TASK_ID . ')' .
                ' VALUES (:user_id, :task_id)';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':task_id', $task_id);
        $result = $stmt->execute();
        $stmt->closeCursor();
        return $result;
    }
    
    
}


?>