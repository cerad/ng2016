<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class VerifyOfficialsNG2019Command extends Command
{
    private $projectId;

    private $Conn;

    public function __construct(
        $projectId,
        Connection $ng2019Conn
    ) {
        parent::__construct();

        $this->projectId = $projectId;
        $this->Conn = $ng2019Conn;
    }

    protected function configure()
    {
        $this
            ->setName('ng2019:officials:verify')
            ->setDescription('Verify Game Officials NG2019');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Verifying Game Officials NG2019 ...\n");

        $projectPersons = null;

        $sql = 'SELECT * FROM projectPersons pp LEFT JOIN projectPersonRoles ppr ON ppr.projectPersonId = pp.id WHERE pp.projectKey LIKE ? AND NOT ppr.projectPersonId IS NULL;';
        $stmt = $this->Conn->executeQuery($sql, [$this->projectId]);
        $projectPersonRoles = [];
        while ($row = $stmt->fetch()) {
            $projectPersonRoles[] = $row;
        }

        $projectPersons = [];
        foreach ($projectPersonRoles as $projectPersonRole) {
            $projectPersons[$projectPersonRole['projectPersonId']]['regYear'] = $projectPersonRole['regYear'];
            $projectPersons[$projectPersonRole['projectPersonId']]['currentMY'] = $projectPersonRole['regYear'] >= "MY2017";
            $projectPersons[$projectPersonRole['projectPersonId']][$projectPersonRole['role']] = $projectPersonRole['verified'];
        }

        $newProjectPersons = [];
        foreach ($projectPersons as $id => $projectPerson) {
            $referee = isset($projectPerson['CERT_REFEREE']) ? $projectPerson['CERT_REFEREE'] == true : false;
            $safeHaven = isset($projectPerson['CERT_SAFE_HAVEN']) ? $projectPerson['CERT_SAFE_HAVEN'] == true : false;
            $concussion = isset($projectPerson['CERT_CONCUSSION']) ? $projectPerson['CERT_CONCUSSION'] == true : false;
            $currentMY = $projectPerson['currentMY'];

            if (isset($projectPerson['ROLE_REFEREE'])) {
                $projectPerson['ROLE_REFEREE'] = $referee && $safeHaven && $concussion && $currentMY;
            }

            if (isset($projectPerson['ROLE_VOLUNTEER'])) {
                $projectPerson['ROLE_VOLUNTEER'] = $safeHaven && $currentMY;
            }

            $newProjectPersons[$id] = $projectPerson;
        }

        $updateCount = null;
        foreach ($newProjectPersons as $projectPersonId => $certs) {
            if (isset($certs['ROLE_REFEREE'])) {
                $updateCount += 1;
                $this->Conn->update(
                    'projectPersonRoles',
                    ['verified' => $certs['ROLE_REFEREE']],
                    [
                        'projectPersonId' => $projectPersonId,
                        'role' => 'ROLE_REFEREE',
                    ]
                );
            }

            if (isset($certs['ROLE_VOLUNTEER'])) {
                $updateCount += 1;
                $this->Conn->update(
                    'projectPersonRoles',
                    ['verified' => $certs['ROLE_VOLUNTEER']],
                    [
                        'projectPersonId' => $projectPersonId,
                        'role' => 'ROLE_VOLUNTEER',
                    ]
                );
            }

            $this->Conn->update(
                'projectPersons',
                ['verified' => $certs['currentMY']],
                [
                    'projectKey' => $this->projectId,
                    'id' => $projectPersonId,
                    'registered' => 1,
                ]
            );
        }

        echo sprintf("%d records updated\n", $updateCount);
    }
}
