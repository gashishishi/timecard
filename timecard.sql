-- Adminer 4.8.1 MySQL 8.0.31 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `timecard_rests`;
CREATE TABLE `timecard_rests` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  `timecard_id` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timecard_id` (`timecard_id`),
  CONSTRAINT `timecard_rests_ibfk_2` FOREIGN KEY (`timecard_id`) REFERENCES `timecards` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `timecards`;
CREATE TABLE `timecards` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `day` datetime NOT NULL COMMENT '勤務日',
  `start` datetime NOT NULL COMMENT '出勤時間',
  `end` datetime DEFAULT NULL COMMENT '退勤時間',
  `work_time` datetime DEFAULT NULL COMMENT '総労働時間',
  `actual_work_time` datetime DEFAULT NULL COMMENT '実働時間',
  `total_rest_time` datetime DEFAULT NULL COMMENT '総休憩時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 2023-03-24 06:32:36
