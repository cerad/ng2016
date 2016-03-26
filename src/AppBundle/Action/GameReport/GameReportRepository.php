<?php
namespace AppBundle\Action\GameReport;

use Cerad\Bundle\ProjectBundle\ProjectFactory;

use Doctrine\DBAL\Connection;

/** ================================================
 *  This is basically a view model
 * 
 * gameReport
 *   status
 *   notes
 *   desc
 *   game
 *     id
 *     number
 *     fieldName
 *   teamReports
 *     teamReport (slot = 1)
 *       team
 *         id
 *         name
 *       status
 *       goalsScored
 *       etc
 */
class GameReportRepository
{
    /** @var  Connection */
    private $conn;

    /** @var ProjectFactory */
    private $projectFactory;

    public function __construct(Connection $conn, ProjectFactory $projectFactory)
    {
        $this->conn = $conn;
        $this->projectFactory = $projectFactory;
    }

    public function find($projectKey, $gameNumber)
    {
        if (!$gameNumber) {
            return null;
        }
        $qb = $this->conn->createQueryBuilder();

        $qb->addSelect([
            'project_game.id         AS id',
            'project_game.num        AS number',
            'project_game.dtBeg      AS start',
            'project_game.fieldName  AS fieldName',
            'project_game.groupType  AS groupType',
            'project_game.groupName  AS groupName',
            'project_game.levelKey   AS levelKey',
            'project_game.status     AS status',
            'project_game.report     AS report',
        ]);
        $qb->from('games', 'project_game');

        $qb->andWhere('project_game.projectKey = :projectKey');
        $qb->andWhere('project_game.num        = :gameNumber');

        $qb->setParameter('projectKey', $projectKey);
        $qb->setParameter('gameNumber', $gameNumber);

        $stmt = $qb->execute();
        $game = $stmt->fetch();
        if (!$game) return null;

        $gameReport = $this->projectFactory->createProjectGameReport($game);

        $start = \DateTime::createFromFormat('Y-m-d H:i:s', $game['start']);
        $start = $start->format('D, g:i A');

        $gameReport['desc'] = sprintf('Game Report #%d: %s, %s on %s',
            $gameNumber,
            $game['levelKey'],
            $start,
            $game['fieldName']
        );

        // Report
        $report = isset($game['report']) ? unserialize($game['report']) : [];
        foreach ($report as $key => $value) {
            $gameReport[$key] = $value;
        }

        // Teams
        $qb = $this->conn->createQueryBuilder();
        $qb->addSelect([
            'project_game_team.id         AS  id',
            'project_game_team.slot       AS  slot',
            'project_game_team.teamName   AS  name',
            'project_game_team.groupSlot  AS  groupSlot',
            'project_game_team.report     AS  report',
        ]);
        $qb->from('game_teams', 'project_game_team');

        $qb->addOrderBy('slot', 'ASC');

        $qb->andWhere('project_game_team.gameId = :gameId');

        $qb->setParameter('gameId', $game['id']);

        $stmt = $qb->execute();
        while ($team = $stmt->fetch()) {
            $gameTeamReport = $this->projectFactory->createProjectGameTeamReport($team);

            $report = isset($team['report']) ? unserialize($team['report']) : [];
            foreach ($report as $key => $value) {
                $gameTeamReport[$key] = $value;
            }
            $gameReport['teamReports'][$team['slot']] = $gameTeamReport;
        }
        return $gameReport;
    }

    /* =====================================================================
     * Here is the real fun, update a game report
     * TODO: Only update when info has changed
     */
    public function update($gameReportOriginal, $gameReportPosted)
    {
        // Game Report, could be better
        $report = serialize([
            'notes'  => $gameReportPosted['notes'],
            'status' => $gameReportPosted['status'],
        ]);
        $this->conn->executeUpdate(
            'UPDATE games SET status = ?, report = ? WHERE id = ?',
            [
                $gameReportPosted['game']['status'],
                $report,
                $gameReportOriginal['game']['id']
            ]
        );
        // Team Reports
        foreach($gameReportPosted['teamReports'] as $report)
        {
            $teamId = $report['team']['id'];
            unset($report['team']);
            unset($report['type']);
            $report = serialize($report);

            $this->conn->executeUpdate(
                'UPDATE game_teams SET report = ? WHERE id = ?',
                [$report,$teamId]
            );
        }

    }
}