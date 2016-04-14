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

  CONSTRAINT users_primary_key     PRIMARY KEY(id),
  CONSTRAINT users_unique_username UNIQUE(username),
  CONSTRAINT users_unique_email    UNIQUE(email)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

DROP TABLE IF EXISTS project_persons;

CREATE TABLE project_persons
(
  id INT AUTO_INCREMENT NOT NULL,

  projectKey VARCHAR( 40) NOT NULL,
  personKey  VARCHAR( 40) NOT NULL,
  orgKey     VARCHAR( 40),
  fedKey     VARCHAR( 40),

  registered BOOLEAN NOT NULL DEFAULT FALSE, -- Maybe

  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL,
  phone      VARCHAR( 20),
  gender     VARCHAR(  1),
  age        INTEGER,

  refereeBadge     VARCHAR( 20),
  refereeUpgrading VARCHAR( 20),
  refereeApproved  BOOLEAN NOT NULL DEFAULT FALSE,

  notes     LONGTEXT,
  notesUser LONGTEXT,
  plans     LONGTEXT,
  avail     LONGTEXT,
  roles     LONGTEXT,

  CONSTRAINT project_person_primary_key PRIMARY KEY(id),

  CONSTRAINT project_person_unique_key  UNIQUE(projectKey,personKey),
  CONSTRAINT project_person_unique_name UNIQUE(projectKey,name)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ====================================================================
-- Project specific roles
DROP TABLE IF EXISTS projectPersonRoles;

CREATE TABLE projectPersonRoles
(
  id              INT AUTO_INCREMENT NOT NULL,
  projectPersonId INT, -- Parent

-- projectKey VARCHAR( 40) NOT NULL, -- Avoid the join?
-- personKey  VARCHAR( 40) NOT NULL,

  role     VARCHAR( 40), -- ROLE_REFEREE, ROLE_SCORE_ENTRY etc

  active   BOOLEAN NOT NULL DEFAULT true,  -- Role is used by security
  approved BOOLEAN NOT NULL DEFAULT FALSE, -- Set by assignor
  verified BOOLEAN NOT NULL DEFAULT FALSE, -- Set by verifier
  ready    BOOLEAN NOT NULL DEFAULT TRUE,  -- Set by user(controls active?)

  badge    VARCHAR(20),
  since    DATETIME,

-- fedKey   VARCHAR(40), -- Player or Volunteer

  misc  LONGTEXT, -- upgrading, assessments, mentoring etc
  notes LONGTEXT,

  CONSTRAINT projectPersonRoles_primaryKey PRIMARY KEY(id),

  CONSTRAINT
    ProjectPersonRoles_foreignKey_parent
    FOREIGN KEY(projectPersonId)
    REFERENCES  project_persons(id)
    ON DELETE CASCADE,

  CONSTRAINT projectPersonRoles_unique_role UNIQUE(projectPersonId,role)

-- INDEX project_person_roles_index_role(projectKey,personKey,role,active)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

