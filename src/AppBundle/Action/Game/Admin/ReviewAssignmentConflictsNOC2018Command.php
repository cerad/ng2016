<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Doctrine\DBAL\Connection;

class ReviewAssignmentConflictsNOC2018Command extends Command
{
    private $projectId;

    private $gameConn;

    public function __construct(
        $projectId,
        Connection $noc2018GamesConn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn = $noc2018GamesConn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:review:assignment:conflicts')
            ->setDescription('Review Official Assignments for Time Conflicts NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Review Official Assignments for Time Conflicts ... ";

        $this->gameConn->exec('SET @pid := NULL');
        $this->gameConn->exec("SET @finish := '2018-01-01 00:00:00'");
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
    minutes_late
FROM (
SELECT 
    gameNumber,
    Role,
    name,
    assignState,
    fieldName,
    start,
    finish,
    IF(@lastFinish = '2018-01-01 00:00:00',@lastFinish:=finish,@lastFinish) as lastFinish,
    IF(@lastField = 0, fieldName, @lastField) as lastField,
    IF ((@pid=phyPersonId) AND (addtime(@lastFinish, @late) > start),time_format(timediff(addtime(@lastFinish, @late),start),'%H:%i'), '') AS 'minutes_late',
    @lastField := fieldName,
    @lastFinish := finish,
    @pid := phyPersonId
FROM
    (SELECT DISTINCT
        gameNumber,
            IF(slot = 1, 'Referee', 'AR') AS Role,
            name,
            assignState,
            fieldName,
            start,
            finish,
            phyPersonId
    FROM
        (SELECT 
        go.phyPersonId,
            go.gameOfficialId,
            g.gameNumber,
            slot,
            assignState,
            fieldName,
            start,
            finish
    FROM
        noc2018games.gameOfficials go
    LEFT JOIN noc2018games.games g ON go.gameId = g.gameId
    WHERE
        g.projectId LIKE '%2018') s
    LEFT JOIN noc2018.projectPersons pp ON s.phyPersonId = pp.personKey
    WHERE
        assignState <> 'Open'
    ORDER BY s.phyPersonId ASC , start , fieldName) d) f;
SQL;
        $keys = array(
            'gameNumber',
            'Role',
            'name',
            'assignState',
            'fieldName',
            'start',
            'finish',
            'conflict',
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