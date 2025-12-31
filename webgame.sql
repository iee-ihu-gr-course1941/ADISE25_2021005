
-- Creation and selection of the database
CREATE DATABASE IF NOT EXISTS `webgame`;
USE `webgame`;


-- Table that handles multiple games
CREATE TABLE IF NOT EXISTS `games` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `p_turn` enum('W','B') DEFAULT 'W',
  `dice_roll` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


-- Board Table
CREATE TABLE IF NOT EXISTS `board` (
  `game_id` INT NOT NULL,
  `point_id` INT NOT NULL,
  `piece_color` enum('W','B') DEFAULT NULL,
  `point_count` INT DEFAULT 0,
  PRIMARY KEY (`game_id`,`point_id`),
  CONSTRAINT `fkey_board` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


-- Starting Board Table
CREATE TABLE IF NOT EXISTS `empty_board` (
  `point_id` INT NOT NULL,
  `piece_color` enum('W','B') DEFAULT NULL,
  `point_count` INT DEFAULT 0,
  PRIMARY KEY (`point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

-- Insert the Clean Board
INSERT INTO `empty_board` (`point_id`, `piece_color`, `point_count`) VALUES
	(1, NULL, 0),
	(2, NULL, 0),
	(3, NULL, 0),
	(4, NULL, 0),
	(5, NULL, 0),
	(6, NULL, 0),
	(7, NULL, 0),
	(8, NULL, 0),
	(9, NULL, 0),
	(10, NULL, 0),
	(11, NULL, 0),
	(12, 'B', 15),
	(13, NULL, 0),
	(14, NULL, 0),
	(15, NULL, 0),
	(16, NULL, 0),
	(17, NULL, 0),
	(18, NULL, 0),
	(19, NULL, 0),
	(20, NULL, 0),
	(21, NULL, 0),
	(22, NULL, 0),
	(23, NULL, 0),
	(24, 'W', 15);


-- Status Table
CREATE TABLE IF NOT EXISTS `game_status` (
  `game_id` INT NOT NULL,
  `current_status` enum('not active','initialized','started','ended','aborted') NOT NULL DEFAULT 'not active',
  `result` enum('W','B') DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`game_id`),
  CONSTRAINT `fkey_game_status` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


-- Players Table
CREATE TABLE IF NOT EXISTS `players` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` varchar(20) DEFAULT NULL,
  `game_id` INT DEFAULT NULL,
  `piece_color` enum('W','B') DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `last_action` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  CONSTRAINT `fkey_players` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


-- Clean Board Procedure
DELIMITER //
CREATE PROCEDURE `clean_board`(IN target_game_id INT)
BEGIN 
REPLACE INTO board (game_id, point_id, piece_color, point_count) 
SELECT 
target_game_id,point_id, piece_color, point_count
FROM empty_board;
END//
DELIMITER ;