-- noinspection SqlDialectInspectionForFile
-- noinspection SqlNoDataSourceInspectionForFile
DROP DATABASE IF EXISTS ng2016;

CREATE DATABASE ng2016;

USE ng2016;

-- ========================================================
-- Users
DROP TABLE IF EXISTS users;

CREATE TABLE users
(
  id INT AUTO_INCREMENT NOT NULL,

  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL,
  username   VARCHAR(255) NOT NULL,
  personKey  VARCHAR( 40) NOT NULL,

  salt          VARCHAR(255),
  password      VARCHAR(255),
  passwordToken VARCHAR( 20),

  enabled       BOOLEAN  NOT NULL DEFAULT TRUE,
  locked        BOOLEAN  NOT NULL DEFAULT FALSE,
  roles         longtext NOT NULL,
  providerKey   VARCHAR(255), -- Social network

  PRIMARY KEY(id),
  UNIQUE INDEX users_username_index(username),
  UNIQUE INDEX users_email_index   (email)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

DROP TABLE IF EXISTS project_persons;

CREATE TABLE project_persons
(
  id INT AUTO_INCREMENT NOT NULL,

  projectKey VARCHAR( 40) NOT NULL,
  personKey  VARCHAR( 40) NOT NULL,
  orgKey     VARCHAR( 40),

  registered BOOLEAN NOT NULL DEFAULT FALSE, -- Maybe

  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL,
  phone      VARCHAR( 20),
  gender     VARCHAR(  1),
  age        INTEGER,

  refereeBadge     VARCHAR( 20),
  refereeUpgrading VARCHAR( 20),
  refereeApproved  BOOLEAN NOT NULL DEFAULT FALSE,

  notes LONGTEXT,
  plans LONGTEXT,
  avail LONGTEXT,

  PRIMARY KEY(id),
  UNIQUE INDEX project_person_key_index  (projectKey,personKey),
  UNIQUE INDEX project_person_name_index (projectKey,name)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

