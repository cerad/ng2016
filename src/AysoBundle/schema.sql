
-- DROP DATABASE   ayso;
-- CREATE DATABASE ayso;
-- USE             ayso;

DROP TABLE IF EXISTS vols;

CREATE TABLE vols
(
  fedKey  VARCHAR( 20) NOT NULL,
  name    VARCHAR(255),
  email   VARCHAR(255),
  phone   VARCHAR(255),
  gender  VARCHAR(  8),
  sar     VARCHAR( 20),
  regYear VARCHAR( 20),

  CONSTRAINT aysoVols_primaryKey PRIMARY KEY(fedKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

DROP TABLE IF EXISTS certs;

CREATE TABLE certs
(
  fedKey    VARCHAR( 20) NOT NULL,
  role      VARCHAR( 40) NOT NULL,
  roleDate  DATE,
  badge     VARCHAR( 40),
  badgeDate DATE,
  badgeSort INTEGER,

  CONSTRAINT aysoCerts_primaryKey PRIMARY KEY(fedKey,role)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

DROP TABLE IF EXISTS orgs;

CREATE TABLE orgs
(
  orgKey VARCHAR(20) NOT NULL,
  sar    VARCHAR(20) NOT NULL,
  state  VARCHAR( 4),
  comms  LONGTEXT, -- communities

  CONSTRAINT aysoOrgs_primaryKey PRIMARY KEY(orgKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ALTER TABLE orgs ADD state VARCHAR(4);

DROP TABLE IF EXISTS orgStates;

CREATE TABLE orgStates
(
  orgKey VARCHAR(20) NOT NULL,
  state  VARCHAR( 4) NOT NULL,

  CONSTRAINT aysoOrgs_primaryKey PRIMARY KEY(orgKey,state),

  INDEX  ayso_orgStates_state(state)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
