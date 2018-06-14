<?php
/**
 * Description of string_vars
 *
 * @author Nick
 */

/**
 * Database configuration
 */
define('DB_SERVER', 'localhost');
define('DB_DATABASE', 'task_manager');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_SERVER_PORT', '3306');

define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);

define('DB_TABLE_USERS', 'users');
define('DB_TABLE_TASKS', 'tasks');
define('DB_TABLE_USERTASKS', 'user_tasks');
define('DB_VAR_ID', 'id');
define('DB_VAR_NAME', 'name');
define('DB_VAR_EMAIL', 'email');
define('DB_VAR_PASSWORD_HASH', 'password_hash');
define('DB_VAR_API_KEY', 'api_key');
define('DB_VAR_STATUS', 'status');
define('DB_VAR_CREATED_AT', 'created_at');
define('DB_VAR_USER_ID', 'user_id');
define('DB_VAR_TASK_ID', 'task_id');

?>