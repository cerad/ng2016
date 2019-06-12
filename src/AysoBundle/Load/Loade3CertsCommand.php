<?php

namespace AysoBundle\Load;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL;
use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use DateTime;

class Loade3CertsCommand extends LoadAbstractCommand
{

    /** @var String */
    protected $projectKey;

    /** @var String */
    protected $appRegYear;

    public function __construct(Connection $connAyso, Connection $connNG2019, string $projectKey, array $project)
    {
        parent::__construct($connAyso, $connNG2019);

        $this->projectKey = $projectKey;

        $this->appRegYear = $project['info']['regYear'];
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ng2019:load:e3certs')
            ->setDescription('Load AYSO e3 Cert Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO e3 Cert File')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete existing data before update')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

    /**
     * @throws DBAL\DBALException
     */
    protected function resetValues()
    {
        if (!$this->delete) {
            return;
        }

        echo "  Resetting person roles verified ... ";
        $this->clearPPRVerified->execute();
        echo "done\n";

        echo "  Removing old Safe Haven certs ... ";
        $this->clearPPROldSH->execute();
        echo "done\n";

    }

    /**
     * @param $filename
     * @throws DBAL\DBALException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    protected function load($filename)
    {
        /* ====================================
        /*  vc.ayso1ref.com volunteer cert Export Fields // expected values

            Row 1: header
                N/A
            Row 2: header
                [ 0]=> "AYSOID"
                [ 1]=> "Full Name"
                [ 2]=> "Type"
                [ 3]=> "MY"
                [ 4]=> "SAR"
                [ 5]=> "Safe Haven Date"
                [ 6]=> "CDC Date"
                [ 7]=> "Ref Cert Desc"
                [ 8]=> "Ref Cert Date"
            /** 9 - 15 not used
                [ 9]=> "Assessor Cert Desc"
                [ 10]=> "Assessor Cert Date"
                [ 11]=> "Inst Cert Desc"
                [ 12]=> "Inst Cert Date"
                [ 13]=> "Inst Eval Cert Desc"
                [ 14]=> "Inst Eval Cert Date"
                [ 15]=> "Data Source"
            **/

        echo sprintf("  Loading e3 Data file: %s...\n", $filename);

        $this->resetValues();

        /** @var Xlsx $reader */
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $wb = $reader->load($filename);
        $ws = $wb->getSheet(0);

        $rowMax = $ws->getHighestRow();
        $colMax = $ws->getHighestColumn();
        $data = null;
        try {
            for ($row = 3; $row <= $rowMax; $row++) {  //skip headers in e3Data file
                $range = sprintf('A%d:%s%d', $row, $colMax, $row);
                $data = $ws->rangeToArray($range, null, false, false, false)[0];

                if (!empty((trim($data[0])))) {
                    $this->processCert($data);

                    if (($row % 100) === 0) {
                        echo sprintf("\r  Processed %4d of %d rows", $row, $rowMax - 1);
                    }
                }

            }

            echo sprintf("\r  %4d rows processed        \n", $row - 1);

        } catch (Exception $e) {
            echo 'Exception: ', $e->getMessage(), "\n";
            $range = sprintf('A%d:%s%d', $row, $colMax, $row);
            echo 'Row Max: ', $rowMax, "\n";
            echo 'Column Max: ', $colMax, "\n";
            echo 'Row: ', $row, "\n";
            echo 'Range: ', $range, "\n";
            echo 'Data: ', "\n";
            dump($data); //
            echo "\n";
        }

    }

    /**
     * @param array $row
     * @throws DBAL\DBALException
     */
    protected function processCert(array $row = [])
    {
        if (empty($row) || empty($row[0]) || $row[0] == 'AYSOID' || $row[1] == '*** Volunteer not found ***') {
            return;
        }

        $fedKey = strpos($row[0], 'AYSOV:') ? $row[0] : 'AYSOV:'.$row[0];

        //$type = $row[2]; // Adult or Youth
        $regYear = $row[3];
        $sar = $row[4];
        $roleDate = null;

        $dt = DateTime::createFromFormat('Y-m-d', $row[5]);
        $roleSH = 'CERT_SAFE_HAVEN';
        $badgeSHDate = $dt !== false ? $dt->format('Y-m-d') : '';
        if (!empty($badgeSHDate)) {
            $badgeSH = 'AYSO';
            $roleDate = $badgeSHDate;
        } else {
            $badgeSH = null;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $row[6]);
        $roleCDC = 'CERT_CONCUSSION';
        $badgeCDCDate = $dt !== false ? $dt->format('Y-m-d') : '';
        if (!empty($badgeCDCDate)) {
            $badgeCDC = 'CDC Concussion';
            $roleDate = $badgeCDCDate;
        } else {
            $badgeCDC = null;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $row[8]);
        $badgeDate = $dt !== false ? $dt->format('Y-m-d') : explode('/', $row[8])[0];
        $role = "CERT_REFEREE";
        if (empty($badgeDate)) {
            $badge = 'None';
        } else {
            $badge = trim(explode('/', $row[7])[0]);

            switch ($badge) {
                case 'AYSO':
                case 'CDC Concussion':
                    break;
                case 'Z-Online Regional Referee Course':
                case 'Intermediate Referee Course':
                case 'Advanced Referee Course':
                case 'National Referee Course':
                    $badge = '';
                    break;
                case 'Regional Referee':
                case 'Regional Referee & Safe Haven Referee':
                case 'Regional Referee & Safe Haven Referee / Intermediate Referee Course':
                case 'Regional Referee / Advanced Referee Course':
                case 'Regional Referee / Intermediate Referee Course':
                    $badge = 'Regional Referee';
                    break;
                case 'Intermediate Referee':
                case 'Intermediate Referee / Advanced Referee Course':
                case 'Intermediate Referee / National Referee Course':
                    $badge = 'Intermediate Referee';
                    break;
                case 'Advanced Referee':
                case 'Advanced Referee / National Referee Course':
                    $badge = 'Advanced Referee';
                    break;
                case 'National 1 Referee':
                case 'National 2 Referee':
                case 'National Referee':
                    $badge = 'National Referee';
                    break;
                case '':
                default:
                    $badge = 'None';
            }
            $roleDate = $badgeDate;
        }

        $badge = isset($this->certMetas[$badge]) ? $this->certMetas[$badge]['badge'] : null;

        // Update AYSO.Certs & NG2019.ProjectPersonRoles tables
        if ($this->commit) {
            $this->updateCert($fedKey, $sar, $regYear, $role, $roleDate, $badge, $badgeDate);

            $this->updateCert($fedKey, $sar, $regYear, $roleSH, $roleDate, $badgeSH, $badgeSHDate);

            $this->updateCert($fedKey, $sar, $regYear, $roleCDC, $roleDate, $badgeCDC, $badgeCDCDate);
        }

        return;
    }

    /**
     * @param $fedKey
     * @param $sar
     * @param $regYear
     * @param $role
     * @param $roleDate
     * @param $badge
     * @param $badgeDate
     * @throws DBAL\DBALException
     */
    private function updateCert($fedKey, $sar, $regYear, $role, $roleDate, $badge, $badgeDate)
    {
        echo sprintf("\r  Updating %s...                                  ", $fedKey);

        if ($this->commit) {
            $this->refreshProjectPersonsAndRole($fedKey, $sar, $regYear, $role, $roleDate, $badge, $badgeDate);
        }

    }

    /**
     * @param $fedKey
     * @param $sar
     * @param $regYear
     * @param $role
     * @param $roleDate
     * @param $badge
     * @param $badgeDate
     * @throws DBAL\DBALException
     */
    private function refreshProjectPersonsAndRole($fedKey, $sar, $regYear, $role, $roleDate, $badge, $badgeDate)
    {
        if (!$this->commit || empty($role)) {
            return;
        }

        $registered = $regYear >= $this->appRegYear ? 1 : 0;
        switch (count(explode('/', $sar))) {
            case 1:
            case 2:
                $sar = "e3:".$sar;
                break;
            case 3:
                $sar = $this->regionToSarTransformer->reverseTransform($sar);
                break;
            default:
        }

        $this->updateProjectPersonsStmt->execute([$sar, $regYear, $registered, $this->projectKey, $fedKey]);
        $this->checkProjectPersonsRegisteredStmt->execute([$this->projectKey, $this->appRegYear]);

        $this->selectProjectPersonIDStmt->execute([$this->projectKey, $fedKey]);
        $pp = $this->selectProjectPersonIDStmt->fetch();

        if (!is_bool($pp)) {
            $ppid = $pp['id'];
            $this->checkProjectPersonRolesStmt->execute([$ppid, $role]);
            $ppr = $this->checkProjectPersonRolesStmt->fetch();
            if (is_bool($ppr)) {
                $this->insertProjectPersonRoleStmt->execute([$ppid, $role, $roleDate, $badge, $badgeDate]);
            } else {
                $this->resetProjectPersonRolesVerifiedStmt->execute([$role, $ppid]);
                $this->updateProjectPersonRoleStmt->execute([$roleDate, $badge, $badgeDate, $ppid, $role]);
                if ($role == 'CERT_REFEREE') {
                    $this->updateProjectPersonRoleStmt->execute([$roleDate, $badge, $badgeDate, $ppid, 'ROLE_REFEREE']);
                }
            }
        }

        return;
    }

}