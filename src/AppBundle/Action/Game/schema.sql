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

  CONSTRAINT projectGames_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectGames_unique_gameNumber UNIQUE(projectKey,gameNumber)

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

  poolKey   VARCHAR(40) NOT NULL,
  poolType  VARCHAR(20) NOT NULL,

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

  CONSTRAINT projectPoolTeams_primaryKey PRIMARY KEY(id),

  CONSTRAINT projectPoolTeams_unique_poolTeamKey UNIQUE(projectKey,poolTeamKey),

  INDEX      projectPoolTeams_index_poolKey(projectKey,poolKey)

) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
