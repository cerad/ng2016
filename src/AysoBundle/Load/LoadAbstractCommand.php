<?php

namespace AysoBundle\Load;

use Cerad\Bundle\AysoBundle\AysoFinder;
use Cerad\Bundle\AysoBundle\DataTransformer\RegionToSarTransformer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

abstract class LoadAbstractCommand extends Command
{
    /** @var  Connection */
    protected $connAyso;

    /** @var  Connection */
    protected $connNG2019;

    /** @var Statement */
    protected $checkVolsStmt;

    /** @var  Statement */
    protected $insertVolsStmt;

    /** @var  Statement */
    protected $updateVolStmt;

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

    /** @var Statement */
    protected $selectProjectPersonIDStmt;

    /** @var Statement */
    protected $checkProjectPersonRolesStmt;

    /** @var Statement */
    protected $updateProjectPersonRoleStmt;

    /** @var Statement */
    protected $insertProjectPersonRoleStmt;

    /** @var Statement */
    protected $updateProjectPersonsStmt;

    /** @var Statement */
    protected $clearPPRVerified;

    /** @var Statement */
    protected $clearPPROldSH;

    /** @var boolean */
    protected $delete;

    /** @var boolean */
    protected $commit;

    /** @var AysoFinder */
    protected $aysoFinder;

    /** @var RegionToSarTransformer */
    protected $regionToSarTransformer;



    public function __construct(Connection $connAyso, Connection $connNG2019)
    {
        parent::__construct();

        $this->connAyso = $connAyso;

        $this->connNG2019 = $connNG2019;

        $this->aysoFinder = new AysoFinder($connAyso);

        $this->regionToSarTransformer = new RegionToSarTransformer($this->aysoFinder);

        $this->initStatements($connAyso, $connNG2019);

        $this->initCerts();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');
        $this->delete = $input->getOption('delete');
        $this->commit = $input->getOption('commit');

        echo sprintf("Loading AYSO File: %s...\n", $filename);

        $this->load($filename);
    }

    abstract protected function load($filename);

    protected function initStatements(Connection $connAyso, Connection $connNG2019)
    {
        $sql = <<<EOD
INSERT INTO vols
(fedKey,name,email,phone,gender,sar,regYear)
VALUES (?,?,?,?,?,?,?)
EOD;
        $this->insertVolsStmt = $connAyso->prepare($sql);

        $sql = <<<EOD
UPDATE vols SET 
  name = ?, email = ?, phone = ?, gender = ?, sar = ?, regYear = ?
WHERE fedKey = ?
EOD;
        $this->updateVolStmt = $connAyso->prepare($sql);

        $sql = 'SELECT * FROM vols WHERE fedKey = ?';
        $this->checkVolsStmt = $connAyso->prepare($sql);

//        $sql = 'INSERT INTO vols (fedKey,name,phone,email,sar,regYear) VALUES (?,?,?,?,?,?)';
//        $this->insertVolsStmt = $connAyso->prepare($sql);
//
//        $sql = 'UPDATE vols SET sar = ?, regYear = ? WHERE fedKey = ?';
//        $this->updateVolsStmt = $connAyso->prepare($sql);
//
        $sql = 'SELECT roleDate,badge,badgeDate FROM certs WHERE fedKey = ? AND role = ?';
        $this->checkCertStmt = $connAyso->prepare($sql);

        $sql = 'INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate) VALUES (?,?,?,?,?)';
        $this->insertCertStmt = $connAyso->prepare($sql);

        $sql = 'UPDATE certs SET roleDate = ?, badge = ?, badgeDate = ? WHERE fedKey = ? AND role = ?';
        $this->updateCertStmt = $connAyso->prepare($sql);

        $sql = 'SELECT state FROM orgs WHERE orgKey = ?';
        $this->checkOrgStmt = $connAyso->prepare($sql);

        $sql = 'INSERT INTO orgs (orgKey,sar,state) VALUES (?,?,?)';
        $this->insertOrgStmt = $connAyso->prepare($sql);

        $sql = 'UPDATE orgs SET state = ? WHERE orgKey = ?';
        $this->updateOrgStmt = $connAyso->prepare($sql);

        $sql = 'SELECT orgKey FROM orgStates WHERE orgKey = ? AND state = ?';
        $this->checkOrgStateStmt = $connAyso->prepare($sql);

        $sql = 'INSERT INTO orgStates (orgKey,state) VALUES (?,?)';
        $this->insertOrgStateStmt = $connAyso->prepare($sql);

        $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND fedKey = ?';
        $this->selectProjectPersonIDStmt = $connNG2019->prepare($sql);

        $sql = 'UPDATE projectPersons SET verified = 1, orgKey = ?, regYear = ?, registered = ? WHERE fedKey = ?';
        $this->updateProjectPersonsStmt = $connNG2019->prepare($sql);

        $sql = 'SELECT role, roleDate, badge, badgeDate FROM projectPersonRoles WHERE projectPersonId = ? AND role = ?';
        $this->checkProjectPersonRolesStmt = $connNG2019->prepare($sql);

        $sql = 'INSERT INTO projectPersonRoles (projectPersonId, role, roleDate, badge, badgeDate, verified) VALUES(?,?,?,?,?,1)';
        $this->insertProjectPersonRoleStmt = $connNG2019->prepare($sql);

        $sql = 'UPDATE projectPersonRoles SET roleDate = ?, badge = ?, badgeDate = ?, verified = 1 WHERE projectPersonId = ? AND role = ?';
        $this->updateProjectPersonRoleStmt = $connNG2019->prepare($sql);

        $sql = 'UPDATE projectPersonRoles SET verified = 0 WHERE verified = 1';
        $this->clearPPRVerified = $connNG2019->prepare($sql);

        $sql = "DELETE FROM projectPersonRoles WHERE role LIKE 'CERT_SAFE_HAVEN_%'";
        $this->clearPPROldSH = $connNG2019->prepare($sql);
    }

    protected function initCerts()
    {
        // Mess with badge list
        $badgeSorts = [];
        foreach ($this->certMetas as $certMeta) {
            if ($certMeta['role']) {
                $badgeSorts[$certMeta['badge']] = $certMeta['sort'];
            }
        }
        $this->badgeSorts = $badgeSorts;
    }

    protected $certMetas = [
        'None' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'None',
            'sort' => -1,
        ],
        'U-8 Official' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'U8',
            'sort' => 2,
        ],
        'Assistant Referee' => [
            'role' => 'CERT_REFEREE',
            'badge' => 'Assistant',
            'sort' => 5,
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
    protected $badgeSorts = [];
}