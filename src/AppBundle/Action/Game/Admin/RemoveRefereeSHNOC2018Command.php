<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class RemoveRefereeSHNOC2018Command extends Command
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
            ->setName('noc2018:remove:refereesafehaven')
            ->setDescription('Remove Referee Safe Haven NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Removing Referee Safe Haven for Officials NOC2018 ... ");

        $sqlSelect = "SELECT * FROM noc2018.projectPersonRoles WHERE role LIKE '%SAFE_HAVEN_REFEREE' AND 
            projectPersonId IN 
            (SELECT DISTINCT id FROM noc2018.projectPersons WHERE projectKey LIKE ?);";

        $result = $this->gameConn->executeQuery($sqlSelect, [$this->projectId]);

        $rshRecords = $result->fetchAll();
        $count = count($rshRecords);

        if ($count) {
            $sqlDelete = "DELETE FROM noc2018.projectPersonRoles WHERE role LIKE '%SAFE_HAVEN_REFEREE' AND 
            projectPersonId IN 
            (SELECT DISTINCT id FROM noc2018.projectPersons WHERE projectKey LIKE ?);";

            $this->gameConn->executeQuery($sqlDelete, [$this->projectId]);
        }

        echo sprintf("$count CERT_SAFE_HAVEN_REFEREE records removed.\n");

    }
}
