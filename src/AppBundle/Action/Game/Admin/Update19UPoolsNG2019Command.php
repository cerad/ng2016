<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\DBAL\Connection;

class Update19UPoolsNG2019Command extends Command
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
            ->setName('ng2019:update:19u')
            ->setDescription('Update 19U Game and Pools NG2019')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Updating NG2019 19U pools ... ");

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
        '492021 ' => '501047',
        '492022 ' => '501056',
        '492023 ' => '501050',
        '492024 ' => '501049',
        '492026 ' => '',
        '492025 ' => '',
        '478742 ' => '501281',
        '478736 ' => '501276',
        '478737 ' => '501290',
        '478743 ' => '',
        '478738 ' => '501285',
        '478744 ' => '',
        '478739 ' => '501287',
        '478745 ' => '501286',
        '478740 ' => '501277',
        '478746 ' => '501282',
        '478741 ' => '501289',
        '478747 ' => '',
    );
    private $poolKeysToDelete = array(
        'B19UCorePPB',
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
WHERE age = '19U' and teamNumber IN (11,12)
SQL;
        $this->gameConn->exec($sql);

    }
}
