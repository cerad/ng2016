<?php declare(strict_types=1);

namespace Zayso\Fed\Ayso;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zayso\Fed\FedPerson;
use Zayso\Reg\Person\RegPersonConnection;

class AysoFindCommand extends Command
{
    const aysoidArt   = 'AYSO:99437977';
    const aysoidEthan = 'AYSO:51563588';

    const projectIdNG2019 = 'AYSONationalGames2019';

    protected static $defaultName = 'fed:ayso:find';

    private $finder;
    private $aysoConn;
    private $regPersonConn;

    public function __construct(AysoFinder $finder, AysoConnection $aysoConn, RegPersonConnection $regPersonConn)
    {
        parent::__construct();

        $this->finder        = $finder;
        $this->aysoConn      = $aysoConn;
        $this->regPersonConn = $regPersonConn;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        echo "AYSO Find\n";
        $fedPerson = $this->finder->find(self::aysoidArt);
        dump($fedPerson);
    }
    private function loadRawCerts()
    {
        $sql = 'SELECT id,name,fedKey AS fedPersonId FROM projectPersons WHERE projectKey = ?';
        $rows = $this->regPersonConn->executeQuery($sql,[self::projectIdNG2019])->fetchAll();
        foreach($rows as $row) {
            $fedPersonId = $row['fedPersonId'];
            $this->loadRawCertForPerson($fedPersonId);
        }
    }
    private function loadRawCertForPerson($fedPersonId)
    {
        $data = $this->finder->findData($fedPersonId);
        if ($data === null) return;
        $details = $data['VolunteerCertificationDetails'];
        foreach($details as $group => $certDatas) {
            if (is_array($certDatas)) {
                foreach($certDatas as $certData) {
                    $this->loadRawCert($group,$certData['CertificationDesc']);
                }
            }
        }
    }
    private function loadRawCert(string $group, string $desc)
    {
        $sql = 'SELECT count(*) AS count FROM CertDesc WHERE `group` = ? AND `desc` = ?';
        $row = $this->aysoConn->executeQuery($sql,[$group,$desc])->fetch();
        if ($row['count'] !== 0) {
            return;
        }
        $this->aysoConn->insert('CertDesc',[
            '`group`' => $group,
            '`desc`'  => $desc,
        ]);
    }
    private function loadMany()
    {
        $sql = 'SELECT id,name,fedKey AS fedPersonId FROM projectPersons WHERE projectKey = ?';
        $rows = $this->regPersonConn->executeQuery($sql,[self::projectIdNG2019])->fetchAll();
        foreach($rows as $row) {
            $fedPersonId = $row['fedPersonId'];
            $fedPerson = $this->finder->find($fedPersonId);
            if ($fedPerson === null) {
                dump($row);
                ///echo sprintf("*** Invalid aysoid %s\n",$fedPersonId);
            }
            else {
                $this->processFedPerson($fedPerson);
            }
        }
        //dump($rows);
    }
    private function loadOne(string $fedPersonId) : void
    {
        $fedPerson = $this->finder->find($fedPersonId);
        if ($fedPerson === null) {
            return;
        }
        $this->processFedPerson($fedPerson);
    }
    private function processFedPerson(FedPerson $fedPerson)
    {
        $sql = 'SELECT count(*) AS count FROM FedPersons WHERE FedPersonId = ?';

        $fedPersonId = $fedPerson->fedPersonId;

        $row = $this->aysoConn->executeQuery($sql,[$fedPersonId])->fetch();
        if ($row['count'] !== 0) {
            return;
        }
        $this->aysoConn->insert('FedPersons',[
            'FedPersonId' => $fedPersonId,
            'FedId'       => $fedPerson->fedId,
            'FullName'    => $fedPerson->fullName,
            'AgeGroup'    => $fedPerson->ageGroup,
            'FedOrgId'    => $fedPerson->fedOrgId,
            'MemYear'     => $fedPerson->memYear,
        ]);
    }
}