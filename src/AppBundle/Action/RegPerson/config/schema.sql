-- ====================================================================
-- For crews
-- Still need to work with ids here?

DROP TABLE IF EXISTS regPersonPersons;

CREATE TABLE regPersonPersons
(
  -- regPersonPersonId VARCHAR(255) NOT NULL, -- Try composite id

  managerId   VARCHAR( 99) NOT NULL, -- RegPersonId
  managerName VARCHAR(255) NOT NULL, -- Is this really neede?

  memberId   VARCHAR( 99) NOT NULL,  -- RegPersonId
  memberName VARCHAR(255) NOT NULL,

  role VARCHAR(39) NOT NULL,

  CONSTRAINT regPersonPersons_primaryKey PRIMARY KEY(managerId,memberId,role)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

DROP TABLE IF EXISTS regPersonTeams;

CREATE TABLE regPersonTeams
(
  managerId   VARCHAR( 99) NOT NULL, -- RegPersonId

  teamId   VARCHAR( 99) NOT NULL,  -- RegTeamId
  teamName VARCHAR(255) NOT NULL,  -- Different databases

  role VARCHAR(39) NOT NULL DEFAULT 'Family', -- Probably won't be used

  CONSTRAINT regPersonTeams_primaryKey PRIMARY KEY(managerId,teamId,role)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

