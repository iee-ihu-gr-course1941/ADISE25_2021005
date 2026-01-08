
-- Creation and selection of the database
DROP DATABASE IF EXISTS `webgame`;
CREATE DATABASE IF NOT EXISTS `webgame`;
USE `webgame`;

-- Board Table
DROP TABLE IF EXISTS `board`;
CREATE TABLE IF NOT EXISTS `board` (
  `point_id` INT NOT NULL,
  `piece_color` enum('W','B') DEFAULT NULL,
  `point_count` INT DEFAULT 0,
  PRIMARY KEY (`point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


-- Starting Board Table
DROP TABLE IF EXISTS `empty_board`;
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
DROP TABLE IF EXISTS `game_status`;
CREATE TABLE IF NOT EXISTS `game_status` (
  `current_status` enum('not active','initialized','started','ended','aborted') NOT NULL DEFAULT 'not active',
  `id` INT NOT NULL DEFAULT 1,
  `current_turn` ENUM('W','B') DEFAULT NULL,
  `first_dice` TINYINT DEFAULT NULL,
  `second_dice` TINYINT DEFAULT NULL,
  `white_collected` TINYINT DEFAULT 0,
  `black_collected` TINYINT DEFAULT 0,
  `result_of_match` enum('W','B') DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

INSERT IGNORE INTO `game_status` (current_status, id) VALUES ('not active',1);

-- Players Table
DROP TABLE IF EXISTS `players`;
CREATE TABLE IF NOT EXISTS `players` (
  `username` varchar(20) DEFAULT NULL,
  `piece_color` enum('W','B') DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `last_action` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`piece_color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


-- Clean Board Procedure
DROP PROCEDURE IF EXISTS `clean_board`;
DELIMITER //
CREATE PROCEDURE `clean_board`()
BEGIN 
REPLACE INTO board (point_id, piece_color, point_count) 
SELECT * FROM empty_board;

UPDATE game_status
SET current_status='started',
    current_turn='W',
    first_dice=NULL,
    second_dice=NULL,
    white_collected=0,
    black_collected=0,
    result_of_match=NULL
WHERE id=1;
    
END//
DELIMITER ;

-- Piece moving procedure
DROP PROCEDURE IF EXISTS `move_piece`;
DELIMITER // 
CREATE PROCEDURE `move_piece`(IN p_from INT, IN p_to INT)
BEGIN
	DECLARE v_moving_color ENUM('W','B');
	
	START TRANSACTION;
	
	SELECT piece_color INTO v_moving_color 
	FROM board WHERE point_id=p_from;
	
	UPDATE board 
	SET point_count = point_count-1
	WHERE point_id = p_from;
	
	UPDATE board
	SET piece_color = NULL
	WHERE point_id = p_from AND point_count = 0;
	
	UPDATE board
	SET point_count = point_count +1, 
	piece_color = v_moving_color 
	WHERE point_id = p_to;
	
	COMMIT;
END //
DELIMITER ;
	
	
	