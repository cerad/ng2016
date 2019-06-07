-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 14, 2018 at 10:23 PM
-- Server version: 5.6.33-0ubuntu0.14.04.1
-- PHP Version: 7.2.7-1+ubuntu14.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `noc2018games`
--
CREATE DATABASE IF NOT EXISTS `noc2018games` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `noc2018games`;

-- --------------------------------------------------------

--
-- Table structure for table `gameOfficials`
--

DROP TABLE IF EXISTS `gameOfficials`;
CREATE TABLE `gameOfficials` (
  `gameOfficialId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `projectId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `gameId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `gameNumber` int(11) NOT NULL,
  `slot` int(11) NOT NULL,
  `phyPersonId` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `regPersonId` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `regPersonName` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `assignRole` varchar(40) COLLATE utf8_unicode_ci DEFAULT 'ROLE_REFEREE',
  `assignState` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
CREATE TABLE `games` (
  `gameId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `projectId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `gameNumber` int(11) NOT NULL,
  `role` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'game',
  `fieldName` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `venueName` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `start` datetime DEFAULT NULL,
  `finish` datetime DEFAULT NULL,
  `state` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Published',
  `status` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Normal',
  `reportText` longtext COLLATE utf8_unicode_ci,
  `reportState` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Initial',
  `selfAssign` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gameTeams`
--

DROP TABLE IF EXISTS `gameTeams`;
CREATE TABLE `gameTeams` (
  `gameTeamId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `projectId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `gameId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `gameNumber` int(11) NOT NULL,
  `slot` int(11) NOT NULL,
  `poolTeamId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `results` int(11) DEFAULT NULL,
  `resultsDetail` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pointsScored` int(11) DEFAULT NULL,
  `pointsAllowed` int(11) DEFAULT NULL,
  `pointsEarned` int(11) DEFAULT NULL,
  `pointsDeducted` int(11) DEFAULT NULL,
  `sportsmanship` int(11) DEFAULT NULL,
  `injuries` int(11) DEFAULT NULL,
  `misconduct` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poolTeams`
--

DROP TABLE IF EXISTS `poolTeams`;
CREATE TABLE `poolTeams` (
  `poolTeamId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `projectId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `poolKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `poolTypeKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `poolTeamKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `poolView` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `poolSlotView` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `poolTypeView` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `poolTeamView` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `poolTeamSlotView` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sourcePoolKeys` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sourcePoolSlot` int(11) DEFAULT NULL,
  `program` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `age` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `division` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `regTeamId` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `regTeamName` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `regTeamPoints` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regTeams`
--

DROP TABLE IF EXISTS `regTeams`;
CREATE TABLE `regTeams` (
  `regTeamId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `projectId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `teamKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `teamNumber` int(11) NOT NULL,
  `teamName` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `teamPoints` int(11) DEFAULT NULL,
  `orgId` varchar(99) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orgView` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `program` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `age` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `division` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gameOfficials`
--
ALTER TABLE `gameOfficials`
  ADD PRIMARY KEY (`gameOfficialId`),
  ADD UNIQUE KEY `gameOfficials_unique_gameNumberSlot` (`projectId`,`gameNumber`,`slot`),
  ADD KEY `gameOfficials_index_gameId` (`gameId`),
  ADD KEY `gameOfficials_index_regPersonId` (`regPersonId`),
  ADD KEY `gameOfficials_index_phyPersonId` (`phyPersonId`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`gameId`),
  ADD UNIQUE KEY `games_unique_gameNumber` (`projectId`,`gameNumber`);

--
-- Indexes for table `gameTeams`
--
ALTER TABLE `gameTeams`
  ADD PRIMARY KEY (`gameTeamId`),
  ADD UNIQUE KEY `gameTeams_unique_gameNumberSlot` (`projectId`,`gameNumber`,`slot`),
  ADD KEY `gameTeams_index_gameId` (`gameId`),
  ADD KEY `gameTeams_index_poolTeamId` (`poolTeamId`);

--
-- Indexes for table `poolTeams`
--
ALTER TABLE `poolTeams`
  ADD PRIMARY KEY (`poolTeamId`),
  ADD UNIQUE KEY `poolTeams_unique_poolTeamKey` (`projectId`,`poolTeamKey`),
  ADD KEY `poolTeams_index_poolKey` (`projectId`,`poolKey`),
  ADD KEY `poolTeams_index_poolTypeKey` (`projectId`,`poolTypeKey`);

--
-- Indexes for table `regTeams`
--
ALTER TABLE `regTeams`
  ADD PRIMARY KEY (`regTeamId`),
  ADD UNIQUE KEY `regTeams_unique_teamKey` (`projectId`,`teamKey`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
