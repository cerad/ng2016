<?php

namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class ApproveVerifiedNG2019Command extends Command
{
    /** @var Connection */
    private $ngConn;

    /** @var string */
    private $projectId;

    /** @var boolean */
    protected $approve;

    public function __construct(
        Connection $ng2019Conn,
        string $projectId
    ) {
        parent::__construct();

        $this->ngConn = $ng2019Conn;
        $this->projectId = $projectId;
    }

    protected function configure()
    {
        $this
            ->setName('ng2019:officials:approve:verified')
            ->setDescription("Approve Verified Game Officials for NG2019")
            ->addOption('unapprove', 'u', InputOption::VALUE_NONE, 'Unapprove');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approving Verified Game Officials NG2019 ...\n");

        $this->approve = !$input->getOption('unapprove');

        $sql = "SELECT * from projectPersonRoles WHERE approved = 1 AND role LIKE 'ROLE_%'";
        $result = $this->ngConn->executeQuery($sql);
        $countPre = count($result->fetchAll());

        //get list of any unregistered volunteers
        $sql = "SELECT id FROM projectPersons WHERE projectKey = ? AND registered = 1";
        $stmt = $this->ngConn->prepare($sql);
        $stmt->execute([$this->projectId]);

        $ppids = [];
        while ($row = $stmt->fetch()) {
            array_push($ppids, $row['id']);
        };
        $ppidStr = implode(',', $ppids);

        if (!empty($ppids)) {
            $sql = "UPDATE projectPersonRoles SET approved = ? WHERE verified = 1 AND role LIKE 'ROLE_%' AND NOT badgeDate IS NULL and badgeDate <> '0000-00-00' AND projectPersonId IN ($ppidStr)";
            $stmt = $this->ngConn->prepare($sql);
            $stmt->execute([$this->approve]);
        }

        $sql = "SELECT * from projectPersonRoles WHERE approved = 1 AND role LIKE 'ROLE_%'";
        $result = $this->ngConn->executeQuery($sql);
        $countPost = count($result->fetchAll());

        echo sprintf("%d records updated\n", abs($countPost - $countPre));

    }
}
