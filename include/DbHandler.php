<?php

class DbHandler {
    
    
    
    
    
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
    
}


?>