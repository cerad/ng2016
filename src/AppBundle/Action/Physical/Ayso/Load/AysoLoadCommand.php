<?php
namespace AppBundle\Action\Physical\Ayso\Load;

use PHPExcel_IOFactory;
use PHPExcel_Reader_Abstract;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//  Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

class AysoLoadCommand extends Command
{
    /** @var  Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        parent::__construct();

        $this->conn = $conn;

        //$this->projectPersonRepository = new ProjectPersonRepository($ng2016Conn);
    }
    protected function configure()
    {
        $this
            ->setName('ayso:load')
            ->setDescription('Load AYSO Cert Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO Cert File');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sql = <<<EOD
INSERT INTO vols
(fedKey,name,email,phone,gender,sar,regYear)
VALUES (?,?,?,?,?,?,?)
EOD;
        $this->insertVolStmt = $this->conn->prepare($sql);

        $sql = 'SELECT regYear FROM vols WHERE fedKey = ?';
        $this->checkVolStmt = $this->conn->prepare($sql);

        $sql = 'INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate) VALUES (?,?,?,?,?)';
        $this->insertCertStmt = $this->conn->prepare($sql);

        $sql = 'SELECT roleDate,badge,badgeDate FROM certs WHERE fedKey = ? AND role = ?';
        $this->checkCertStmt = $this->conn->prepare($sql);

        $sql = 'UPDATE certs SET roleDate = ?, badge = ?, badgeDate = ? WHERE fedKey = ? AND role = ?';
        $this->updateCertStmt = $this->conn->prepare($sql);

        $sql = 'SELECT sar FROM orgs WHERE orgKey = ?';
        $this->checkOrgStmt = $this->conn->prepare($sql);

        $sql = 'INSERT INTO orgs (orgKey,sar) VALUES (?,?)';
        $this->insertOrgStmt = $this->conn->prepare($sql);

        // Mess with badge list
        $badgeSorts = [];
        foreach($this->certMetas as $certMeta) {
            $badgeSorts[$certMeta['badge']] = $certMeta['sort'];
        }
        $this->badgeSorts = $badgeSorts;

        // Start the processing
        $filename = $input->getArgument('filename');

        echo sprintf("Loading AYSO File: %s...\n",$filename);
        $this->load($filename);
        $this->processOrgs();
    }
    /** @var  Statement */
    private $insertVolStmt;

    /** @var  Statement */
    private $checkVolStmt;

    /** @var  Statement */
    private $insertCertStmt;

    /** @var  Statement */
    private $updateCertStmt;

    /** @var  Statement */
    private $checkCertStmt;

    /** @var  Statement */
    private $insertOrgStmt;

    /** @var  Statement */
    private $checkOrgStmt;

    private function load($filename)
    {
        /** @var PHPExcel_Reader_Abstract $reader */
        $reader = PHPExcel_IOFactory::createReaderForFile($filename);
        $reader->setReadDataOnly(true);

        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);

        $rowMax = $ws->getHighestRow();
        $colMax = $ws->getHighestColumn();

        for($row = 2; $row < $rowMax; $row++) {
            $range = sprintf('A%d:%s%d',$row,$colMax,$row);
            $data = $ws->rangeToArray($range,null,false,false,false)[0];
            $this->loadVol ($data);
            $this->loadCert($data);
            if (($row % 100) === 0) {
                echo sprintf("\rProcessed %4d of %d",$row,$rowMax - 1);
            }
        }
        echo sprintf("\rProcessed %4d rows      \n",$row - 1);
    }
    /* ====================================
        [ 0]=> string(6)  "AYSOID" float(59181932)
        [ 1]=> string(4)  "Name"
        [ 2]=> string(6)  "Street"
        [ 3]=> string(4)  "City"
        [ 4]=> string(5)  "State"
        [ 5]=> string(3)  "Zip"
        [ 6]=> string(9)  "HomePhone"
        [ 7]=> string(13) "BusinessPhone"
        [ 8]=> string(5)  "Email"
        [ 9]=> string(17) "CertificationDesc" badge Regional Referee
        [10]=> string(6)  "Gender"
        [11]=> string(17) "SectionAreaRegion"
        [12]=> string(8)  "CertDate"
        [13]=> string(10) "ReCertDate"
        [14]=> string(9)  "FirstName"
        [15]=> string(8)  "LastName"
        [16]=> string(11) "SectionName"
        [17]=> string(8)  "AreaName"
        [18]=> string(12) "RegionNumber"
        [19]=> string(15) "Membership Year"
     */
    private function loadVol($row)
    {
        $fedKey = 'AYSOV:' . (string)$row[0]; // "AYSOID";

        $regYear = $row[19]; // "Membership Year"

        $item = [
            $fedKey,
            $row[ 1],        // "Name"
            //$row[14],      // "FirstName"
            //$row[15],      // "LastName"
            $row[ 8],        // "Email"
            $row[ 6],        // "HomePhone"
            $row[10],        // "Gender M or F
            $row[11],        // "SectionAreaRegion"
            $row[19],        // "Membership Year"
        ];
        $this->checkVolStmt->execute([$fedKey]);
        $vol = $this->checkVolStmt->fetch();
        if (!$vol) {
            $this->insertVolStmt->execute($item);
            return;
        }
        if ($regYear <= $vol['regYear']) {
            return;
        }
        // TODO Update operation
        var_dump($item); die();
    }
    private $certMetas = [
        'Regional Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Regional',
            'sort'  => 10,
        ],
        'Intermediate Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Intermediate',
            'sort'  => 20,
        ],
        'Advanced Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Advanced',
            'sort'  => 30,
        ],
        'National Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National',
            'sort'  => 90,
        ],
        'National 1 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National 1',
            'sort'  => 80,
        ],
        'National 2 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National 2',
            'sort'  => 70,
        ],
    ];
    private $badgeSorts = [];

    private function loadCert($row)
    {
        $fedKey = 'AYSOV:' . (string)$row[0]; // "AYSOID";

        $certDesc = $row[9];                  //"CertificationDesc"
        $certMeta = isset($this->certMetas[$certDesc]) ? $this->certMetas[$certDesc] : null;
        if (!$certMeta) {
            die('Missing cert: ' . $certDesc);
        }

        $role  = $certMeta['role'];
        $badge = $certMeta['badge'];

        $badgeDate = $row[12];
        $badgeDate = $badgeDate ? \PHPExcel_Style_NumberFormat::toFormattedString($badgeDate, 'YYYY-MM-DD') : null;
        $roleDate  = $badgeDate;

        $this->checkCertStmt->execute([$fedKey,$role]);
        $certExisting = $this->checkCertStmt->fetch();
        if (!$certExisting) {
            $this->insertCertStmt->execute([$fedKey,$role,$roleDate,$badge,$badgeDate]);
            return;
        }
        $badgeExisting    = $certExisting['badge'];
        $roleDateExisting = $certExisting['roleDate'];

        if ($this->badgeSorts[$badge] <= $this->badgeSorts[$badgeExisting]) {
            if ($roleDate === null) {
                return;
            }
            if (($roleDateExisting === null) || ($roleDate < $roleDateExisting)) {

                // Update earlier role date
                $this->updateCertStmt->execute([$roleDate,$badgeExisting,$certExisting['badgeDate'],$fedKey,$role]);

                // die(sprintf('Update Role date %s %s > %s',$fedKey,$roleDate, $roleDateExisting));
                return;
            }
            return;
        }
        $roleDate = $roleDateExisting ? : $roleDate;
        $this->updateCertStmt->execute([$roleDate,$badge,$badgeDate,$fedKey,$role]);
        return;
        // die(sprintf('Update Badge %s > %s',$badge, $badgeExisting));
    }
    /* ==============================================================================
     * Quick and dirty mapping from sar to org key
     *
     */
    private function processOrgs()
    {
        $sql = 'SELECT DISTINCT sar FROM vols';
        $stmt = $this->conn->executeQuery($sql);
        while($row = $stmt->fetch()) {
            $sar = $row['sar'];
            $this->processSar($sar);
        }
    }
    private function processSar($sar)
    {
        if (!$sar) return;

        $sarParts = explode('/',$sar);
        if (count($sarParts) != 3) {
            die('sar error: ' . $sar);
        }
        $region = (int)$sarParts[2];
        if ($region < 1 || $region > 9999) {
            die('sar region number error: ' . $sar);
        }
        $orgKey = sprintf('AYSOR:%04d',$region);
        //die(sprintf('%s %s',$sar,$orgKey));
        $row = $this->checkOrgStmt->execute([$orgKey]);
        if ($row) {
            return;
        }
        $this->insertOrgStmt->execute([$orgKey,$sar]);
    }
    protected function clearDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE ' . $databaseName);
        $conn->exec('CREATE DATABASE ' . $databaseName);
        $conn->exec('USE '             . $databaseName);
    }
    protected function createDatabase(Connection $conn)
    {
        $cmd = sprintf("mysql -u%s -p%s %s < schema2016.sql",
            $conn->getUsername(),
            $conn->getPassword(),
            $conn->getDatabase()
        );
        exec($cmd);
    }
}