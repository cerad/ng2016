<?php

namespace AppBundle\Action\Schedule;

use Doctrine\DBAL;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateSelfAssignCommand
 * @package AppBundle\Action\Schedule
 */
class UpdateSelfAssignCommand extends Command
{

    /** @var  Connection */
    protected $connNG2019Games;

    /** @var Statement */
    protected $updateGamesSelfAssignStmt;

    /** @var Statement */
    protected $selectGamesSelfAssignStmt;

    /** @var String */
    protected $projectKey;

    /** @var String */
    protected $appRegYear;

    /**
     * UpdateSelfAssignCommand constructor.
     * @param Connection $connNG2019Games
     * @param string $projectKey
     * @param array $project
     * @throws DBAL\DBALException
     */
    public function __construct(Connection $connNG2019Games, string $projectKey, array $project)
    {

        parent::__construct();

        $this->connNG2019Games = $connNG2019Games;

        $this->projectKey = $projectKey;

        $this->appRegYear = $project['info']['regYear'];

        $this->initStatements($connNG2019Games);

    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ng2019:games:selfAssign')
            ->setDescription('Set or clear self assigning')
            ->addArgument('date', InputArgument::REQUIRED, 'YYYY-MM-DD')
            ->addOption('assign', 'a', InputOption::VALUE_OPTIONAL, 'true or false', true)
            ->addOption('commit', 'c', InputOption::VALUE_OPTIONAL, 'Commit data', false);
    }

    /**
     * @param Connection $connNG2019Games
     * @throws DBAL\DBALException
     */
    protected function initStatements(Connection $connNG2019Games)
    {
        $sql = 'UPDATE games SET selfAssign = ? WHERE projectId = ? AND DATE(start) = ?';
        $this->updateGamesSelfAssignStmt = $connNG2019Games->prepare($sql);

        $sql = 'SELECT * FROM games WHERE selfAssign = ? AND projectId = ? AND DATE(start) = ?';
        $this->selectGamesSelfAssignStmt = $connNG2019Games->prepare($sql);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $strDates = $input->getArgument('date');
        $assign = $input->getOption('assign') == 'true' ? true : false;
        $commit = $input->getOption('commit') == 'true' ? true : false;

        $params = array($assign, $this->projectKey, $strDates);

        if ($commit) {
            $this->updateGamesSelfAssignStmt->execute($params);
            $g = $this->updateGamesSelfAssignStmt->fetchAll();
            echo $g." games set updated. \n";

        } else {
            $this->selectGamesSelfAssignStmt->execute($params);
            $g = $this->selectGamesSelfAssignStmt->fetchAll();

            echo $g->rowCount()." games selfAssign will be updated. \n";
        }

    }


}