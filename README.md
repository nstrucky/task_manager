# PHP/SLIM framework reference repo
Tutorial: https://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/ 

# DB Setup

CREATE DATABASE task_manager;
 
USE task_manager;
 
CREATE TABLE IF NOT EXISTS `users` (
  `unique_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` text NOT NULL,
  `api_key` varchar(32) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unique_id`),
  UNIQUE KEY `email` (`email`)
);
 
CREATE TABLE IF NOT EXISTS `tasks` (
  `unique_id` int(11) NOT NULL AUTO_INCREMENT,
  `task` text NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unique_id`)
);
 
CREATE TABLE IF NOT EXISTS `user_tasks` (
  `unique_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  PRIMARY KEY (`unique_id`),
  KEY `user_id` (`user_id`),
  KEY `task_id` (`task_id`)
);
 
ALTER TABLE  `user_tasks` ADD FOREIGN KEY (  `user_id` ) REFERENCES  `task_manager`.`users` (
`unique_id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
 
ALTER TABLE  `user_tasks` ADD FOREIGN KEY (  `task_id` ) REFERENCES  `task_manager`.`tasks` (
`unique_id`
) ON DELETE CASCADE ON UPDATE CASCADE ;