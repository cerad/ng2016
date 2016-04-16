
DROP TABLE IF EXISTS vols;

CREATE TABLE vols
(
  fedKey  VARCHAR( 20),
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
  fedKey    VARCHAR( 20),
  role      VARCHAR( 20),
  roleDate  DATE,
  badge     VARCHAR( 20),
  badgeDate DATE,

  CONSTRAINT aysoCerts_primaryKey PRIMARY KEY(fedKey,role)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

CREATE TABLE orgs
(
  orgKey VARCHAR( 20),
  sar    VARCHAR( 20),

  CONSTRAINT aysoOrgs_primaryKey PRIMARY KEY(orgKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
