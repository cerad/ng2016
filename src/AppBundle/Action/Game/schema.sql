
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

