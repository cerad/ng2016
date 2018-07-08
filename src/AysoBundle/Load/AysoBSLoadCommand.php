<?php

namespace AysoBundle\Load;

use PHPExcel_Style_NumberFormat;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

use Exception;

class AysoBSLoadCommand extends Command
{
    /** @var  Connection */
    private $aysoConn;

    /** @var  Connection */
    private $nocConn;

    private $project;

    public function __construct(Connection $aysoConn, Connection $nocConn, $project)
    {
        parent::__construct();

        $this->aysoConn = $aysoConn;
        $this->nocConn = $nocConn;
        $this->project = $project['info'];

        //$this->projectPersonRepository = new ProjectPersonRepository($ng2016Conn);
    }

    protected function configure()
    {
        $this
            ->setName('ayso:load')
            ->setDescription('Load AYSO BS Cert Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO BS Cert File');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $sql = <<<EOD
//INSERT INTO vols
//(fedKey,name,email,phone,gender,sar,regYear)
//VALUES (?,?,?,?,?,?,?)
//EOD;
        $sql = <<<EOD
INSERT INTO vols
(fedKey,name,email,phone,sar,regYear)
VALUES (?,?,?,?,?,?)
EOD;
        $this->insertVolStmt = $this->aysoConn->prepare($sql);

//        $sql = <<<EOD
//UPDATE vols SET
//  name = ?, email = ?, phone = ?, gender = ?, sar = ?, regYear = ?
//WHERE fedKey = ?
//EOD;

        $sql = <<<EOD
UPDATE vols SET 
  name = ?, email = ?, phone = ?, sar = ?, regYear = ?
WHERE fedKey = ?
EOD;
        $this->updateVolStmt = $this->aysoConn->prepare($sql);

        $sql = 'SELECT regYear FROM vols WHERE fedKey = ?';
        $this->checkVolStmt = $this->aysoConn->prepare($sql);

        $sql = 'INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate,verified) VALUES (?,?,?,?,?,1)';
        $this->insertCertStmt = $this->aysoConn->prepare($sql);

        $sql = 'SELECT roleDate,badge,badgeDate FROM certs WHERE fedKey = ? AND role = ?';
        $this->checkCertStmt = $this->aysoConn->prepare($sql);

        $sql = 'UPDATE certs SET roleDate = ?, badge = ?, badgeDate = ?, verified=1 WHERE fedKey = ? AND role = ?';
        $this->updateCertStmt = $this->aysoConn->prepare($sql);

        $sql = 'SELECT sar FROM orgs WHERE orgKey = ?';
        $this->checkOrgStmt = $this->aysoConn->prepare($sql);

        $sql = 'INSERT INTO orgs (orgKey,sar) VALUES (?,?)';
        $this->insertOrgStmt = $this->aysoConn->prepare($sql);

        $sql = 'UPDATE projectPersons SET orgKey = ?, regYear = ?, name = ?, email = ?, phone = ?, verified=1 WHERE fedKey = ? AND projectKey
 = ?';
        $this->updateProjectPersonStmt = $this->nocConn->prepare($sql);

        // Mess with badge list
        $badgeSorts = [];
        foreach ($this->certMetas as $certMeta) {
            if ($certMeta['role']) {
                $badgeSorts[$certMeta['badge']] = $certMeta['sort'];
            }
        }
        $this->badgeSorts = $badgeSorts;

        // Start the processing
        $filename = $input->getArgument('filename');

        echo sprintf("Loading AYSO File: %s...\n", $filename);
        $this->load($filename);
        //$this->processOrgs();
    }

    /** @var  Statement */
    private $insertVolStmt;

    /** @var  Statement */
    private $updateVolStmt;

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

    /** @var  Statement */
    private $updateProjectPersonStmt;

    private function load($filename)
    {
        $file = file($filename, FILE_SKIP_EMPTY_LINES);
        $rowMax = count($file);

        //skip header and count the columns
        $file = fopen($filename, 'r');

        if ($file !== false) {
            $colMax = count(fgetcsv($file));
            $data = null;
            $loc = null;

            try {
                for ($row = 2; $row < $rowMax; $row++) {
                    $data = fgetcsv($file);
                    if ($data !== false) {
                        $this->loadVol($data);
                        $this->loadCert($data, trim($data[6]));
                        $this->refreshProjectPerson($data);
                        if (($row % 1000) === 0) {
                            echo sprintf(
                                "\nProcessed %4d of %d (%1.2f%%)",
                                $row,
                                $rowMax - 1,
                                $row / ($rowMax - 1) * 100
                            );
                        }
                    }
                }
                echo sprintf("\nProcessed %4d rows (100%%)\n", $row - 1);

            } catch (Exception $e) {
                $range = sprintf('A%d:%s%d', $row, $colMax, $row);
                echo "\n";
                echo 'Row Max: ', $rowMax, "\n";
                echo 'Column Max: ', $colMax, "\n";
                echo 'Row: ', $row, "\n";
                echo 'Range: ', $range, "\n";
                echo 'Data: ', "\n";
                var_dump($data); //
                echo "\n";

                echo 'Exception: ', $e->getMessage(), "\n";

            }

        }
    }

    /* ====================================
        [ 0]=> string(6)  "AYSO Volunteer ID" float(12345678) or  float(123456789)
        [ 1]=> string(4)  "Program AYSO Membership Year"
        [ 2]=> string(6)  "Volunteer First Name"
        [ 3]=> string(4)  "Volunteer Last Name"
        [ 4]=> string(5)  "Volunteer Cell"
        [ 5]=> string(3)  "Volunteer Email"
        [ 6]=> string(9)  "AYSO Certifications"
        [ 7]=> string(13) "Date of Last AYSO Certification Update"
        [ 8]=> string(5)  "Portal Name"
        [ 9]=> string(17) "SectionAreaRegion"
        [10]=> string(8)  "Section"
        [11]=> string(10) "Area"
        [12]=> string(9)  "Region"
        [13]=> string(8)  "Name"
     */
    private function loadVol($row)
    {
        $fedKey = 'AYSOV:'.(string)$row[0]; // "AYSOID";

        $regYear = $row[1]; // "Membership Year"
        if (substr($regYear, 0, 2) !== 'MY') {
            $regYear = 'MY'.(string)$regYear;
        }

        $item = [
            $fedKey,
            $row[13],      // "Name"
            $row[5],       // "Volunteer Email"
            $row[4],       // "Volunteer Cell"
//            null,           // "Gender M or F
            $row[9],       // "SectionAreaRegion"
            $regYear,       // "Program AYSO Membership Year"
        ];
        $this->checkVolStmt->execute([$fedKey]);
        $vol = $this->checkVolStmt->fetch();
        if (!$vol) {
//            echo 'insertVolStmt' . "\n";
            $this->insertVolStmt->execute($item);

            return;
        }

        if ($regYear < $vol['regYear']) {
//            echo '$regYear: '.   var_dump($regYear) . "\n";
//            echo '$vol[\'regYear\']: '. var_dump($vol['regYear']) . "\n";
//            echo '$regYear <= $vol[\'regYear\']' . var_dump($regYear <= $vol['regYear']) . "\n";
//            echo '-------------------'."\n";

            return;
        }
        $item = [
            $row[13],      // "Name"
            $row[5],       // "Volunteer Email"
            $row[4],       // "Volunteer Cell"
//            null,           // "Gender M or F
            $row[9],       // "SectionAreaRegion"
            $regYear,       // "Program AYSO Membership Year"
            $fedKey,
        ];
//        echo var_dump($item);
//        echo "\n";
        $this->updateVolStmt->execute($item);

        return;
    }

    private $certMetas = [
        'U-8 Official' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'U8',
            'sort' => 5,
        ],
        'Assistant Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'Assistant',
            'sort' => 7,
        ],
        'Regional Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'Regional',
            'sort' => 10,
        ],
        'Intermediate Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'Intermediate',
            'sort' => 20,
        ],
        'Advanced Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'Advanced',
            'sort' => 30,
        ],
        'National Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'National',
            'sort' => 90,
        ],
        'National 1 Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'National_1',
            'sort' => 80,
        ],
        'National 2 Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'National_2',
            'sort' => 70,
        ],
        'Z-Online AYSOs Safe Haven' => [
            'role' => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort' => 90,
        ],
        'AYSOs Safe Haven' => [
            'role' => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort' => 90,
        ],
        'Webinar-AYSOs Safe Haven' => [
            'role' => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort' => 90,
        ],
        'Z-Online Refugio Seguro de AYSO' => [
            'role' => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort' => 90,
        ],
        'Safe Haven Referee' => [
            'role' => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort' => 70,
        ],
        'Z-Online Safe Haven Referee' => [
            'role' => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort' => 70,
        ],
        'Safe Haven Update' => [
            'role' => null,
        ],
        'Webinar-Safe Haven Update' => [
            'role' => null,
        ],
        'Z-Online CDC Concussion Awareness Training' => [
            'role' => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort' => 90,
        ],
        'CDC Online Concussion Awareness Training' => [
            'role' => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort' => 90,
        ],
    ];
    private $badgeSorts = [];

//    private function loadCerts($row)
//    {
////        $regYear = $row[1]; // "Membership Year"
////        if (substr($regYear, 0, 2) !== 'MY') {
////            return;
////        }
//
//        $certDescs = explode(',', $row[6]); //"CertificationDesc"
//        $certDescs = str_replace(' & ', ',', $certDescs);
//
//        foreach ($certDescs as $certDesc) {
//            $this->loadCert($row, trim($certDesc));
//        }
//
//    }

    private function loadCert($row, $certDesc)
    {
        if (!$row) {
            return;
        }

        if (!$certDesc) {
            return;
        }

        $fedKey = 'AYSOV:'.(string)$row[0]; // "AYSOID";

        $certMeta = isset($this->certMetas[$certDesc]) ? $this->certMetas[$certDesc] : null;
        if (!$certMeta) {
//            var_dump($row);
//            die('Missing cert: '.$fedKey.' '.$certDesc."\n");

            return;
        }
        $role = $certMeta['role'];
        if (!$role) {
            return;
        }

        $badge = $certMeta['badge'];

        $badgeDate = $row[7];
        $badgeDate = $badgeDate ? PHPExcel_Style_NumberFormat::toFormattedString($badgeDate, 'YYYY-MM-DD') : null;

        $roleDate = $badgeDate;

        $this->checkCertStmt->execute([$fedKey, $role]);
        $certExisting = $this->checkCertStmt->fetch();
        if (!$certExisting) {
            $this->insertCertStmt->execute([$fedKey, $role, $roleDate, $badge, $badgeDate]);

            return;
        }
        $badgeExisting = $certExisting['badge'];
        $roleDateExisting = $certExisting['roleDate'];

        if ($this->badgeSorts[$badge] <= $this->badgeSorts[$badgeExisting]) {
            if ($roleDate === null) {
                return;
            }

            if (($roleDateExisting === null) || ($roleDate < $roleDateExisting)) {

                // Update earlier role date
                $this->updateCertStmt->execute([$roleDate, $badgeExisting, $certExisting['badgeDate'], $fedKey, $role]);

//                echo sprintf("\n".'Update Role date %s %s > %s', $fedKey, $roleDate, $roleDateExisting);

                return;
            }

            return;
        }
        $roleDate = $roleDateExisting ?: $roleDate;
        $this->updateCertStmt->execute([$roleDate, $badge, $badgeDate, $fedKey, $role]);

        return;
        // die(sprintf('Update Badge %s > %s',$badge, $badgeExisting));
    }

    /**
     * @param $row
     * @throws Exception
     */
    private function refreshProjectPerson($row)
    {
        if (!$row) {
            return;
        }
        $orgKey = $this->getOrgKey($row[9]);

        $fedKey = 'AYSOV:'.(string)$row[0]; // "AYSOID";

        $regYear = $row[1]; // "Membership Year"
        if (substr($regYear, 0, 2) !== 'MY') {
            $regYear = 'MY'.(string)$regYear;
        }

        $projectKey = $this->project['key'];

        $item = [
            $orgKey,        // "AYSOV:".Region
            $regYear,       // "Program AYSO Membership Year"
            $row[13],      // "Name"
            $row[5],       // "Volunteer Email"
            $row[4],       // "Volunteer Cell"
            $fedKey,
            $projectKey,
        ];

        $this->updateProjectPersonStmt->execute($item);

        return;

    }

    /* ==============================================================================
     * Quick and dirty mapping from sar to org key
     *
     */
//    private function processOrgs()
//    {
//        $sql = 'SELECT DISTINCT sar FROM vols';
//        $stmt = $this->aysoConn->executeQuery($sql);
//        while ($row = $stmt->fetch()) {
//            $sar = $row['SectionAreaRegion'];
//            $this->processSar($sar);
//        }
//    }

//    private function processSar($sar)
//    {
//
//        $orgKey = $this->getOrgKey($sar);
//
//        $this->checkOrgStmt->execute([$orgKey]);
//        if ($this->checkOrgStmt->fetch()) {
//            return;
//        }
//        $this->insertOrgStmt->execute([$orgKey, $sar]);
//    }

    private function getOrgKey($sar)
    {
        if (!$sar) {
            return null;
        }
        $sarParts = explode('/', $sar);
        if (count($sarParts) != 3) {
//            die('sar error (no Region found in SAR): '.$sar."\n");
            return null;
        }
        $region = (int)$sarParts[2];
        if ($region < 1 || $region > 9999) {
            die('sar region number error: '.$sar."\n");
        }
        $orgKey = sprintf('AYSOR:%04d', $region);

        return $orgKey;
    }

    protected function clearDatabase(Connection $conn)
    {
        $databaseName = $conn->getDatabase();
        $conn->exec('DROP   DATABASE '.$databaseName);
        $conn->exec('CREATE DATABASE '.$databaseName);
        $conn->exec('USE '.$databaseName);
    }

    protected function createDatabase(Connection $conn)
    {
        $cmd = sprintf(
            "mysql -u%s -p%s %s < schema2017.sql",
            $conn->getUsername(),
            $conn->getPassword(),
            $conn->getDatabase()
        );
        exec($cmd);
    }
}