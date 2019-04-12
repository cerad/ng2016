<?php declare(strict_types=1);

namespace Zayso\Fed\Ayso;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AysoOrgCommand extends Command
{
    const aysoidArt   = 'AYSO:99437977';
    const aysoidEthan = 'AYSO:51563588';

    const projectIdNG2019 = 'AYSONationalGames2019';

    protected static $defaultName = 'fed:ayso:org';

    private $finder;
    private $aysoConn;
    private $orgViewTransformer;

    public function __construct(AysoFinder $finder, AysoConnection $aysoConn, AysoOrgViewTransformer $orgViewTransformer)
    {
        parent::__construct();

        $this->finder        = $finder;
        $this->aysoConn      = $aysoConn;
        $this->orgViewTransformer = $orgViewTransformer;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        echo "AYSO Org Check\n";
        //$this->checkOne(' ');
        $this->checkAll();
    }
    private function checkAll() : void
    {
        $sql = 'SELECT DISTINCT FedOrgId AS orgId FROM FedPersons ORDER BY orgId';
        $rows = $this->aysoConn->executeQuery($sql)->fetchAll();
        foreach($rows as $row) {
            $this->checkOne($row['orgId']);
        }
        //dump($rows);
    }
    private function checkOne(string $orgId) : void
    {
        $orgView = $this->orgViewTransformer->transform($orgId);
        if ($orgView !== $orgId)
        {
            //echo sprintf("Org '%s' '%s'\n",$orgView,$orgId);
            return;
        }
        // Check for sar
        $orgParts = explode(':',$orgId);
        $sar = isset($orgParts[1]) ? $orgParts[1] : $orgId;
        $sql = 'SELECT orgKey,sar,state FROM orgs WHERE sar = ?';
        $row = $this->aysoConn->executeQuery($sql,[$sar])->fetch();
        if ($row === false) {
            echo sprintf("        '%s' => '%s',\n",$sar,'X ' . $sar);
            return;
        }

        $sarParts = explode('/',$sar);
        if (count($sarParts) != 3) {
            echo sprintf("        '%s' => '%s',\n",$sar,'X ' . $sar);
            return;
        }
        $orgView = sprintf("%02d/%s/%04d",(int)$sarParts[0],$sarParts[1],(int)$sarParts[2]);
        if ($row['state']) {
            $orgView .= '/' . $row['state'];
        }
        echo sprintf("        '%s' => '%s',\n",$sar,$orgView);
    }
}