-- noinspection SqlDialectInspectionForFile
-- noinspection SqlNoDataSourceInspectionForFile

-- DROP DATABASE IF EXISTS ng2016;
-- CREATE DATABASE ng2016;
-- USE ng2016;

-- ========================================================
-- Users
DROP TABLE IF EXISTS users;

CREATE TABLE users
(
  id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,

  name VARCHAR(255) NOT NULL,

  email         VARCHAR(255) NOT NULL,
  emailToken    VARCHAR( 20),
  emailVerified BOOLEAN DEFAULT FALSE,

  username  VARCHAR(255) NOT NULL,
  personKey VARCHAR( 40) NOT NULL,

  salt          VARCHAR(255),
  password      VARCHAR(255),
  passwordToken VARCHAR( 20),

  enabled       BOOLEAN  NOT NULL DEFAULT TRUE,
  locked        BOOLEAN  NOT NULL DEFAULT FALSE,

  roles         VARCHAR(255) NOT NULL DEFAULT 'ROLE_USER', -- LONGTEXT cannot have default value

  providerKey   VARCHAR(255), -- Social network

  CONSTRAINT users_primary_key     PRIMARY KEY(id),
  CONSTRAINT users_unique_username UNIQUE(username),
  CONSTRAINT users_unique_email    UNIQUE(email),
  CONSTRAINT users_unique_provider UNIQUE(providerKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ===================================================================
-- The main registration information
DROP TABLE IF EXISTS projectPersons;

CREATE TABLE projectPersons
(
  id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,

  projectKey VARCHAR( 40) NOT NULL,
  personKey  VARCHAR( 40) NOT NULL,
  orgKey     VARCHAR( 40),
  fedKey     VARCHAR( 40),

  registered BOOLEAN DEFAULT NULL,
  verified   BOOLEAN DEFAULT NULL, -- NULL implies information does not need to be verified

  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL,
  phone      VARCHAR( 20),
  gender     VARCHAR(  1),
  age        INTEGER,

  notes     LONGTEXT,
  notesUser LONGTEXT,
  plans     LONGTEXT,
  avail     LONGTEXT,

  CONSTRAINT projectPerson_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectPerson_unique_person UNIQUE(projectKey,personKey),
  CONSTRAINT projectPerson_unique_name   UNIQUE(projectKey,name)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ====================================================================
-- Project specific roles
DROP TABLE IF EXISTS projectPersonRoles;

CREATE TABLE projectPersonRoles
(
  id              INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,
  projectPersonId INTEGER UNSIGNED, -- Parent

-- projectKey VARCHAR(40) NOT NULL, -- Avoid the join?
-- personKey  VARCHAR(40) NOT NULL,
-- fedKey     VARCHAR(40), -- Player or Volunteer

  role     VARCHAR(40), -- ROLE_REFEREE, ROLE_SCORE_ENTRY etc

  active   BOOLEAN NOT NULL DEFAULT TRUE,  -- Role is used by security
  approved BOOLEAN NOT NULL DEFAULT FALSE, -- Set by assignor
  verified BOOLEAN NOT NULL DEFAULT FALSE, -- Set by verifier, might not be needed
  ready    BOOLEAN NOT NULL DEFAULT TRUE,  -- Set by user

  badge     VARCHAR(20),
  badgeUser VARCHAR(20),
  badgeDate DATETIME,

  misc  LONGTEXT, -- upgrading, assessments, mentoring etc
  notes LONGTEXT,

  CONSTRAINT projectPersonRoles_primaryKey PRIMARY KEY(id),

  CONSTRAINT
    ProjectPersonRoles_foreignKey_parent
    FOREIGN KEY(projectPersonId)
    REFERENCES  projectPersons(id)
    ON DELETE CASCADE,

  CONSTRAINT projectPersonRoles_unique_role UNIQUE(role,projectPersonId)

-- INDEX project_person_roles_index_role(projectKey,personKey,role,active)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

