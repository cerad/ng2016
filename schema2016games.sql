-- ======================================================================
-- Project Game
--
DROP TABLE IF EXISTS projectGames;

CREATE TABLE projectGames
(
  id         VARCHAR(99) NOT NULL,
  projectKey VARCHAR(40) NOT NULL,
  gameNumber INTEGER     NOT NULL,

  fieldName  VARCHAR(99),
  venueName  VARCHAR(99),

  start   DATETIME,
  finish  DATETIME,

  state   VARCHAR(40) NOT NULL DEFAULT 'Published',
  status  VARCHAR(40) NOT NULL DEFAULT 'Normal',

  reportText  LONGTEXT,
  reportState VARCHAR(40) NOT NULL DEFAULT 'Initial',

  CONSTRAINT projectGames_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectGames_unique_gameNumber UNIQUE(projectKey,gameNumber)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Project Game Team
--
DROP TABLE IF EXISTS projectGameTeams;

CREATE TABLE projectGameTeams
(
  id         VARCHAR(99) NOT NULL,
  projectKey VARCHAR(40) NOT NULL,
  gameNumber INTEGER     NOT NULL,
  slot       INTEGER     NOT NULL,

  name       VARCHAR(99), -- Move to pool team?

  results        INTEGER,     -- 1 => Won, 2 => Lost, 3 => Tied, 4 => Not Played etc
  resultsDetail  VARCHAR(40), -- Won/Lost/Tied, Won By Forfeit, Won in Extra Time, Won by KFTM, Not Played

  pointsScored   INTEGER,     -- Usually goals but try a more generic term
  pointsAllowed  INTEGER,

  pointsEarned   INTEGER,
  pointsDeducted INTEGER,

  sportsmanship  INTEGER,
  injuries       INTEGER,

  misconduct     LONGTEXT, -- array

  gameId        VARCHAR(99) NOT NULL,
  poolTeamId    VARCHAR(99) NOT NULL,
  projectTeamId VARCHAR(99), -- not currently being used
  orgKey        VARCHAR(99), -- move to pool team?

  CONSTRAINT projectGameTeams_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectGameTeams_unique_gameNumberSlot UNIQUE(projectKey,gameNumber,slot),

  INDEX      projectGameTeams_index_gameId       (gameId),
  INDEX      projectGameTeams_index_poolTeamId   (poolTeamId),
  INDEX      projectGameTeams_index_projectTeamId(projectTeamId)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Project Pool Team
--
DROP TABLE IF EXISTS projectPoolTeams;

CREATE TABLE projectPoolTeams
(
  id          VARCHAR(99) NOT NULL,
  projectKey  VARCHAR(40) NOT NULL,
  poolTeamKey VARCHAR(40) NOT NULL,
  poolKey     VARCHAR(40) NOT NULL,
  poolType    VARCHAR(20) NOT NULL,

  poolView         VARCHAR(40),
  poolTypeView     VARCHAR(20),
  poolTeamView     VARCHAR(40),
  poolTeamSlotView VARCHAR(40),

  sourcePoolKeys VARCHAR(255),
  sourcePoolSlot integer,

  program  VARCHAR(20),
  gender   VARCHAR(20),
  age      VARCHAR(20),
  division VARCHAR(20),

  projectTeamId VARCHAR(99), -- Maybe

  -- name From projectTeam aka registeredTeam
  -- points
  -- orgKey,orgView

  CONSTRAINT projectPoolTeams_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectPoolTeams_unique_poolTeamKey UNIQUE(projectKey,poolTeamKey),

  INDEX      projectPoolTeams_index_poolKey(projectKey,poolKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Project Team
--
DROP TABLE IF EXISTS projectTeams;

CREATE TABLE projectTeams
(
  id         VARCHAR(99) NOT NULL,
  projectKey VARCHAR(40) NOT NULL,
  teamKey    VARCHAR(40) NOT NULL,
  teamNumber INTEGER     NOT NULL,

  name       VARCHAR(99) NOT NULL,
  coach      VARCHAR(99),
  points     INTEGER,
  status     VARCHAR(40) NOT NULL DEFAULT 'Active',
  orgKey     VARCHAR(40),
  -- orgKeyView

  program    VARCHAR(20),
  gender     VARCHAR(20),
  age        VARCHAR(20),
  division   VARCHAR(20),

  CONSTRAINT projectTeams_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectTeams_unique_teamKey UNIQUE(projectKey,teamKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Project Game Official
--
DROP TABLE IF EXISTS projectGameOfficials;

CREATE TABLE projectGameOfficials
(
  id         VARCHAR(99) NOT NULL, -- projectKey:gameNumber:slot
  projectKey VARCHAR(40) NOT NULL,
  gameNumber INTEGER     NOT NULL,
  slot       INTEGER     NOT NULL,
  name       VARCHAR(99),
  -- details about the official
  assignRole   VARCHAR(40) DEFAULT 'ROLE_REFEREE',
  assignState  VARCHAR(40),

  gameId          VARCHAR(99) NOT NULL,
  projectPersonId VARCHAR(99), -- aka RegisteredPersonId along with PhysicalPersonKey

  CONSTRAINT projectGameOfficials_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectGameOfficials_unique_gameNumberSlot UNIQUE(projectKey,gameNumber,slot),

  INDEX      projectGameOfficials_index_gameId(gameId),

  INDEX      projectGameOfficials_index_projectPersonId(projectPersonId)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
