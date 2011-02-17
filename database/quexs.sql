-- phpMyAdmin SQL Dump
-- version 2.11.2.2
-- http://www.phpmyadmin.net
--
-- Host: databasedev.dcarf
-- Generation Time: Jul 24, 2008 at 11:07 AM
-- Server version: 5.0.32
-- PHP Version: 5.2.0-8+etch11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `quexs`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` bigint(20) NOT NULL auto_increment,
  `case_id` bigint(20) NOT NULL,
  `contact_phone_id` bigint(20) NOT NULL,
  `call_attempt_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `require_operator_id` bigint(20) default NULL,
  `respondent_id` bigint(20) NOT NULL,
  `completed_call_id` bigint(20) default NULL,
  PRIMARY KEY  (`appointment_id`),
  KEY `completed_call_id` (`completed_call_id`),
  KEY `call_attempt_id` (`call_attempt_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `appointment`
--


-- --------------------------------------------------------

--
-- Table structure for table `call`
--

CREATE TABLE `call` (
  `call_id` bigint(20) NOT NULL auto_increment,
  `operator_id` bigint(20) NOT NULL,
  `respondent_id` bigint(20) NOT NULL,
  `case_id` bigint(20) NOT NULL,
  `contact_phone_id` bigint(20) NOT NULL,
  `call_attempt_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime default NULL,
  `outcome_id` int(11) NOT NULL default '0',
  `state` tinyint(1) NOT NULL default '0' COMMENT '0 not called, 1 requesting call, 2 ringing, 3 answered, 4 requires coding, 5 done',
  PRIMARY KEY  (`call_id`),
  KEY `operator_id` (`operator_id`),
  KEY `case_id` (`case_id`),
  KEY `call_attempt_id` (`call_attempt_id`),
  KEY `contact_phone_id` (`contact_phone_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `call`
--


-- --------------------------------------------------------

--
-- Table structure for table `call_attempt`
--

CREATE TABLE `call_attempt` (
  `call_attempt_id` bigint(20) NOT NULL auto_increment,
  `case_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `respondent_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime default NULL,
  PRIMARY KEY  (`call_attempt_id`),
  KEY `case_id` (`case_id`),
  KEY `end` (`end`),
  KEY `respondent_id` (`respondent_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `call_attempt`
--


-- --------------------------------------------------------

--
-- Table structure for table `call_note`
--

CREATE TABLE `call_note` (
  `call_note_id` bigint(20) NOT NULL auto_increment,
  `call_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `note` text NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`call_note_id`),
  KEY `call_id` (`call_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `call_note`
--


-- --------------------------------------------------------

--
-- Table structure for table `call_restrict`
--

CREATE TABLE `call_restrict` (
  `day_of_week` tinyint(1) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  KEY `day_of_week` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `call_restrict`
--

INSERT INTO `call_restrict` VALUES(1, '09:00:00', '17:00:00');
INSERT INTO `call_restrict` VALUES(2, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` VALUES(3, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` VALUES(4, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` VALUES(5, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` VALUES(6, '09:00:00', '20:30:00');
INSERT INTO `call_restrict` VALUES(7, '09:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `call_state`
--

CREATE TABLE `call_state` (
  `call_state_id` tinyint(1) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`call_state_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `call_state`
--

INSERT INTO `call_state` VALUES(0, 'Not called');
INSERT INTO `call_state` VALUES(1, 'Requesting call');
INSERT INTO `call_state` VALUES(2, 'Ringing');
INSERT INTO `call_state` VALUES(3, 'Answered');
INSERT INTO `call_state` VALUES(4, 'Requires coding');
INSERT INTO `call_state` VALUES(5, 'Done');

-- --------------------------------------------------------

--
-- Table structure for table `case`
--

CREATE TABLE `case` (
  `case_id` bigint(20) NOT NULL auto_increment,
  `sample_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  `last_call_id` bigint(20) default NULL,
  `current_operator_id` bigint(20) default NULL,
  `current_call_id` bigint(20) default NULL,
  `current_outcome_id` int(11) NOT NULL default '1',
  `sortorder` int(11) default NULL,
  PRIMARY KEY  (`case_id`),
  UNIQUE KEY `onecasepersample` (`sample_id`,`questionnaire_id`),
  UNIQUE KEY `current_operator_id` (`current_operator_id`),
  UNIQUE KEY `current_call_id` (`current_call_id`),
  KEY `sample_id` (`sample_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sortorder` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


--
-- Dumping data for table `case`
--


-- --------------------------------------------------------

--
-- Table structure for table `case_note`
--

CREATE TABLE `case_note` (
  `case_note_id` bigint(20) NOT NULL auto_increment,
  `case_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `note` text NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`case_note_id`),
  KEY `case_id` (`case_id`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `case_note`
--


-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `client_id` bigint(20) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `Time_zone_name` char(64) NOT NULL,
  PRIMARY KEY  (`client_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `client`
--

-- --------------------------------------------------------

--
-- Table structure for table `client_questionnaire`
--

CREATE TABLE `client_questionnaire` (
  `client_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`client_id`,`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `client_questionnaire`
--


-- --------------------------------------------------------

--
-- Table structure for table `contact_phone`
--

CREATE TABLE `contact_phone` (
  `contact_phone_id` bigint(20) NOT NULL auto_increment,
  `case_id` bigint(20) NOT NULL,
  `priority` tinyint(1) NOT NULL default '1',
  `phone` bigint(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`contact_phone_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `contact_phone`
--

--
-- Table structure for table `day_of_week`
--

CREATE TABLE `day_of_week` (
  `day_of_week` tinyint(1) NOT NULL,
  PRIMARY KEY  (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `day_of_week`
--

INSERT INTO `day_of_week` VALUES(1);
INSERT INTO `day_of_week` VALUES(2);
INSERT INTO `day_of_week` VALUES(3);
INSERT INTO `day_of_week` VALUES(4);
INSERT INTO `day_of_week` VALUES(5);
INSERT INTO `day_of_week` VALUES(6);
INSERT INTO `day_of_week` VALUES(7);


-- --------------------------------------------------------

--
-- Table structure for table `operator`
--

CREATE TABLE `operator` (
  `operator_id` bigint(20) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `extension_password` varchar(255),
  `Time_zone_name` char(64) NOT NULL,
  `enabled` tinyint(1) NOT NULL default '1',
  `voip` tinyint(1) NOT NULL default '1',
  `voip_status` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`operator_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `extension` (`extension`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `operator`
--

-- --------------------------------------------------------

--
-- Table structure for table `operator_questionnaire`
--

CREATE TABLE `operator_questionnaire` (
  `operator_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`operator_id`,`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `operator_questionnaire`
--


-- --------------------------------------------------------

--
-- Table structure for table `operator_skill`
--

CREATE TABLE `operator_skill` (
  `operator_id` bigint(20) NOT NULL,
  `outcome_type_id` int(11) NOT NULL,
  PRIMARY KEY  (`operator_id`,`outcome_type_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `operator_skill`
--


-- --------------------------------------------------------

--
-- Table structure for table `outcome`
--

CREATE TABLE `outcome` (
  `outcome_id` int(11) NOT NULL auto_increment,
  `aapor_id` char(6) NOT NULL,
  `description` varchar(255) NOT NULL,
  `default_delay_minutes` bigint(20) NOT NULL,
  `outcome_type_id` int(11) NOT NULL default '1',
  `tryanother` tinyint(1) NOT NULL default '1' COMMENT 'Whether to try the next number on the list',
  `contacted` tinyint(1) NOT NULL default '1' COMMENT 'Whether a person was contacted',
  `tryagain` tinyint(1) NOT NULL default '1' COMMENT 'Whether to try this number ever again',
  `eligible` tinyint(1) NOT NULL default '1' COMMENT 'If the respondent is eligible to participate',
  `require_note` tinyint(1) NOT NULL default '0' COMMENT 'Whether to require a note to be entered',
  `calc` char(2) NOT NULL,
  PRIMARY KEY  (`outcome_id`),
  KEY `calc` (`calc`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `outcome`
--

INSERT INTO `outcome` VALUES(1, '3.11', 'Not attempted or worked', 0, 1, 1, 0, 1, 0, 0, 'UH');
INSERT INTO `outcome` VALUES(2, '3.13', 'No answer', 180, 1, 1, 0, 1, 1, 0, 'UH');
INSERT INTO `outcome` VALUES(3, '3.16', 'Technical phone problems', 180, 1, 1, 0, 1, 0, 0, 'UH');
INSERT INTO `outcome` VALUES(4, '2.34', 'Other, Referred to Supervisor (Eligible)', 0, 2, 0, 1, 1, 1, 1, 'O');
INSERT INTO `outcome` VALUES(5, '3.91', 'Other, Referred to Supervisor (Unknown eligibility)', 0, 2, 0, 0, 1, 0, 1, 'UO');
INSERT INTO `outcome` VALUES(6, '2.111a', 'Soft Refusal, Other', 10080, 3, 0, 1, 1, 1, 1, 'R');
INSERT INTO `outcome` VALUES(7, '2.111b', 'Hard Refusal, Other', 10080, 3, 0, 1, 1, 1, 1, 'R');
INSERT INTO `outcome` VALUES(8, '2.112a', 'Soft Refusal, Respondent', 10080, 3, 0, 1, 1, 1, 1, 'R');
INSERT INTO `outcome` VALUES(9, '2.112b', 'Hard Refusal, Respondent', 10080, 3, 0, 1, 1, 1, 1, 'R');
INSERT INTO `outcome` VALUES(10, '1.1', 'Complete', 0, 4, 0, 1, 1, 1, 0, 'I');
INSERT INTO `outcome` VALUES(11, '2.112', 'Known respondent refusal', 0, 4, 0, 1, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(12, '2.111', 'Household-level refusal', 0, 4, 0, 1, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(13, '2.112c', 'Broken appointment (Implicit refusal)', 10080, 3, 1, 0, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(14, '4.32', 'Disconnected number', 0, 4, 1, 0, 0, 0, 0, '');
INSERT INTO `outcome` VALUES(15, '4.20', 'Fax/data line', 0, 4, 1, 1, 0, 0, 0, '');
INSERT INTO `outcome` VALUES(16, '4.51', 'Business, government office, other organization', 0, 4, 1, 1, 0, 0, 0, '');
INSERT INTO `outcome` VALUES(17, '4.70', 'No eligible respondent', 0, 4, 1, 1, 0, 0, 0, '');
INSERT INTO `outcome` VALUES(18, '2.35a', 'Accidental hang up or temporary phone problem', 0, 1, 1, 1, 1, 1, 0, 'O');
INSERT INTO `outcome` VALUES(19, '2.12a', 'Definite Appointment - Respondent', 0, 5, 0, 1, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(20, '2.12b', 'Definite Appointment - Other', 0, 5, 0, 1, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(21, '2.13a', 'Unspecified Appointment - Respondent', 0, 5, 0, 1, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(22, '2.13b', 'Unspecified Appointment - Other', 0, 5, 0, 1, 1, 1, 0, 'R');
INSERT INTO `outcome` VALUES(23, '2.221', 'Household answering machine - Message left', 180, 1, 1, 1, 1, 1, 0, 'NC');
INSERT INTO `outcome` VALUES(24, '2.222', 'Household answering machine - No message left', 180, 1, 1, 1, 1, 1, 0, 'NC');
INSERT INTO `outcome` VALUES(25, '2.31', 'Respondent Dead', 0, 4, 0, 1, 0, 1, 0, 'O');
INSERT INTO `outcome` VALUES(26, '2.32', 'Physically or mentally unable/incompetent', 0, 4, 0, 1, 0, 1, 0, 'O');
INSERT INTO `outcome` VALUES(27, '2.331', 'Household level language problem', 0, 4, 1, 1, 0, 1, 0, 'O');
INSERT INTO `outcome` VALUES(28, '2.332', 'Respondent language problem', 0, 4, 0, 1, 0, 1, 0, 'O');
INSERT INTO `outcome` VALUES(29, '3.14', 'Answering machine - Not a household', 0, 4, 1, 1, 0, 0, 0, 'UH');
INSERT INTO `outcome` VALUES(30, '4.10', 'Out of sample', 0, 4, 0, 1, 0, 0, 0, '');
INSERT INTO `outcome` VALUES(31, '2.20', 'Non contact', 180, 1, 1, 1, 1, 1, 0, 'NC');
INSERT INTO `outcome` VALUES(32, '4.80', 'Quota filled', 0, 4, 0, 1, 0, 0, 0, '');
INSERT INTO `outcome` VALUES(33, '2.36', 'Miscellaneous - Unavailable for a week', 10080, 1, 0, 1, 1, 1, 0, 'O');

-- --------------------------------------------------------

--
-- Table structure for table `outcome_type`
--

CREATE TABLE `outcome_type` (
  `outcome_type_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`outcome_type_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `outcome_type`
--

INSERT INTO `outcome_type` VALUES(1, 'Temporary Outcomes (normal cases)');
INSERT INTO `outcome_type` VALUES(2, 'Supervisor Outcomes (referred to supervisor)');
INSERT INTO `outcome_type` VALUES(3, 'Refusal Outcomes (respondent refused)');
INSERT INTO `outcome_type` VALUES(4, 'Final Outcomes (completed, final refusal, etc)');
INSERT INTO `outcome_type` VALUES(5, 'Appointments');

-- --------------------------------------------------------

--
-- Table structure for table `process`
--

CREATE TABLE `process` (
  `process_id` bigint(20) NOT NULL auto_increment,
  `type` int(11) NOT NULL default '1',
  `start` datetime NOT NULL,
  `stop` datetime default NULL,
  `kill` tinyint(1) NOT NULL default '0',
  `data` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`process_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `process_log` (
`process_log_id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`process_id` BIGINT NOT NULL ,
`datetime` DATETIME NOT NULL ,
`data` TEXT NOT NULL ,
INDEX ( `process_id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `questionnaire`
--

CREATE TABLE `questionnaire` (
  `questionnaire_id` bigint(20) NOT NULL auto_increment,
  `description` varchar(255) NOT NULL,
  `lime_sid` int(11) NOT NULL,
  `restrict_appointments_shifts` tinyint(1) NOT NULL default '1',
  `restrict_work_shifts` tinyint(1) NOT NULL default '1',
  `testing` tinyint(1) NOT NULL default '0' COMMENT 'Whether this questionnaire is just for testing',
  `respondent_selection` tinyint(1) NOT NULL default '1',
  `rs_intro` text collate utf8_unicode_ci NOT NULL,
  `rs_project_intro` text collate utf8_unicode_ci NOT NULL,
  `rs_project_end` text collate utf8_unicode_ci NOT NULL,
  `rs_callback` text collate utf8_unicode_ci NOT NULL,
  `rs_answeringmachine` text collate utf8_unicode_ci NOT NULL,
  `lime_rs_sid` int(11) default NULL,
  `info` text collate utf8_unicode_ci default NULL,
  `enabled` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `questionnaire`
--


-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_prefill`
--

CREATE TABLE `questionnaire_prefill` (
  `questionnaire_prefill_id` bigint(20) NOT NULL auto_increment,
  `questionnaire_id` bigint(20) NOT NULL,
  `lime_sgqa` varchar(255) collate utf8_unicode_ci NOT NULL,
  `value` varchar(2048) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`questionnaire_prefill_id`),
  KEY `questionnaire_id` (`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table `questionnaire_sample`
--

CREATE TABLE `questionnaire_sample` (
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `call_max` int(11) NOT NULL default '0',
  `call_attempt_max` int(11) NOT NULL default '0',
  `random_select` tinyint(1) NOT NULL default '0',
  `answering_machine_messages` int(11) NOT NULL default '1',
  PRIMARY KEY  (`questionnaire_id`,`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `questionnaire_sample`
--


-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_exclude_priority`
--

CREATE TABLE `questionnaire_sample_exclude_priority` (
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_id` bigint(20) NOT NULL,
  `exclude` tinyint(1) NOT NULL default '0',
  `priority` tinyint(3) NOT NULL default '50',
  `sortorder` int(11) default NULL,
  PRIMARY KEY  (`questionnaire_id`,`sample_id`),
  KEY `exclude` (`exclude`),
  KEY `priority` (`priority`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sortorder` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `questionnaire_sample_exclude_priority`
--


--
-- Table structure for table `questionnaire_sample_quota`
--

CREATE TABLE IF NOT EXISTS `questionnaire_sample_quota` (
  `questionnaire_sample_quota_id` bigint(20) NOT NULL auto_increment,
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `lime_sgqa` varchar(255) collate utf8_unicode_ci NOT NULL,
  `value` varchar(2048) collate utf8_unicode_ci NOT NULL,
  `comparison` varchar(15) collate utf8_unicode_ci NOT NULL default 'LIKE',
  `completions` int(11) NOT NULL,
  `quota_reached` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`questionnaire_sample_quota_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `questionnaire_sample_quota_row`
--

CREATE TABLE IF NOT EXISTS `questionnaire_sample_quota_row` (
  `questionnaire_sample_quota_row_id` bigint(20) NOT NULL auto_increment,
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_import_id` bigint(20) NOT NULL,
  `lime_sgqa` varchar(255) collate utf8_unicode_ci NOT NULL,
  `value` varchar(2048) collate utf8_unicode_ci NOT NULL,
  `comparison` varchar(15) collate utf8_unicode_ci NOT NULL default 'LIKE',
  `completions` int(11) NOT NULL,
  `exclude_var` char(128) collate utf8_unicode_ci NOT NULL,
  `exclude_val` varchar(256) collate utf8_unicode_ci NOT NULL,
  `quota_reached` tinyint(1) NOT NULL default '0',
  `current_completions` int(11) NOT NULL default '0',
  `description` text collate utf8_unicode_ci NOT NULL,
  `priority` tinyint(3) NOT NULL default '50' COMMENT 'Priority from 0 - 100',
  `autoprioritise` tinyint(1) NOT NULL default '0' COMMENT 'Should this row have it''s priority automatically adjusted to 100 - (completions %)',
  PRIMARY KEY  (`questionnaire_sample_quota_row_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sample_import_id` (`sample_import_id`),
  KEY `exclude_var` (`exclude_var`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_sample_quota_row_exclude`
--

CREATE TABLE IF NOT EXISTS `questionnaire_sample_quota_row_exclude` (
  `questionnaire_sample_quota_row_id` bigint(20) NOT NULL,
  `questionnaire_id` bigint(20) NOT NULL,
  `sample_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`questionnaire_sample_quota_row_id`,`questionnaire_id`,`sample_id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `sample_id` (`sample_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



-- --------------------------------------------------------

--
-- Table structure for table `respondent`
--

CREATE TABLE `respondent` (
  `respondent_id` bigint(20) NOT NULL auto_increment,
  `case_id` bigint(20) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `Time_zone_name` char(64) NOT NULL,
  PRIMARY KEY  (`respondent_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `respondent`
--


-- --------------------------------------------------------

--
-- Table structure for table `respondent_not_available`
--

CREATE TABLE `respondent_not_available` (
  `respondent_not_available_id` bigint(20) NOT NULL auto_increment,
  `respondent_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  PRIMARY KEY  (`respondent_not_available_id`),
  KEY `respondent_id` (`respondent_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `respondent_not_available`
--


-- --------------------------------------------------------

--
-- Table structure for table `sample`
--

CREATE TABLE `sample` (
  `sample_id` bigint(20) NOT NULL auto_increment,
  `import_id` bigint(20) NOT NULL,
  `Time_zone_name` char(64) NOT NULL,
  `phone` char(30) NOT NULL,
  PRIMARY KEY  (`sample_id`),
  KEY `import_id` (`import_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `sample`
--


-- --------------------------------------------------------

--
-- Table structure for table `sample_import`
--

CREATE TABLE `sample_import` (
  `sample_import_id` bigint(20) NOT NULL auto_increment,
  `description` varchar(255) NOT NULL,
  `call_restrict` tinyint(1) NOT NULL default '1',
  `refusal_conversion` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`sample_import_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `sample_import`
--

-- --------------------------------------------------------

--
-- Table structure for table `sample_postcode_timezone`
--

CREATE TABLE `sample_postcode_timezone` (
  `val` int(4) NOT NULL,
  `Time_zone_name` char(64) NOT NULL,
  PRIMARY KEY  (`val`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sample_state_timezone`
--

CREATE TABLE `sample_state_timezone` (
  `val` varchar(64) NOT NULL,
  `Time_zone_name` char(64) NOT NULL,
  PRIMARY KEY  (`val`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `sample_prefix_timezone`
--

CREATE TABLE `sample_prefix_timezone` (
  `val` int(10) NOT NULL,
  `Time_zone_name` char(64) NOT NULL,
  PRIMARY KEY  (`val`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `sample_var`
--

CREATE TABLE `sample_var` (
  `sample_id` bigint(20) NOT NULL,
  `var` char(128) NOT NULL,
  `val` varchar(256) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY  (`sample_id`,`var`),
  KEY `sample_id` (`sample_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `sample_var`
--

-- --------------------------------------------------------

--
-- Table structure for table `sample_var_type`
--

CREATE TABLE `sample_var_type` (
  `type` int(11) NOT NULL auto_increment,
  `description` varchar(255) NOT NULL,
  `table` varchar(255) NOT NULL,
  PRIMARY KEY  (`type`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `sample_var_type`
--

INSERT INTO `sample_var_type` VALUES(1, 'String', '');
INSERT INTO `sample_var_type` VALUES(2, 'Phone number', 'sample_prefix_timezone');
INSERT INTO `sample_var_type` VALUES(3, 'Primary phone number', 'sample_prefix_timezone');
INSERT INTO `sample_var_type` VALUES(4, 'State', 'sample_state_timezone');
INSERT INTO `sample_var_type` VALUES(5, 'Postcode', 'sample_postcode_timezone');
INSERT INTO `sample_var_type` VALUES(6, 'Respondent first name', '');
INSERT INTO `sample_var_type` VALUES(7, 'Respondent last name', '');

-- --------------------------------------------------------

--
-- Table structure for table `sessions2`
--

CREATE TABLE `sessions2` (
  `sesskey` varchar(64) NOT NULL default '',
  `expiry` datetime NOT NULL,
  `expireref` varchar(250) default '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `sessdata` longtext,
  PRIMARY KEY  (`sesskey`),
  KEY `sess2_expiry` (`expiry`),
  KEY `sess2_expireref` (`expireref`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `setting_id` int(11) NOT NULL auto_increment,
  `field` varchar(255) collate utf8_unicode_ci NOT NULL,
  `value` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`setting_id`),
  UNIQUE KEY `field` (`field`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Serialised table of settings';

-- --------------------------------------------------------

--
-- Table structure for table `shift`
--

CREATE TABLE `shift` (
  `shift_id` bigint(20) NOT NULL auto_increment,
  `questionnaire_id` bigint(20) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  PRIMARY KEY  (`shift_id`),
  KEY `questionnaire_id` (`questionnaire_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `shift`
--


-- --------------------------------------------------------

--
-- Table structure for table `shift_report`
--

CREATE TABLE `shift_report` (
  `shift_report_id` bigint(20) NOT NULL auto_increment,
  `shift_id` bigint(20) NOT NULL,
  `operator_id` bigint(20) NOT NULL,
  `report` text NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`shift_report_id`),
  KEY `shift_id` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `shift_report`
--


-- --------------------------------------------------------

--
-- Table structure for table `shift_template`
--

CREATE TABLE `shift_template` (
  `day_of_week` tinyint(1) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  KEY `day_of_week` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Dumping data for table `shift_template`
--

INSERT INTO `shift_template` VALUES(2, '17:00:00', '20:30:00');
INSERT INTO `shift_template` VALUES(3, '17:00:00', '20:30:00');
INSERT INTO `shift_template` VALUES(4, '17:00:00', '20:30:00');
INSERT INTO `shift_template` VALUES(5, '17:00:00', '20:30:00');
INSERT INTO `shift_template` VALUES(6, '17:00:00', '20:30:00');
INSERT INTO `shift_template` VALUES(7, '09:00:00', '13:00:00');
INSERT INTO `shift_template` VALUES(7, '13:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `timezone_template`
--

CREATE TABLE `timezone_template` (
  `Time_zone_name` char(64) NOT NULL,
  PRIMARY KEY  (`Time_zone_name`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
