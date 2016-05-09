-- ======================================================================
-- Game
--
DROP TABLE IF EXISTS games;

CREATE TABLE games
(
  gameId     VARCHAR(99) NOT NULL,
  projectId  VARCHAR(99) NOT NULL,
  gameNumber INTEGER     NOT NULL,
  role       VARCHAR(20) NOT NULL DEFAULT 'game', -- kftm, scrimmage
  fieldName  VARCHAR(99),
  venueName  VARCHAR(99),

  start   DATETIME,
  finish  DATETIME,

  state   VARCHAR(40) NOT NULL DEFAULT 'Published',
  status  VARCHAR(40) NOT NULL DEFAULT 'Normal',

  reportText  LONGTEXT,
  reportState VARCHAR(40) NOT NULL DEFAULT 'Initial',

  CONSTRAINT games_primaryKey PRIMARY KEY(gameId),

  CONSTRAINT games_unique_gameNumber UNIQUE(projectId,gameNumber)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Game Team
--
DROP TABLE IF EXISTS gameTeams;

CREATE TABLE gameTeams
(
  gameTeamId  VARCHAR(99) NOT NULL,
  projectId   VARCHAR(99) NOT NULL,
  gameId      VARCHAR(99) NOT NULL,
  gameNumber  INTEGER     NOT NULL,
  slot        INTEGER     NOT NULL,

  poolTeamId  VARCHAR(99) NOT NULL, -- Required

  results        INTEGER,     -- 1 => Won, 2 => Lost, 3 => Tied, 4 => Not Played etc
  resultsDetail  VARCHAR(40), -- Won/Lost/Tied, Won By Forfeit, Won in Extra Time, Won by KFTM, Not Played

  pointsScored   INTEGER,     -- Usually goals but try a more generic term
  pointsAllowed  INTEGER,

  pointsEarned   INTEGER,
  pointsDeducted INTEGER,

  sportsmanship  INTEGER,
  injuries       INTEGER,

  misconduct     LONGTEXT, -- array

  CONSTRAINT gameTeams_primaryKey PRIMARY KEY(gameTeamId),

  CONSTRAINT gameTeams_unique_gameNumberSlot UNIQUE(projectId,gameNumber,slot),

  INDEX      gameTeams_index_gameId    (gameId),
  INDEX      gameTeams_index_poolTeamId(poolTeamId)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Pool Team
--
DROP TABLE IF EXISTS poolTeams;

CREATE TABLE poolTeams
(
  poolTeamId  VARCHAR(99) NOT NULL,
  projectId   VARCHAR(99) NOT NULL,

  poolKey     VARCHAR(40) NOT NULL,
  poolTypeKey VARCHAR(40) NOT NULL,
  poolTeamKey VARCHAR(40) NOT NULL,

  poolView         VARCHAR(40),
  poolTypeView     VARCHAR(40),
  poolTeamView     VARCHAR(40),
  poolTeamSlotView VARCHAR(40),

  sourcePoolKeys VARCHAR(255),
  sourcePoolSlot integer,

  program  VARCHAR(20),
  gender   VARCHAR(20),
  age      VARCHAR(20),
  division VARCHAR(20),

  regTeamId     VARCHAR(99),
  regTeamName   VARCHAR(99), -- Sync with RegTeam
  regTeamPoints INTEGER,     -- Sync with RegTeam

  CONSTRAINT poolTeams_primaryKey PRIMARY KEY(poolTeamId),

  CONSTRAINT poolTeams_unique_poolTeamKey UNIQUE(projectId,poolTeamKey),

  INDEX      poolTeams_index_poolKey    (projectId,poolKey),
  INDEX      poolTeams_index_poolTypeKey(projectId,poolTypeKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Game Official
--
DROP TABLE IF EXISTS gameOfficials;

CREATE TABLE gameOfficials
(
  gameOfficialId VARCHAR(99) NOT NULL, -- projectId:gameNumber:slot
  projectId      VARCHAR(99) NOT NULL,
  gameId         VARCHAR(99) NOT NULL,
  gameNumber     INTEGER     NOT NULL,
  slot           INTEGER     NOT NULL,

  phyPersonId    VARCHAR(99), -- Physical Person (for conflicts?)
  regPersonId    VARCHAR(99),
  regPersonName  VARCHAR(99), -- Sync with Registered Person

  assignRole   VARCHAR(40) DEFAULT 'ROLE_REFEREE',
  assignState  VARCHAR(40),

  CONSTRAINT gameOfficials_primaryKey PRIMARY KEY(gameOfficialId),

  CONSTRAINT gameOfficials_unique_gameNumberSlot UNIQUE(projectId,gameNumber,slot),

  INDEX      gameOfficials_index_gameId(gameId),

  INDEX      gameOfficials_index_regPersonId(regPersonId),
  INDEX      gameOfficials_index_phyPersonId(phyPersonId)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

-- ======================================================================
-- Registered Team
--
DROP TABLE IF EXISTS regTeams;

CREATE TABLE regTeams
(
  regTeamId  VARCHAR(99) NOT NULL,
  projectId  VARCHAR(99) NOT NULL,

  teamKey    VARCHAR(40) NOT NULL,
  teamNumber INTEGER     NOT NULL,
  teamName   VARCHAR(99) NOT NULL,
  teamPoints INTEGER,

  orgId      VARCHAR(99),
  orgView    VARCHAR(40), -- Maybe to avoid hitting ayso

  program    VARCHAR(20),
  gender     VARCHAR(20),
  age        VARCHAR(20),
  division   VARCHAR(20),

  CONSTRAINT regTeams_primaryKey PRIMARY KEY(regTeamId),

  CONSTRAINT regTeams_unique_teamKey UNIQUE(projectId,teamKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
