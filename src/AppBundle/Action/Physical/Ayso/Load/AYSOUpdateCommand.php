<?php

namespace AppBundle\Action\Physical\Ayso\Load;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;
use AppBundle\Action\Services\VolCerts;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AYSOUpdateCommand extends Command
{
    private $conn;

    private $volCerts;

    public function __construct(
        Connection $NG2019Conn,
        VolCerts $volCerts
    ) {
        parent::__construct();

        $this->conn = $NG2019Conn;
        $this->volCerts = $volCerts;

    }

    protected function configure()
    {
        $this
            ->setName('ayso:update')
            ->setDescription('Update AYSO Volunteer Registration Data');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Updating AYSO Volunteer Registration Data...");

        $sql = <<<EOD
SELECT * FROM volProjectPersons;
EOD;

        $stmt = $this->conn->executeQuery($sql);
        $ids = $row = $stmt->fetchAll();

        $fedKeys = null;
        foreach ($ids as $id) {
            $id = explode(":", $id["fedKey"])[1];
            $fedKeys['AYSOV:'.$id] = $this->volCerts->retrieveVolCertData($id);
        }

        var_dump($fedKeys);

        echo sprintf("completed.\n");
    }

}