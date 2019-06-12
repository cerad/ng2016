<?php

namespace AysoBundle\Load;

use AysoBundle\AysoFinder;
use AysoBundle\DataTransformer\RegionToSarTransformer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL;

abstract class LoadAbstractCommand extends Command
{
    /** @var  Connection */
    protected $connAyso;

    /** @var  Connection */
    protected $connNG2019;

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

    /** @var Statement */
    protected $checkProjectPersonsRegisteredStmt;

    /** @var Statement */
    protected $resetProjectPersonRolesRoleDatedStmt;

    /** @var Statement */
    protected $resetProjectPersonRolesBadgeDateStmt;

    /** @var Statement */
    protected $resetProjectPersonRolesVerifiedStmt;

    /** @var boolean */
    protected $delete;

    /** @var boolean */
    protected $commit;

    /** @var AysoFinder */
    protected $aysoFinder;

    /** @var RegionToSarTransformer */
    protected $regionToSarTransformer;

    /**
     * LoadAbstractCommand constructor.
     * @param Connection $connAyso
     * @param Connection $connNG2019
     * @throws DBAL\DBALException
     */
    public function __construct(Connection $connAyso, Connection $connNG2019)
    {
        parent::__construct();

        $this->connAyso = $connAyso;

        $this->connNG2019 = $connNG2019;

        $this->aysoFinder = new AysoFinder($connAyso);

        $this->regionToSarTransformer = new RegionToSarTransformer($this->aysoFinder);

        $this->initStatements($connNG2019);

        $this->initCerts();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');
        $this->delete = $input->getOption('delete');
        $this->commit = $input->getOption('commit');

        echo sprintf("Loading AYSO File: %s...\n", $filename);

        $this->load($filename);
    }

    /**
     * @param $filename
     * @return mixed
     */
    abstract protected function load($filename);

    /**
     * @param Connection $connNG2019
     * @throws DBAL\DBALException
     */
    protected function initStatements(Connection $connNG2019)
    {
        $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND fedKey = ?';
        $this->selectProjectPersonIDStmt = $connNG2019->prepare($sql);

        $sql = 'UPDATE projectPersons SET verified = 1, orgKey = ?, regYear = ?, registered = ? WHERE projectKey = ? AND fedKey = ?';
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

        $sql = "UPDATE projectPersons SET registered = 0 WHERE projectKey = ? AND (regYear = '' OR regYear < ?)";
        $this->checkProjectPersonsRegisteredStmt = $connNG2019->prepare($sql);

        $sql = 'UPDATE projectPersonRoles SET roleDate = null, badge = null, badgeDate = null, verified = 0 WHERE projectPersonId = ? AND role = ? AND approved = 0';
        $this->resetProjectPersonRolesVerifiedStmt = $connNG2019->prepare($sql);

    }

    /**
     *
     */
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