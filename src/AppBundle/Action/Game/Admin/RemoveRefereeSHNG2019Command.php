<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class RemoveRefereeSHNG2019Command extends Command
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
            ->setName('ng2019:remove:refereesafehaven')
            ->setDescription('Remove Referee Safe Haven NG2019');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Removing Referee Safe Haven for Officials NG2019 ... ");

        $sqlSelect = "SELECT * FROM ng2019.projectPersonRoles WHERE role LIKE '%SAFE_HAVEN_REFEREE' AND 
            projectPersonId IN 
            (SELECT DISTINCT id FROM ng2019.projectPersons WHERE projectKey LIKE ?);";

        $result = $this->gameConn->executeQuery($sqlSelect, [$this->projectId]);

        $rshRecords = $result->fetchAll();
        $count = count($rshRecords);

        if ($count) {
            $sqlDelete = "DELETE FROM ng2019.projectPersonRoles WHERE role LIKE '%SAFE_HAVEN_REFEREE' AND 
            projectPersonId IN 
            (SELECT DISTINCT id FROM ng2019.projectPersons WHERE projectKey LIKE ?);";

            $this->gameConn->executeQuery($sqlDelete, [$this->projectId]);
        }

        echo sprintf("$count CERT_SAFE_HAVEN_REFEREE records removed.\n");

    }
}
