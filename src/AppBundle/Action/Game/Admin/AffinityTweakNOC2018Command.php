<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\DBAL\Connection;

class AffinityTweakNOC2018Command extends Command
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
            ->setName('noc2018:affinity:tweak')
            ->setDescription('Update Games and Pools NOC2018')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Updating NOC2018 games and pools ... ");

        $commit = $input->getOption('commit');

        echo sprintf("\n... moving game Officials ... ");
        $this->updateGameOfficials($commit);

        echo sprintf("\n... deleting old poolTeams ... ");
        $this->updatePoolTeams($commit);

        echo sprintf("\n... deleting old gameTeams ... ");
        $this->updateGameTeams($commit);

        echo sprintf("\n... deleting old games ... ");
        $this->updateGames($commit);

        echo sprintf("\n... updating regTeams ... ");
        $this->updateRegTeams($commit);

        if ($commit) {
            echo sprintf("\nUpdates complete.\n");
        } else {
            echo sprintf("\nSuccess.\nUse the %s option to commit your changes.\n\n", '-c');

        }
    }

    private $poolKeyMap = array(
        '478732 '=> '517333',
        '478735 '=> '517334',
        '283261 '=> '517335',
        '283262 '=> '',
        '283263 '=> '',
        '283264 '=> '',
        '283265 '=> '',
        '283266 '=> '',
        '478730 '=> '',
        '478731 '=> '',
        '478733 '=> '',
        '478734 '=> '',

    );
    private $poolKeysToDelete = array(
        'B16UCore',
    );

    private function updateGameOfficials($commit)
    {
        foreach ($this->poolKeyMap as $oldGameNumber => $newGameNumber) {
            if ($newGameNumber !== '') {
                $sql = <<<SQL
SELECT slot, phyPersonId, regPersonId, regPersonName, assignState
FROM gameOfficials
WHERE projectId = ? and gameNumber = ?;
SQL;
                $gameOfficials = $this->gameConn->executeQuery($sql, [$this->projectId, $oldGameNumber]);

                while ($official = $gameOfficials->fetch()) {
                    if ($commit) {
                        $this->gameConn->update(
                            'gameOfficials',
                            [
                                'phyPersonId' => $official['phyPersonId'],
                                'regPersonId' => $official['regPersonId'],
                                'regPersonName' => $official['regPersonName'],
                                'assignState' => $official['assignState'],
                            ],
                            ['gameNumber' => $newGameNumber, 'slot' => $official['slot']]
                        );

                        $this->gameConn->delete(
                            'gameOfficials',
                            ['gameNumber' => $oldGameNumber]
                        );
                    }
                }
            }
        }
    }

    private function updatePoolTeams($commit)
    {
        if ($commit) {
            foreach ($this->poolKeysToDelete as $poolKey) {
                $this->gameConn->delete(
                    'poolTeams',
                    ['poolKey' => $poolKey]
                );
            }
        }
    }

    private function updateGameTeams($commit)
    {
        foreach ($this->poolKeyMap as $oldGameNumber => $newGameNumber) {
            if ($commit) {
                $this->gameConn->delete(
                    'gameTeams',
                    ['gameNumber' => $oldGameNumber]
                );
            }
        }
    }

    private function updateGames($commit)
    {
        foreach ($this->poolKeyMap as $oldGameNumber => $newGameNumber) {
            if ($commit) {
                $this->gameConn->delete(
                    'games',
                    ['gameNumber' => $oldGameNumber]
                );
            }
        }
    }

    private function updateRegTeams($commit)
    {
        $sql = <<<SQL
DELETE 
FROM regTeams
WHERE 'teamKey' LIKE '?%'
SQL;
        foreach ($this->poolKeysToDelete as $poolKey) {
            if ($commit) {
                $this->gameConn->exec($sql, [$poolKey]);
            }
        }

    }
}
