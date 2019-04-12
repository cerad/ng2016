
-- DROP DATABASE   ayso;
-- CREATE DATABASE ayso;
-- USE             ayso;

DROP TABLE IF EXISTS FedPersons;

CREATE TABLE FedPersons
(
  FedPersonId  VARCHAR( 40) NOT NULL,
  FedId        VARCHAR( 20) NOT NULL,
  FullName     VARCHAR(255),
  AgeGroup     VARCHAR( 20),
  FedOrgId     VARCHAR( 40),
  MemYear      VARCHAR( 20),

  CONSTRAINT PK_FedPersons PRIMARY KEY(FedPersonId)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

DROP TABLE IF EXISTS CertDesc;

CREATE TABLE CertDesc
(
  `group`  VARCHAR( 40) NOT NULL,
  `desc`   VARCHAR( 80) NOT NULL,

  CONSTRAINT PK_CertDesc PRIMARY KEY(`group`,`desc`)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
