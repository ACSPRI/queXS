-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 03, 2015 at 12:47 PM
-- Server version: 5.5.44
-- PHP Version: 5.3.10-1ubuntu3.19

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `quexs`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) NOT NULL,
  `contact_phone_id` bigint(20) NOT NULL,
  `call_attempt_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `require_operator_id` bigint(20) DEFAULT NULL,
  `respondent_id` bigint(20) NOT NULL,
  `completed_call_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `completed_call_id` (`completed_call_id`),
  KEY `call_attempt_id` (`call_attempt_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `availability`
--

CREATE TABLE `availability` (
  `availability_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `availability_group_id` bigint(20) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  `day_of_week` tinyint(1) NOT NULL,
  PRIMARY KEY (`availability_id`),
  KEY `availability_group_id` (`availability_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `availability`
--

INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(1, 1, '00:00:00', '11:59:59', 2);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(2, 1, '00:00:00', '11:59:59', 3);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(3, 1, '00:00:00', '11:59:59', 4);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(4, 1, '00:00:00', '11:59:59', 5);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(5, 1, '00:00:00', '11:59:59', 6);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(6, 2, '12:00:00', '17:59:59', 2);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(7, 2, '12:00:00', '17:59:59', 3);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(8, 2, '12:00:00', '17:59:59', 4);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(9, 2, '12:00:00', '17:59:59', 5);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(10, 2, '12:00:00', '17:59:59', 6);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(11, 3, '18:00:00', '23:59:59', 2);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(12, 3, '18:00:00', '23:59:59', 3);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(13, 3, '18:00:00', '23:59:59', 4);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(14, 3, '18:00:00', '23:59:59', 5);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(15, 3, '18:00:00', '23:59:59', 6);
INSERT INTO `availability` (`availability_id`, `availability_group_id`, `start`, `end`, `day_of_week`) VALUES(16, 4, '00:00:00', '23:59:59', 7);

-- --------------------------------------------------------

--
-- Table structure for table `availability_group`
--

CREATE TABLE `availability_group` (
  `availability_group_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`availability_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `availability_group`
--

INSERT INTO `availability_group` (`availability_group_id`, `description`) VALUES(1, 'Weekday mornings (Before 12pm)');
INSERT INTO `availability_group` (`availability_group_id`, `description`) VALUES(2, 'Weekday afternoons (After 12pm but before 6pm)');
INSERT INTO `availability_group` (`availability_group_id`, `description`) VALUES(3, 'Evenings (After 6pm)');
INSERT INTO `availability_group` (`availability_group_id`, `description`) VALUES(4, 'Saturdays');

-- --------------------------------------------------------

--
-- Table structure for table `call`
--

CREATE TABLE `call` (
  `call_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `operator_id` bigint(20) NOT NULL,
  `respondent_id` bigint(20) NOT NULL,
  `case_id` bigint(20) NOT NULL,
  `contact_phone_id` bigint(20) NOT NULL,
  `call_attempt_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  `outcome_id` int(11) NOT NULL DEFAULT '0',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 not called, 1 requesting call, 2 ringing, 3 answered, 4 requires coding, 5 done',
  PRIMARY KEY (`call_id`),
  KEY `operator_id` (`operator_id`),
  KEY `case_id` (`case_id`),
  KEY `call_attempt_id` (`call_attempt_id`),
  KEY `contact_phone_id` (`contact_phone_id`),
  KEY `start` (`start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `call_attempt`
--

CREATE TABLE `call_attempt` (
  `call_attempt_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `respondent_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  PRIMARY KEY (`call_attempt_id`),
  KEY `case_id` (`case_id`),
  KEY `end` (`end`),
  KEY `respondent_id` (`respondent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `call_note`
--

CREATE TABLE `call_note` (
  `call_note_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`call_note_id`),
  KEY `call_id` (`call_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `call_restrict`
--

CREATE TABLE `call_restrict` (
  `day_of_week` tinyint(1) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  KEY `day_of_week` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `call_restrict`
--

INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(1, '09:00:00', '17:00:00');
INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(2, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(3, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(4, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(5, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(6, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` (`day_of_week`, `start`, `end`) VALUES(7, '09:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `call_state`
--

CREATE TABLE `call_state` (
  `call_state_id` tinyint(1) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`call_state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `call_state`
--

INSERT INTO `call_state` (`call_state_id`, `description`) VALUES(0, 'Not called');
INSERT INTO `call_state` (`call_state_id`, `description`) VALUES(1, 'Requesting call');
INSERT INTO `call_state` (`call_state_id`, `description`) VALUES(2, 'Ringing');
INSERT INTO `call_state` (`call_state_id`, `description`) VALUES(3, 'Answered');
INSERT INTO `call_state` (`call_state_id`, `description`) VALUES(4, 'Requires coding');
INSERT INTO `call_state` (`call_state_id`, `description`) VALUES(5, 'Done');

-- --------------------------------------------------------

--
-- Table structure for table `case`
--

CREATE TABLE `case` (
  `case_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sample_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  `last_call_id` bigint(20) DEFAULT NULL,
  `current_operator_id` bigint(20) DEFAULT NULL,
  `current_call_id` bigint(20) DEFAULT NULL,
  `current_outcome_id` int(11) NOT NULL DEFAULT '1',
  `sortorder` int(11) DEFAULT NULL,
  `token` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`case_id`),
  UNIQUE KEY `onecasepersample` (`sample_id`,`questionnaire_id`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `current_operator_id` (`current_operator_id`),
  UNIQUE KEY `current_call_id` (`current_call_id`),
  KEY `sample_id` (`sample_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sortorder` (`sortorder`),
  KEY `last_call_id` (`last_call_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_availability`
--

CREATE TABLE `case_availability` (
  `case_id` bigint(20) NOT NULL,
  `availability_group_id` bigint(20) NOT NULL,
  PRIMARY KEY (`case_id`,`availability_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_note`
--

CREATE TABLE `case_note` (
  `case_note_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`case_note_id`),
  KEY `case_id` (`case_id`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_queue`
--

CREATE TABLE `case_queue` (
  `case_queue_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `sortorder` int(11) NOT NULL,
  PRIMARY KEY (`case_queue_id`),
  UNIQUE KEY `case_id` (`case_id`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `client_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `firstName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`client_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_questionnaire`
--

CREATE TABLE `client_questionnaire` (
  `client_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  PRIMARY KEY (`client_id`,`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_phone`
--

CREATE TABLE `contact_phone` (
  `contact_phone_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT '1',
  `phone` char(30) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_phone_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `day_of_week`
--

CREATE TABLE `day_of_week` (
  `day_of_week` tinyint(1) NOT NULL,
  PRIMARY KEY (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `day_of_week`
--

INSERT INTO `day_of_week` (`day_of_week`) VALUES(1);
INSERT INTO `day_of_week` (`day_of_week`) VALUES(2);
INSERT INTO `day_of_week` (`day_of_week`) VALUES(3);
INSERT INTO `day_of_week` (`day_of_week`) VALUES(4);
INSERT INTO `day_of_week` (`day_of_week`) VALUES(5);
INSERT INTO `day_of_week` (`day_of_week`) VALUES(6);
INSERT INTO `day_of_week` (`day_of_week`) VALUES(7);

-- --------------------------------------------------------

--
-- Table structure for table `extension`
--

CREATE TABLE `extension` (
  `extension_id` int(11) NOT NULL AUTO_INCREMENT,
  `extension` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `current_operator_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`extension_id`),
  UNIQUE KEY `extension` (`extension`),
  UNIQUE KEY `current_operator_id` (`current_operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_answers`
--

CREATE TABLE `lime_answers` (
  `qid` int(11) NOT NULL DEFAULT '0',
  `code` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `answer` text COLLATE utf8_unicode_ci NOT NULL,
  `assessment_value` int(11) NOT NULL DEFAULT '0',
  `sortorder` int(11) NOT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `scale_id` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`qid`,`code`,`language`,`scale_id`),
  KEY `answers_idx2` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_assessments`
--

CREATE TABLE `lime_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `scope` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gid` int(11) NOT NULL DEFAULT '0',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `minimum` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `maximum` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`,`language`),
  KEY `assessments_idx2` (`sid`),
  KEY `assessments_idx3` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_conditions`
--

CREATE TABLE `lime_conditions` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `qid` int(11) NOT NULL DEFAULT '0',
  `scenario` int(11) NOT NULL DEFAULT '1',
  `cqid` int(11) NOT NULL DEFAULT '0',
  `cfieldname` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `method` char(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`cid`),
  KEY `conditions_idx2` (`qid`),
  KEY `conditions_idx3` (`cqid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_defaultvalues`
--

CREATE TABLE `lime_defaultvalues` (
  `qid` int(11) NOT NULL DEFAULT '0',
  `specialtype` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `scale_id` int(11) NOT NULL DEFAULT '0',
  `sqid` int(11) NOT NULL DEFAULT '0',
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `defaultvalue` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`qid`,`scale_id`,`language`,`specialtype`,`sqid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_expression_errors`
--

CREATE TABLE `lime_expression_errors` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `errortime` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sid` int(11) DEFAULT NULL,
  `gid` int(11) DEFAULT NULL,
  `qid` int(11) DEFAULT NULL,
  `gseq` int(11) DEFAULT NULL,
  `qseq` int(11) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `eqn` text COLLATE utf8_unicode_ci,
  `prettyprint` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_failed_login_attempts`
--

CREATE TABLE `lime_failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(37) COLLATE utf8_unicode_ci NOT NULL,
  `last_attempt` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `number_attempts` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_groups`
--

CREATE TABLE `lime_groups` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `group_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `group_order` int(11) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8_unicode_ci,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `randomization_group` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `grelevance` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`gid`,`language`),
  KEY `groups_idx2` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_labels`
--

CREATE TABLE `lime_labels` (
  `lid` int(11) NOT NULL DEFAULT '0',
  `code` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` text COLLATE utf8_unicode_ci,
  `sortorder` int(11) NOT NULL,
  `assessment_value` int(11) NOT NULL DEFAULT '0',
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  PRIMARY KEY (`lid`,`sortorder`,`language`),
  KEY `ixcode` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_labelsets`
--

CREATE TABLE `lime_labelsets` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `label_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `languages` varchar(200) COLLATE utf8_unicode_ci DEFAULT 'en',
  PRIMARY KEY (`lid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_participants`
--

CREATE TABLE `lime_participants` (
  `participant_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `firstname` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `blacklisted` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `owner_uid` int(20) NOT NULL,
  PRIMARY KEY (`participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_participant_attribute`
--

CREATE TABLE `lime_participant_attribute` (
  `participant_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`participant_id`,`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_participant_attribute_names`
--

CREATE TABLE `lime_participant_attribute_names` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_type` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `visible` char(5) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`attribute_id`,`attribute_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_participant_attribute_names_lang`
--

CREATE TABLE `lime_participant_attribute_names_lang` (
  `attribute_id` int(11) NOT NULL,
  `attribute_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `lang` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`attribute_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_participant_attribute_values`
--

CREATE TABLE `lime_participant_attribute_values` (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `value` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_participant_shares`
--

CREATE TABLE `lime_participant_shares` (
  `participant_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `share_uid` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `can_edit` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`participant_id`,`share_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_questions`
--

CREATE TABLE `lime_questions` (
  `qid` int(11) NOT NULL AUTO_INCREMENT,
  `parent_qid` int(11) NOT NULL DEFAULT '0',
  `sid` int(11) NOT NULL DEFAULT '0',
  `gid` int(11) NOT NULL DEFAULT '0',
  `type` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'T',
  `title` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `question` text COLLATE utf8_unicode_ci NOT NULL,
  `preg` text COLLATE utf8_unicode_ci,
  `help` text COLLATE utf8_unicode_ci,
  `other` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `mandatory` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `question_order` int(11) NOT NULL,
  `language` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `scale_id` tinyint(4) NOT NULL DEFAULT '0',
  `same_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Saves if user set to use the same default value across languages in default options dialog',
  `relevance` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`qid`,`language`),
  KEY `questions_idx2` (`sid`),
  KEY `questions_idx3` (`gid`),
  KEY `questions_idx4` (`type`),
  KEY `parent_qid_idx` (`parent_qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_question_attributes`
--

CREATE TABLE `lime_question_attributes` (
  `qaid` int(11) NOT NULL AUTO_INCREMENT,
  `qid` int(11) NOT NULL DEFAULT '0',
  `attribute` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `language` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`qaid`),
  KEY `question_attributes_idx2` (`qid`),
  KEY `question_attributes_idx3` (`attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_quota`
--

CREATE TABLE `lime_quota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `qlimit` int(8) DEFAULT NULL,
  `action` int(2) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  `autoload_url` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `quota_idx2` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_quota_languagesettings`
--

CREATE TABLE `lime_quota_languagesettings` (
  `quotals_id` int(11) NOT NULL AUTO_INCREMENT,
  `quotals_quota_id` int(11) NOT NULL DEFAULT '0',
  `quotals_language` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `quotals_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quotals_message` text COLLATE utf8_unicode_ci NOT NULL,
  `quotals_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quotals_urldescrip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`quotals_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_quota_members`
--

CREATE TABLE `lime_quota_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `qid` int(11) DEFAULT NULL,
  `quota_id` int(11) DEFAULT NULL,
  `code` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid` (`sid`,`qid`,`quota_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_saved_control`
--

CREATE TABLE `lime_saved_control` (
  `scid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL DEFAULT '0',
  `srid` int(11) NOT NULL DEFAULT '0',
  `identifier` bigint(20) NOT NULL,
  `access_code` text COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip` text COLLATE utf8_unicode_ci NOT NULL,
  `saved_thisstep` text COLLATE utf8_unicode_ci NOT NULL,
  `status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `saved_date` datetime NOT NULL,
  `refurl` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`scid`),
  KEY `saved_control_idx2` (`sid`),
  KEY `identifier` (`identifier`),
  KEY `srid` (`srid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_sessions`
--

CREATE TABLE `lime_sessions` (
  `sesskey` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expiry` datetime NOT NULL,
  `expireref` varchar(250) COLLATE utf8_unicode_ci DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `sessdata` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`sesskey`),
  KEY `sess2_expiry` (`expiry`),
  KEY `sess2_expireref` (`expireref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_settings_global`
--

CREATE TABLE `lime_settings_global` (
  `stg_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `stg_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`stg_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lime_settings_global`
--

INSERT INTO `lime_settings_global` (`stg_name`, `stg_value`) VALUES('DBVersion', '155.6');
INSERT INTO `lime_settings_global` (`stg_name`, `stg_value`) VALUES('SessionName', 'ls28629164789259281352');

-- --------------------------------------------------------

--
-- Table structure for table `lime_surveys`
--

CREATE TABLE `lime_surveys` (
  `sid` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `admin` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `expires` datetime DEFAULT NULL,
  `startdate` datetime DEFAULT NULL,
  `adminemail` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `anonymized` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N',
  `faxto` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `format` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `savetimings` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `template` varchar(100) COLLATE utf8_unicode_ci DEFAULT 'default',
  `language` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `additional_languages` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `datestamp` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `usecookie` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `allowregister` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `allowsave` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `autonumber_start` bigint(11) DEFAULT '0',
  `autoredirect` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `allowprev` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `printanswers` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `ipaddr` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `refurl` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `datecreated` date DEFAULT NULL,
  `publicstatistics` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `publicgraphs` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `listpublic` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `htmlemail` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `tokenanswerspersistence` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `assessments` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `usecaptcha` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `usetokens` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `bounce_email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attributedescriptions` text COLLATE utf8_unicode_ci,
  `emailresponseto` text COLLATE utf8_unicode_ci,
  `emailnotificationto` text COLLATE utf8_unicode_ci,
  `tokenlength` tinyint(2) DEFAULT '15',
  `showxquestions` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `showgroupinfo` char(1) COLLATE utf8_unicode_ci DEFAULT 'B',
  `shownoanswer` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `showqnumcode` char(1) COLLATE utf8_unicode_ci DEFAULT 'X',
  `bouncetime` bigint(20) DEFAULT NULL,
  `bounceprocessing` varchar(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `bounceaccounttype` varchar(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bounceaccounthost` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bounceaccountpass` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bounceaccountencryption` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bounceaccountuser` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `showwelcome` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `showprogress` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y',
  `allowjumps` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `navigationdelay` tinyint(2) DEFAULT '0',
  `nokeyboard` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `alloweditaftercompletion` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `googleanalyticsstyle` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `googleanalyticsapikey` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_surveys_languagesettings`
--

CREATE TABLE `lime_surveys_languagesettings` (
  `surveyls_survey_id` int(11) NOT NULL DEFAULT '0',
  `surveyls_language` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `surveyls_title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `surveyls_description` text COLLATE utf8_unicode_ci,
  `surveyls_welcometext` text COLLATE utf8_unicode_ci,
  `surveyls_endtext` text COLLATE utf8_unicode_ci,
  `surveyls_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_urldescription` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_invite_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_invite` text COLLATE utf8_unicode_ci,
  `surveyls_email_remind_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_remind` text COLLATE utf8_unicode_ci,
  `surveyls_email_register_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_register` text COLLATE utf8_unicode_ci,
  `surveyls_email_confirm_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surveyls_email_confirm` text COLLATE utf8_unicode_ci,
  `surveyls_dateformat` int(10) unsigned NOT NULL DEFAULT '1',
  `email_admin_notification_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_admin_notification` text COLLATE utf8_unicode_ci,
  `email_admin_responses_subj` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_admin_responses` text COLLATE utf8_unicode_ci,
  `surveyls_numberformat` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`surveyls_survey_id`,`surveyls_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_survey_links`
--

CREATE TABLE `lime_survey_links` (
  `participant_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `token_id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`participant_id`,`token_id`,`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_survey_permissions`
--

CREATE TABLE `lime_survey_permissions` (
  `sid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `permission` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `create_p` tinyint(1) NOT NULL DEFAULT '0',
  `read_p` tinyint(1) NOT NULL DEFAULT '0',
  `update_p` tinyint(1) NOT NULL DEFAULT '0',
  `delete_p` tinyint(1) NOT NULL DEFAULT '0',
  `import_p` tinyint(1) NOT NULL DEFAULT '0',
  `export_p` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`,`uid`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_templates`
--

CREATE TABLE `lime_templates` (
  `folder` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `creator` int(11) NOT NULL,
  PRIMARY KEY (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_templates_rights`
--

CREATE TABLE `lime_templates_rights` (
  `uid` int(11) NOT NULL,
  `folder` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `use` int(1) NOT NULL,
  PRIMARY KEY (`uid`,`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_users`
--

CREATE TABLE `lime_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `users_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` blob NOT NULL,
  `full_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(11) NOT NULL,
  `lang` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(320) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_survey` tinyint(1) NOT NULL DEFAULT '0',
  `create_user` tinyint(1) NOT NULL DEFAULT '0',
  `participant_panel` tinyint(1) NOT NULL DEFAULT '0',
  `delete_user` tinyint(1) NOT NULL DEFAULT '0',
  `superadmin` tinyint(1) NOT NULL DEFAULT '0',
  `configurator` tinyint(1) NOT NULL DEFAULT '0',
  `manage_template` tinyint(1) NOT NULL DEFAULT '0',
  `manage_label` tinyint(1) NOT NULL DEFAULT '0',
  `htmleditormode` varchar(7) COLLATE utf8_unicode_ci DEFAULT 'default',
  `templateeditormode` varchar(7) COLLATE utf8_unicode_ci DEFAULT 'default',
  `questionselectormode` varchar(7) COLLATE utf8_unicode_ci DEFAULT 'default',
  `one_time_pw` blob,
  `dateformat` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `users_name` (`users_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `lime_users`
--

INSERT INTO `lime_users` (`uid`, `users_name`, `password`, `full_name`, `parent_id`, `lang`, `email`, `create_survey`, `create_user`, `participant_panel`, `delete_user`, `superadmin`, `configurator`, `manage_template`, `manage_label`, `htmleditormode`, `templateeditormode`, `questionselectormode`, `one_time_pw`, `dateformat`) VALUES(1, 'admin', 0x35653838343839386461323830343731353164306535366638646336323932373733363033643064366161626264643632613131656637323164313534326438, 'Your Name', 0, 'auto', 'your-email@example.net', 1, 1, 0, 1, 1, 1, 1, 1, 'default', 'default', 'default', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `lime_user_groups`
--

CREATE TABLE `lime_user_groups` (
  `ugid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` int(11) NOT NULL,
  PRIMARY KEY (`ugid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lime_user_in_groups`
--

CREATE TABLE `lime_user_in_groups` (
  `ugid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`ugid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `operator`
--

CREATE TABLE `operator` (
  `operator_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `firstName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `voip` tinyint(1) NOT NULL DEFAULT '1',
  `next_case_id` bigint(20) DEFAULT NULL,
  `chat_enable` tinyint(1) DEFAULT '0',
  `chat_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `chat_password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`operator_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------
INSERT INTO `operator` (`operator_id`, `username`, `firstName`, `lastName`, `Time_zone_name`, `enabled`, `voip`, `next_case_id`, `chat_enable`, `chat_user`, `chat_password`) VALUES
(1, 'admin', 'CATI', 'Admin', 'Australia/Victoria', 1, 0, NULL, 0, '', '');


--
-- Table structure for table `operator_questionnaire`
--

CREATE TABLE `operator_questionnaire` (
  `operator_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  PRIMARY KEY (`operator_id`,`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `operator_skill`
--

CREATE TABLE `operator_skill` (
  `operator_id` bigint(20) NOT NULL,
  `outcome_type_id` int(11) NOT NULL,
  PRIMARY KEY (`operator_id`,`outcome_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

INSERT INTO `operator_skill` (`operator_id`, `outcome_type_id`) VALUES
(1, 1),
(1, 5);

--
-- Table structure for table `outcome`
--

CREATE TABLE `outcome` (
  `outcome_id` int(11) NOT NULL AUTO_INCREMENT,
  `aapor_id` char(6) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `default_delay_minutes` bigint(20) NOT NULL,
  `outcome_type_id` int(11) NOT NULL DEFAULT '1',
  `tryanother` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether to try the next number on the list',
  `contacted` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether a person was contacted',
  `tryagain` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether to try this number ever again',
  `eligible` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'If the respondent is eligible to participate',
  `require_note` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether to require a note to be entered',
  `calc` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `default` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Used as default for questionnaire outcomes',
  `permanent` TINYINT(1) UNSIGNED NOT NULL COMMENT 'Permanent outcome, used for all questionnaires, not possible to de-select',
  PRIMARY KEY (`outcome_id`),
  KEY `calc` (`calc`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=100;

--
-- Dumping data for table `outcome`
--

INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (1,'3.11','Not attempted or worked',0,1,1,0,1,0,0,'UH',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (2,'3.13','No answer',180,1,1,0,1,1,0,'UH',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (3,'3.16','Technical phone problems',180,1,1,0,1,0,0,'UH',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (4,'2.34','Other, Referred to Supervisor (Eligible)',0,2,0,1,1,1,1,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (5,'3.91','Other, Referred to Supervisor (Unknown eligibility)',0,2,0,0,1,0,1,'UO',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (6,'2.111a','Soft Refusal, Other',10080,3,0,1,1,1,1,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (7,'2.111b','Hard Refusal, Other',10080,3,0,1,1,1,1,'R',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (8,'2.112a','Soft Refusal, Respondent',10080,3,0,1,1,1,1,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (9,'2.112b','Hard Refusal, Respondent',10080,3,0,1,1,1,1,'R',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (10,'1.1','Complete',0,4,0,1,1,1,0,'I',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (11,'2.112','Known respondent refusal',0,4,0,1,1,1,0,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (12,'2.111','Household-level refusal',0,4,0,1,1,1,0,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (13,'2.112c','Broken appointment (Implicit refusal)',10080,3,1,0,1,1,0,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (14,'4.32','Disconnected number',0,4,1,0,0,0,0,'',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (15,'4.20','Fax/data line',0,4,1,1,0,0,0,'',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (16,'4.51','Business, government office, other organization',0,4,1,1,0,0,0,'',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (17,'4.70','No eligible respondent',0,4,1,1,0,0,0,'',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (18,'2.35a','Accidental hang up or temporary phone problem',0,1,1,1,1,1,0,'O',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (19,'2.12a','Definite Appointment - Respondent',0,5,0,1,1,1,0,'R',1,1);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (20,'2.12b','Definite Appointment - Other',0,5,0,1,1,1,0,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (21,'2.13a','Unspecified Appointment - Respondent',0,5,0,1,1,1,0,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (22,'2.13b','Unspecified Appointment - Other',0,5,0,1,1,1,0,'R',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (23,'2.221','Household answering machine - Message left',180,1,1,1,1,1,0,'NC',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (24,'2.222','Household answering machine - No message left',180,1,1,1,1,1,0,'NC',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (25,'2.31','Respondent Dead',0,4,0,1,0,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (26,'2.32','Physically or mentally unable/incompetent',0,4,0,1,0,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (27,'2.331','Household level language problem',0,4,1,1,0,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (28,'2.332','Respondent language problem',0,4,0,1,0,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (29,'3.14','Answering machine - Not a household',0,4,1,1,0,0,0,'UH',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (30,'4.10','Out of sample',0,4,0,1,0,0,0,'',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (31,'2.20','Non contact',180,1,1,1,1,1,0,'NC',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (32,'4.80','Quota filled',0,4,0,1,0,0,0,'',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (33,'2.36','Miscellaneous - Unavailable for a week',10080,1,0,1,1,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (40,'1.1','Self completed online',0,4,0,1,1,1,0,'I',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (41,'2.36','Self completion email invitation sent',10080,1,0,1,1,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (42,'3.90','Max call attempts reached (Unknown eligibility)',0,1,0,1,1,0,0,'UH',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (43,'3.90','Max calls reached (Unknown eligibility)',0,1,0,1,1,0,0,'UH',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (44,'2.30','Max call attempts reached (Eligible)',0,1,0,1,1,1,0,'O',1,0);
INSERT INTO `outcome` (`outcome_id`, `aapor_id`, `description`, `default_delay_minutes`, `outcome_type_id`, `tryanother`, `contacted`, `tryagain`, `eligible`, `require_note`, `calc`, `default`, `permanent`) VALUES (45,'2.30','Max calls reached (Eligible)',0,1,0,1,1,1,0,'O',1,0);

-- Auto increment start from 100 for manual entries

ALTER TABLE `outcome` AUTO_INCREMENT = 100;

-- --------------------------------------------------------

--
-- Table structure for table `outcome_type`
--

CREATE TABLE `outcome_type` (
  `outcome_type_id` int(11) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`outcome_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `outcome_type`
--

INSERT INTO `outcome_type` (`outcome_type_id`, `description`) VALUES(1, 'Temporary Outcomes (normal cases)');
INSERT INTO `outcome_type` (`outcome_type_id`, `description`) VALUES(2, 'Supervisor Outcomes (referred to supervisor)');
INSERT INTO `outcome_type` (`outcome_type_id`, `description`) VALUES(3, 'Refusal Outcomes (respondent refused)');
INSERT INTO `outcome_type` (`outcome_type_id`, `description`) VALUES(4, 'Final Outcomes (completed, final refusal, etc)');
INSERT INTO `outcome_type` (`outcome_type_id`, `description`) VALUES(5, 'Appointments');

-- --------------------------------------------------------

--
-- Table structure for table `process`
--

CREATE TABLE `process` (
  `process_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '1',
  `start` datetime NOT NULL,
  `stop` datetime DEFAULT NULL,
  `kill` tinyint(1) NOT NULL DEFAULT '0',
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `process_log`
--

CREATE TABLE `process_log` (
  `process_log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `process_id` bigint(20) NOT NULL,
  `datetime` datetime NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`process_log_id`),
  KEY `process_id` (`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qsqr_question`
--

CREATE TABLE `qsqr_question` (
  `qsqr_question_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionnaire_sample_quota_row_id` bigint(20) NOT NULL,
  `lime_sgqa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `comparison` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`qsqr_question_id`),
  KEY `questionnaire_sample_quota_row_id` (`questionnaire_sample_quota_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qsqr_sample`
--

CREATE TABLE `qsqr_sample` (
  `qsqr_sample_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionnaire_sample_quota_row_id` bigint(20) NOT NULL,
  `exclude_var_id` bigint(20) NOT NULL,
  `exclude_var` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `exclude_val` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `comparison` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`qsqr_sample_id`),
  KEY `questionnaire_sample_quota_row_id` (`questionnaire_sample_quota_row_id`),
  KEY `exclude_var` (`exclude_var`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire`
--

CREATE TABLE `questionnaire` (
  `questionnaire_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lime_sid` int(11) NOT NULL,
  `restrict_appointments_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `restrict_work_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `testing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether this questionnaire is just for testing',
  `respondent_selection` tinyint(1) NOT NULL DEFAULT '1',
  `rs_intro` text COLLATE utf8_unicode_ci NOT NULL,
  `rs_project_intro` text COLLATE utf8_unicode_ci NOT NULL,
  `rs_project_end` text COLLATE utf8_unicode_ci NOT NULL,
  `rs_callback` text COLLATE utf8_unicode_ci NOT NULL,
  `rs_answeringmachine` text COLLATE utf8_unicode_ci NOT NULL,
  `lime_rs_sid` int(11) DEFAULT NULL,
  `info` text COLLATE utf8_unicode_ci,
  `self_complete` tinyint(1) NOT NULL DEFAULT '0',
  `referral` tinyint(1) NOT NULL DEFAULT '0',
  `lime_mode` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Limesurvey mode for respondent self completion',
  `lime_template` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Limesurvey template for respondent self completion',
  `lime_endurl` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Forwarding end URL for respondent self completion',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `outcomes` varchar(256) COLLATE utf8_unicode_ci NULL DEFAULT '1,2,3,7,9,10,14,17,18,19' COMMENT 'Comma-separated string of outcomes defined for the questionnaire',
  PRIMARY KEY (`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_availability`
--

CREATE TABLE `questionnaire_availability` (
  `questionnaire_id` bigint(20) NOT NULL,
  `availability_group_id` bigint(20) NOT NULL,
  PRIMARY KEY (`questionnaire_id`,`availability_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_prefill`
--

CREATE TABLE `questionnaire_prefill` (
  `questionnaire_prefill_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionnaire_id` bigint(20) NOT NULL,
  `lime_sgqa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`questionnaire_prefill_id`),
  KEY `questionnaire_id` (`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample`
--

CREATE TABLE `questionnaire_sample` (
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `call_max` int(11) NOT NULL DEFAULT '0',
  `call_attempt_max` int(11) NOT NULL DEFAULT '0',
  `random_select` tinyint(1) NOT NULL DEFAULT '0',
  `answering_machine_messages` int(11) NOT NULL DEFAULT '1',
  `allow_new` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`questionnaire_id`,`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_exclude_priority`
--

CREATE TABLE `questionnaire_sample_exclude_priority` (
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_id` bigint(20) NOT NULL,
  `exclude` tinyint(1) NOT NULL DEFAULT '0',
  `priority` tinyint(3) NOT NULL DEFAULT '50',
  `sortorder` int(11) DEFAULT NULL,
  PRIMARY KEY (`questionnaire_id`,`sample_id`),
  KEY `exclude` (`exclude`),
  KEY `priority` (`priority`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sortorder` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_quota`
--

CREATE TABLE `questionnaire_sample_quota` (
  `questionnaire_sample_quota_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `lime_sgqa` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `comparison` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'LIKE',
  `completions` int(11) NOT NULL,
  `quota_reached` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`questionnaire_sample_quota_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_quota_row`
--

CREATE TABLE `questionnaire_sample_quota_row` (
  `questionnaire_sample_quota_row_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `completions` int(11) NOT NULL,
  `quota_reached` tinyint(1) NOT NULL DEFAULT '0',
  `current_completions` int(11) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `priority` tinyint(3) NOT NULL DEFAULT '50' COMMENT 'Priority from 0 - 100',
  `autoprioritise` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Should this row have it''s priority automatically adjusted to 100 - (completions %)',
  PRIMARY KEY (`questionnaire_sample_quota_row_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sample_import_id` (`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_quota_row_exclude`
--

CREATE TABLE `questionnaire_sample_quota_row_exclude` (
  `questionnaire_sample_quota_row_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_id` bigint(20) NOT NULL,
  PRIMARY KEY (`questionnaire_sample_quota_row_id`,`questionnaire_id`,`sample_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sample_id` (`sample_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_timeslot`
--

CREATE TABLE `questionnaire_sample_timeslot` (
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `availability_group_id` bigint(20) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`questionnaire_id`,`availability_group_id`,`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_timeslot`
--

CREATE TABLE `questionnaire_timeslot` (
  `questionnaire_id` bigint(20) NOT NULL,
  `availability_group_id` bigint(20) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`questionnaire_id`,`availability_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `respondent`
--

CREATE TABLE `respondent` (
  `respondent_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) NOT NULL,
  `firstName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`respondent_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `respondent_not_available`
--

CREATE TABLE `respondent_not_available` (
  `respondent_not_available_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `respondent_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  PRIMARY KEY (`respondent_not_available_id`),
  KEY `respondent_id` (`respondent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample`
--

CREATE TABLE `sample` (
  `sample_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `import_id` bigint(20) NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `phone` char(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`sample_id`),
  KEY `import_id` (`import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_import`
--

CREATE TABLE `sample_import` (
  `sample_import_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `call_restrict` tinyint(1) NOT NULL DEFAULT '1',
  `refusal_conversion` tinyint(1) NOT NULL DEFAULT '1',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_import_var_restrict`
--

CREATE TABLE `sample_import_var_restrict` (
  `sample_import_id` bigint(20) NOT NULL,
  `var_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `var` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` smallint(10) unsigned NOT NULL,
  `restrict` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`var_id`),
  KEY (`var`),
  KEY (`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_postcode_timezone`
--

CREATE TABLE `sample_postcode_timezone` (
  `val` int(4) NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`val`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_prefix_timezone`
--

CREATE TABLE `sample_prefix_timezone` (
  `val` char(10) COLLATE utf8_unicode_ci NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`val`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_state_timezone`
--

CREATE TABLE `sample_state_timezone` (
  `val` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`val`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_var`
--

CREATE TABLE `sample_var` (
  `sample_id` bigint(20) NOT NULL,
  `var_id` bigint(20) unsigned NOT NULL,
  `val` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`sample_id`,`var_id`),
  KEY `sample_id` (`sample_id`),
  KEY `var_id` (`var_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_var_type`
--

CREATE TABLE `sample_var_type` (
  `type` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `table` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `sample_var_type`
--

INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(1, 'String', '');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(2, 'Phone number', 'sample_prefix_timezone');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(3, 'Primary phone number', 'sample_prefix_timezone');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(4, 'State', 'sample_state_timezone');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(5, 'Postcode', 'sample_postcode_timezone');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(6, 'Respondent first name', '');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(7, 'Respondent last name', '');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(8, 'Email address', '');
INSERT INTO `sample_var_type` (`type`, `description`, `table`) VALUES(9, 'Token', '');

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `field` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `field` (`field`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Serialised table of settings';

--
-- Dumping data for table `setting`
--

INSERT INTO `setting` (`setting_id`, `field`, `value`) VALUES(1, 'DEFAULT_TIME_ZONE', 's:18:"Australia/Victoria";');
INSERT INTO `setting` (`setting_id`, `field`, `value`) VALUES(2, 'systemsort', 'b:0;');

-- --------------------------------------------------------

--
-- Table structure for table `shift`
--

CREATE TABLE `shift` (
  `shift_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `questionnaire_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  PRIMARY KEY (`shift_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `start` (`start`),
  KEY `end` (`end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_report`
--

CREATE TABLE `shift_report` (
  `shift_report_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `report` text COLLATE utf8_unicode_ci NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`shift_report_id`),
  KEY `shift_id` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_template`
--

CREATE TABLE `shift_template` (
  `day_of_week` tinyint(1) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  KEY `day_of_week` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `shift_template`
--

INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(2, '17:00:00', '20:30:00');
INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(3, '17:00:00', '20:30:00');
INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(4, '17:00:00', '20:30:00');
INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(5, '17:00:00', '20:30:00');
INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(6, '17:00:00', '20:30:00');
INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(7, '09:00:00', '13:00:00');
INSERT INTO `shift_template` (`day_of_week`, `start`, `end`) VALUES(7, '13:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `timezone_template`
--

CREATE TABLE `timezone_template` (
  `Time_zone_name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`Time_zone_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
