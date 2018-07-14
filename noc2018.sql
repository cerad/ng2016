-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 14, 2018 at 10:24 PM
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
-- Database: `noc2018`
--
CREATE DATABASE IF NOT EXISTS `noc2018` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `noc2018`;

-- --------------------------------------------------------

--
-- Table structure for table `projectPersonRoles`
--

DROP TABLE IF EXISTS `projectPersonRoles`;
CREATE TABLE `projectPersonRoles` (
  `id` int(10) UNSIGNED NOT NULL,
  `projectPersonId` int(10) UNSIGNED NOT NULL,
  `role` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `roleDate` date DEFAULT NULL,
  `badge` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `badgeDate` date DEFAULT NULL,
  `badgeUser` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `badgeExpires` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `ready` tinyint(1) NOT NULL DEFAULT '1',
  `misc` longtext COLLATE utf8_unicode_ci,
  `notes` longtext COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projectPersons`
--

DROP TABLE IF EXISTS `projectPersons`;
CREATE TABLE `projectPersons` (
  `id` int(10) UNSIGNED NOT NULL,
  `projectKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `personKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `orgKey` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fedKey` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `regYear` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `registered` tinyint(1) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `shirtSize` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notes` longtext COLLATE utf8_unicode_ci,
  `notesUser` longtext COLLATE utf8_unicode_ci,
  `plans` longtext COLLATE utf8_unicode_ci,
  `avail` longtext COLLATE utf8_unicode_ci,
  `createdOn` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedOn` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `version` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regPersonPersons`
--

DROP TABLE IF EXISTS `regPersonPersons`;
CREATE TABLE `regPersonPersons` (
  `managerId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `managerName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `memberId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `memberName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `role` varchar(39) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regPersonTeams`
--

DROP TABLE IF EXISTS `regPersonTeams`;
CREATE TABLE `regPersonTeams` (
  `managerId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `teamId` varchar(99) COLLATE utf8_unicode_ci NOT NULL,
  `teamName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `role` varchar(39) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Family'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `personKey` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `emailToken` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `emailVerified` tinyint(1) NOT NULL DEFAULT '0',
  `salt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwordToken` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `roles` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ROLE_USER',
  `providerKey` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `projectPersonRoles`
--
ALTER TABLE `projectPersonRoles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `projectPersonRoles_unique_role` (`role`,`projectPersonId`),
  ADD KEY `ProjectPersonRoles_foreignKey_parent` (`projectPersonId`);

--
-- Indexes for table `projectPersons`
--
ALTER TABLE `projectPersons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `projectPerson_unique_person` (`projectKey`,`personKey`),
  ADD UNIQUE KEY `projectPerson_unique_name` (`projectKey`,`name`);

--
-- Indexes for table `regPersonPersons`
--
ALTER TABLE `regPersonPersons`
  ADD PRIMARY KEY (`managerId`,`memberId`,`role`);

--
-- Indexes for table `regPersonTeams`
--
ALTER TABLE `regPersonTeams`
  ADD PRIMARY KEY (`managerId`,`teamId`,`role`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_unique_username` (`username`),
  ADD UNIQUE KEY `users_unique_email` (`email`),
  ADD UNIQUE KEY `users_unique_provider` (`providerKey`),
  ADD UNIQUE KEY `users_unique_emailToken` (`emailToken`),
  ADD UNIQUE KEY `users_unique_passwordToken` (`passwordToken`),
  ADD KEY `users_index_personKey` (`personKey`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `projectPersonRoles`
--
ALTER TABLE `projectPersonRoles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projectPersons`
--
ALTER TABLE `projectPersons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `projectPersonRoles`
--
ALTER TABLE `projectPersonRoles`
  ADD CONSTRAINT `ProjectPersonRoles_foreignKey_parent` FOREIGN KEY (`projectPersonId`) REFERENCES `projectPersons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
