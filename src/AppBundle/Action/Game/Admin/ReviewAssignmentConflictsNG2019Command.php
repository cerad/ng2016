<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class ReviewAssignmentConflictsNG2019Command extends Command
{
    private $projectId;

    private $gameConn;

    public function __construct(
        $projectId,
        Connection $ng2019GamesConn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn = $ng2019GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('ng2019:assignments:review:conflicts')
            ->setDescription('Review Official Assignments for Time Conflicts NG2019');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Review Official Assignments for Time Conflicts ... ";

        $this->gameConn->exec('SET @pid := NULL');
        $this->gameConn->exec("SET @lastFinish := '2019-01-01 00:00:00'");
        $this->gameConn->exec("SET @lastField := 0");
        $this->gameConn->exec("SET @late := '00:15:00'");

        $sql = <<<SQL
SELECT 
    gameNumber,
    Role,
    name,
    assignState,
    fieldName,
    start,
    finish,
    lastFinish,
    lastField,
    minutes_late
FROM
    (SELECT 
        projectId,
            gameNumber,
            Role,
            name,
            assignState,
            fieldName,
            start,
            finish,
            IF(@lastFinish = '2019-01-01 00:00:00', @lastFinish:=finish, @lastFinish) AS lastFinish,
            IF(@lastField = 0, fieldName, @lastField) AS lastField,
            IF((@pid = phyPersonId)
                AND (ADDTIME(@lastFinish, @late) > start), TIME_FORMAT(TIMEDIFF(ADDTIME(@lastFinish, @late), start), '%H:%i'), '') AS 'minutes_late',
            @lastField:=fieldName,
            @lastFinish:=finish,
            @pid:=phyPersonId
    FROM
        (SELECT DISTINCT
        gameNumber,
            projectId,
            IF(slot = 1, 'Referee', 'AR') AS Role,
            regPersonName AS name,
            assignState,
            fieldName,
            start,
            finish,
            phyPersonId
    FROM
        (SELECT 
        go.phyPersonId,
            g.projectId,
            regPersonName,
            go.gameOfficialId,
            g.gameNumber,
            slot,
            assignState,
            fieldName,
            start,
            finish
    FROM
        gameOfficials go
    LEFT JOIN games g ON go.gameId = g.gameId) s
    WHERE
        assignState <> 'Open'
    ORDER BY s.phyPersonId ASC , start , fieldName) d) f
WHERE
    projectId LIKE ?
SQL;
        $keys = array(
            'gameNumber',
            'Role',
            'name',
            'assignState',
            'fieldName',
            'start',
            'finish',
            'lastFinish',
            'lastField',
            'minutes_late',
        );
        $stmt = $this->gameConn->executeQuery($sql, [$this->projectId]);

        $conflicts = [];
        while ($row = $stmt->fetch()) {
            if (empty($conflicts)) {
                $conflicts[] = $keys;
            } else {
                $conflicts[] = $row;
            }
        }

        $file = 'var/affinity_data/assignment_conflicts.'.date('Ymd_His').'.prn';
        $handle = fopen($file, 'w') or die('Cannot open file:  '.$file);

        foreach ($conflicts as $row) {
            foreach ($row as $field) {
                fwrite($handle, $field."\t");
            }

            fwrite($handle, "\n");
        }
        fclose($handle);

        $path = realpath($file);
        echo sprintf("done.  Saved to: $path\n");
    }
}
