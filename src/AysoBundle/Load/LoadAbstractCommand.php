<?php
namespace AysoBundle\Load;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

abstract class LoadAbstractCommand extends Command
{
    /** @var  Connection */
    protected $conn;

    /** @var  Statement */
    protected $insertVolStmt;

    /** @var  Statement */
    protected $updateVolStmt;

    /** @var  Statement */
    protected $checkVolStmt;

    /** @var  Statement */
    protected $insertCertStmt;

    /** @var  Statement */
    protected $updateCertStmt;

    /** @var  Statement */
    protected $checkCertStmt;

    /** @var  Statement */
    protected $insertOrgStmt;

    /** @var  Statement */
    protected $checkOrgStmt;

    /** @var  Statement */
    protected $updateOrgStmt;

    /** @var  Statement */
    protected $insertOrgStateStmt;

    /** @var  Statement */
    protected $checkOrgStateStmt;

    public function __construct(Connection $conn)
    {
        parent::__construct();

        $this->conn = $conn;

        $this->initStatements($conn);

        $this->initCerts();
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');

        echo sprintf("Loading AYSO File: %s...\n",$filename);
        
        $this->load($filename);
    }
    abstract protected function load($filename);
    
    protected function initStatements(Connection $conn)
    {
        $sql = <<<EOD
INSERT INTO vols
(fedKey,name,email,phone,gender,sar,regYear)
VALUES (?,?,?,?,?,?,?)
EOD;
        $this->insertVolStmt = $conn->prepare($sql);

        $sql = <<<EOD
UPDATE vols SET 
  name = ?, email = ?, phone = ?, gender = ?, sar = ?, regYear = ?
WHERE fedKey = ?
EOD;
        $this->updateVolStmt = $conn->prepare($sql);

        $sql = 'SELECT * FROM vols WHERE fedKey = ?';
        $this->checkVolStmt = $conn->prepare($sql);

        $sql = 'INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate) VALUES (?,?,?,?,?)';
        $this->insertCertStmt = $conn->prepare($sql);

        $sql = 'SELECT roleDate,badge,badgeDate FROM certs WHERE fedKey = ? AND role = ?';
        $this->checkCertStmt = $conn->prepare($sql);

        $sql = 'UPDATE certs SET roleDate = ?, badge = ?, badgeDate = ? WHERE fedKey = ? AND role = ?';
        $this->updateCertStmt = $conn->prepare($sql);

        $sql = 'SELECT state FROM orgs WHERE orgKey = ?';
        $this->checkOrgStmt = $conn->prepare($sql);

        $sql = 'INSERT INTO orgs (orgKey,sar,state) VALUES (?,?,?)';
        $this->insertOrgStmt = $conn->prepare($sql);

        $sql = 'UPDATE orgs SET state = ? WHERE orgKey = ?';
        $this->updateOrgStmt = $conn->prepare($sql);

        $sql = 'SELECT orgKey FROM orgStates WHERE orgKey = ? AND state = ?';
        $this->checkOrgStateStmt = $conn->prepare($sql);

        $sql = 'INSERT INTO orgStates (orgKey,state) VALUES (?,?)';
        $this->insertOrgStateStmt = $conn->prepare($sql);
    }
    protected function initCerts()
    {
        // Mess with badge list
        $badgeSorts = [];
        foreach($this->certMetas as $certMeta) {
            if ($certMeta['role']) {
                $badgeSorts[$certMeta['badge']] = $certMeta['sort'];
            }
        }
        $this->badgeSorts = $badgeSorts;
    }
    protected $certMetas = [
        'U-8 Official' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'U8',
            'sort'  =>  2,
        ],
        'Assistant Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Assistant',
            'sort'  =>  5,
        ],
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
            'badge' => 'National_1',
            'sort'  => 80,
        ],
        'National 2 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National_2',
            'sort'  => 70,
        ],
        'Z-Online AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Webinar-AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Z-Online Refugio Seguro de AYSO' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Safe Haven Referee' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'Z-Online Safe Haven Referee' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'Safe Haven Update' => [
            'role'  => null,
        ],
        'Webinar-Safe Haven Update' => [
            'role'  => null,
        ],
        'Z-Online CDC Concussion Awareness Training' => [
            'role'  => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort'  => 90,
        ],
        'CDC Online Concussion Awareness Training' => [
            'role'  => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort'  => 90,
        ],
    ];
    protected $badgeSorts = [];
}