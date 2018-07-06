<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class VerifyAllNOC2018Command extends Command
{
    private $projectId;

    private $Conn;

    public function __construct(
        $projectId,
        Connection $noc2018Conn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->Conn = $noc2018Conn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:verify:game:officials')
            ->setDescription('Verify Game Officials NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Verifying Game Officials NOC2018 ...\n");

        $projectPersons = null;

        $sql = 'SELECT * FROM projectPersons pp LEFT JOIN projectPersonRoles ppr ON ppr.projectPersonId = pp.id WHERE pp.projectKey LIKE  ?;';
        $stmt = $this->Conn->executeQuery($sql, [$this->projectId]);
        $projectPersonRoles = [];
        while ($row = $stmt->fetch()) {
            $projectPersonRoles[] = $row;
        }

        $projectPersons = [];
        foreach ($projectPersonRoles as $projectPersonRole) {
            $projectPersons[$projectPersonRole['projectPersonId']][$projectPersonRole['role']] = $projectPersonRole['verified'];
        }

        $newProjectPersons = [];
        foreach ($projectPersons as $id => $projectPerson) {
            $before[$id] = $projectPerson;
            $referee = isset($projectPerson['CERT_REFEREE']) ? $projectPerson['CERT_REFEREE'] == true : false;
            $safeHaven = isset($projectPerson['CERT_SAFE_HAVEN']) ? $projectPerson['CERT_SAFE_HAVEN'] == true : false;
            $concussion = isset($projectPerson['CERT_CONCUSSION']) ? $projectPerson['CERT_CONCUSSION'] == true : false;

            $projectPerson['ROLE_REFEREE'] = $referee && $safeHaven && $concussion;

            if (isset($projectPerson[$id]['ROLE_VOLUNTEER'])) {
                $projectPerson[$id]['ROLE_VOLUNTEER'] = $safeHaven && $concussion;
            }

            $newProjectPersons[$id] = $projectPerson;
        }

        foreach ($newProjectPersons as $projectPersonId => $certs) {
            if(isset($certs['ROLE_REFEREE'])) {
                $this->Conn->update(
                    'projectPersonRoles',
                    ['verified' => $certs['ROLE_REFEREE']],
                    [
                        'projectPersonId' => $projectPersonId,
                        'role' => 'ROLE_REFEREE'
                    ]
                );

                $this->Conn->update(
                    'projectPersons',
                    ['verified' => $certs['ROLE_REFEREE']],
                    [
                        'id' => $projectPersonId,
                        'registered' => 1
                    ]
                );
            }

            if(isset($certs['ROLE_VOLUNTEER'])) {
                $this->Conn->update(
                    'projectPersonRoles',
                    ['verified' => $certs['ROLE_VOLUNTEER']],
                    [
                        'projectPersonId' => $projectPersonId,
                        'role' => 'ROLE_VOLUNTEER'
                    ]
                );

                $this->Conn->update(
                    'projectPersons',
                    ['verified' => $certs['ROLE_VOLUNTEER']],
                    [
                        'id' => $projectPersonId,
                        'registered' => 1
                    ]
                );
            }


        }
    }
}
