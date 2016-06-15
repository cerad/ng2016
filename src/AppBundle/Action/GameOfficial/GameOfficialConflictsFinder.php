<?php
namespace AppBundle\Action\GameOfficial;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Game\GameOfficial;
use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use Doctrine\DBAL\Connection;

class GameOfficialConflictsFinder
{
    private $gameConn;

    public function __construct(
        Connection $gameConn
    ) {
        $this->gameConn = $gameConn;
    }
    /* =====================================================
     * Slot kickoff is 10am
     * Pick up games that cover 10 am
     * game.start <= 10 am and game.end >= 10am
     *
     * game.start <= slot.finish AND game.finish >= slot.start
     */

    // Return a list of conflicts of some sort
    public function findGameOfficialConflicts(Game $game, GameOfficial $gameOfficial)
    {
        // No conflicts for unassigned slots
        $regPersonId = $gameOfficial->regPersonId;
        if (!$regPersonId) {
            return [];
        }
        // Find any overlapping slots
        $sql = <<<EOD
SELECT 
  game.gameId,
  game.gameNumber,
  game.start,
  game.fieldName,
  gameOfficial.slot          AS gameOfficialSlot,
  gameOfficial.regPersonName AS gameOfficialName
FROM games AS game
LEFT JOIN gameOfficials AS gameOfficial ON gameOfficial.gameId = game.gameId
WHERE gameOfficial.regPersonId = ? AND game.start <= ? AND game.finish >= ?
EOD;
        $stmt = $this->gameConn->executeQuery($sql,[$regPersonId,$game->finish,$game->start]);
        $games = [];
        $gameId = $game->gameId;
        $gameOfficialSlot = $gameOfficial->slot;
        while($row = $stmt->fetch()) {
            if ($row['gameId'] !== $gameId) {
                $games[] = $row;
            } else {
                if ((integer)$row['gameOfficialSlot'] !== $gameOfficialSlot) {
                    $games[] = $row;
                }
            }
        }
        if (count($games) < 1) {
            return [];
        }
        // Need game details
        return $games;
        
        $sql = <<<EOD
SELECT 
  regPerson.id AS regPersonId,
  
  projectKey AS projectId,
  personKey  AS personId,
  orgKey     AS orgId,
  fedKey     AS fedId,
  regYear    AS regYear,
  name       AS name,
  email      AS email,
  shirtSize  AS shirtSize,
  badge      AS refereeBadge
FROM projectPersons as regPerson
LEFT JOIN 
  projectPersonRoles AS regPersonRole 
  ON  regPersonRole.projectPersonId = regPerson.id 
  AND regPersonRole.role = 'CERT_REFEREE'
WHERE
  regPerson.projectKey = ? AND regPerson.personKey = ?
EOD;
        $stmt = $this->regPersonConn->executeQuery($sql,[$projectId,$phyPersonId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null; // Exception
        }
        // Really need a sars view property
        $row['orgView'] = $this->orgFinder->transform($row['orgId']);

        return $row;
    }
}
