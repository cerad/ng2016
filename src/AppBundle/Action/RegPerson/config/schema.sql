-- ====================================================================
-- For crews
-- Still need to work with ids here?

DROP TABLE IF EXISTS regPersonPersons;

CREATE TABLE regPersonPersons
(
  -- regPersonPersonId VARCHAR(255) NOT NULL, -- Try composite id

  managerId   VARCHAR( 99) NOT NULL, -- RegPersonId
  managerName VARCHAR(255) NOT NULL,

  memberId   VARCHAR( 99) NOT NULL,  -- RegPersonId
  memberName VARCHAR(255) NOT NULL,

  role VARCHAR(39) NOT NULL,

  CONSTRAINT regPersonPersons_primaryKey PRIMARY KEY(managerId,memberId,role)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

