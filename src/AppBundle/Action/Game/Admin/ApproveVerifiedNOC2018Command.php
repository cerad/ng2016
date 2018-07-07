<?php
namespace AppBundle\Action\Game\Admin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class ApproveVerifiedNOC2018Command extends Command
{
    private $gameConn;

    public function __construct(
        Connection $noc2018Conn
    ) {
        parent::__construct();

        $this->gameConn    = $noc2018Conn;
    }

    protected function configure()
    {
        $this
            ->setName('noc2018:approve:verified:officials')
            ->setDescription('Approve Verified Game Officials NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Approving Verified Game Officials NOC2018 ...\n");

        $sql = 'SELECT * from projectPersonRoles WHERE approved =1';
        $result = $this->gameConn->executeQuery($sql);
        $countPre = count($result->fetchAll());

        $this->gameConn->update('projectPersonRoles',['approved' => '1', ],
            [
                'verified' => '1'
            ]);

        $sql = 'SELECT * from projectPersonRoles WHERE approved =1';
        $result = $this->gameConn->executeQuery($sql);
        $countPost = count($result->fetchAll());

        echo sprintf("%d records updated\n", $countPost-$countPre);

    }
}
