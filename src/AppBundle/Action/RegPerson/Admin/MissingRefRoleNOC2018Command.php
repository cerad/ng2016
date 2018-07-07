<?php

namespace AppBundle\Action\RegPerson\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;

use Doctrine\DBAL\Connection;

class MissingRefRoleNOC2018Command extends Command
{
    private $projectId;

    private $gameConn;

    public function __construct(
        $projectId,
        Connection $noc2018Conn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->gameConn = $noc2018Conn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:norefrole')
            ->setDescription('Find Referee without ROLE_REFEREE NOC2018')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update Roles to add ROLE_REFEREE');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updateRoles = $input->getOption('update');
        $command = $this->getApplication()->find('manage:roles');

        echo sprintf("Finding Referees without ROLE_REFEREE NOC2018 ... ");

        $sqlSelect = "SELECT cr.projectPersonId AS id, cr.name, cr.email, cr.role AS cert, rr.role as role FROM (
            SELECT DISTINCT
                ppr.projectPersonId, name, email, role
            FROM
                projectPersonRoles ppr
                    LEFT JOIN
                projectPersons pp ON pp.id = ppr.projectPersonId
            WHERE
                pp.projectKey LIKE ? AND role = 'CERT_REFEREE') cr LEFT JOIN
                
            (SELECT DISTINCT
                ppr.projectPersonId, name, role
            FROM
                projectPersonRoles ppr
                    LEFT JOIN
                projectPersons pp ON pp.id = ppr.projectPersonId
            WHERE
                pp.projectKey LIKE ? AND role = 'ROLE_REFEREE') rr 
                
            ON cr.projectPersonId = rr.projectPersonId
                
            WHERE NOT cr.name LIKE 'test_account%' AND rr.role IS NULL";

        $result = $this->gameConn->executeQuery($sqlSelect, [$this->projectId, $this->projectId]);

        $recordCount = 0;
        $action = 'found';
        while ($row = $result->fetch()) {
            $recordCount += 1;

            $arguments = array(
                'command' => 'manage:roles',
                'identifier' => $row['email'],
            );

            if ($updateRoles) {
                $arguments['role'] = 'ROLE_REFEREE';
            } else {
                $arguments['role'] = null;
            }

            echo "\n";
            $manageRoles = new ArrayInput($arguments);
            $command->run($manageRoles, $output);
        }
        $action = $updateRoles ? 'updated' : 'found';
        echo "$recordCount records $action.\n";

    }
}
