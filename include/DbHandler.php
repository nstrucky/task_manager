<?php

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
        
        if (count($userResult) > 0) {
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
        $statement->bind(':userEmail', $email);
        $statement->execute();
        $thisArray = $statement->fetch_all();
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
    
    
    
    
}


?>