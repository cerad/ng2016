<?php

namespace AysoBundle\Load;

use Doctrine\DBAL\Connection;
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

    protected function configure()
    {
        $this
            ->setName('ayso:load:e3:certs')
            ->setDescription('Load AYSO e3 Cert Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO e3 Cert File')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete existing data before update')
            ->addOption('commit', 'c', InputOption::VALUE_NONE, 'Commit data');
    }

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

    protected function processCert(array $row = [])
    {
        if (empty($row) || empty($row[0]) || $row[0] == 'AYSOID' || $row[1] == '*** Volunteer not found ***') {
            return;
        }

        $fedKey = strpos($row[0], 'AYSOV:') ? $row[0] : 'AYSOV:'.$row[0];

        //$type = $row[2]; // Adult or Youth
        $regYear = $row[3];
        $sar = $row[4];
        $roleDate = '';

        $dt = DateTime::createFromFormat('Y-m-d', $row[5]);
        $badgeSHDate = $dt !== false ? $dt->format('Y-m-d') : '';
        if (!empty($badgeSHDate)) {
            $roleSH = 'CERT_SAFE_HAVEN';
            $badgeSH = 'AYSO';
            $roleDate = $badgeSHDate;
        } else {
            $roleSH = '';
            $badgeSH = '';
        }

        $dt = DateTime::createFromFormat('Y-m-d', $row[6]);
        $badgeCDCDate = $dt !== false ? $dt->format('Y-m-d') : '';
        if (!empty($badgeCDCDate)) {
            $roleCDC = 'CERT_CONCUSSION';
            $badgeCDC = 'CDC Concussion';
            $roleDate = $badgeCDCDate;
        } else {
            $roleCDC = '';
            $badgeCDC = '';
        }

        $dt = DateTime::createFromFormat('Y-m-d', $row[8]);
        $badgeDate = $dt !== false ? $dt->format('Y-m-d') : explode('/', $row[8])[0];
        if (empty($badgeDate)) {
            $badge = 'None';
            $role = '';
        } else {
            $role = "CERT_REFEREE";
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

        if (!isset($this->certMetas[$badge])) {
//            echo "*** Missing cert meta: \n";
//            dump($row);

            return;
        }

        $badge = $this->certMetas[$badge]['badge'];

        // Update AYSO.Certs & NG2019.ProjectPersonRoles tables
        if ($this->commit) {

            $this->checkCertStmt->execute([$fedKey, $role]);
            $cert = $this->checkCertStmt->fetch();
            if (is_bool($cert)) {
                $this->insertCert($fedKey, $role, $roleDate, $badge, $badgeDate);
            } else {
                $this->updateCert($fedKey, $role, $roleDate, $badge, $badgeDate, $cert);
            }

            $this->refreshProjectPersonsAndRole($fedKey, $sar, $regYear, $role, $roleDate, $badge, $badgeDate);

            if (!empty($roleSH)) {
                $this->checkCertStmt->execute([$fedKey, $roleSH]);
                $cert = $this->checkCertStmt->fetch();

                if (is_bool($cert)) {
                    $this->insertCert($fedKey, $roleSH, $roleDate, $badgeSH, $badgeSHDate);
                } else {
                    $this->updateCert($fedKey, $roleSH, $roleDate, $badgeSH, $badgeSHDate, $cert);
                }

                $this->refreshProjectPersonsAndRole(
                    $fedKey,
                    $sar,
                    $regYear,
                    $roleSH,
                    $roleDate,
                    $badgeSH,
                    $badgeSHDate
                );
            }
            if (!empty($roleCDC)) {
                $this->checkCertStmt->execute([$fedKey, $roleCDC]);
                $cert = $this->checkCertStmt->fetch();
                if (is_bool($cert)) {
                    $this->insertCert($fedKey, $roleCDC, $roleDate, $badgeCDC, $badgeCDCDate);
                } else {
                    $this->updateCert($fedKey, $roleCDC, $roleDate, $badgeCDC, $badgeCDCDate, $cert);
                }

                $this->refreshProjectPersonsAndRole(
                    $fedKey,
                    $sar,
                    $regYear,
                    $roleCDC,
                    $roleDate,
                    $badgeCDC,
                    $badgeCDCDate
                );
            }
        }

        return;
    }

    private function insertCert($fedKey, $role, $roleDate, $badge, $badgeDate)
    {

        echo sprintf("\r  Inserting %s %s %s...     ", $fedKey, $badge, $badgeDate);

        if ($this->commit) {
            $this->insertCertStmt->execute([$fedKey, $role, $roleDate, $badge, $badgeDate]);
        }

        return;
    }

    private function updateCert($fedKey, $role, $roleDate, $badge, $badgeDate, $cert)
    {
        echo sprintf("\r  Updating %s...        ", $fedKey);

        if ($this->badgeSorts[$badge] > $this->badgeSorts[$cert['badge']]) {
            $cert['badge'] = $badge;
            $cert['badgeDate'] = $badgeDate;
        }
        if ($badge === $cert['badge']) {
            if ($cert['badgeDate'] === '0000-00-00' && $badgeDate) {
                $cert['badgeDate'] = $badgeDate;
            }
            if (($cert['badgeDate'] === null) && $badgeDate) {
                $cert['badgeDate'] = $badgeDate;
            }
        }
        if ($cert['badgeDate'] === '0000-00-00') {
            $cert['badgeDate'] = $badgeDate;

            return;
        }
        if ($cert['roleDate'] === '0000-00-00' && $roleDate) {
            $cert['roleDate'] = $roleDate;
        }
        if ($roleDate > $cert['roleDate'] && $roleDate) { // Get here, not really sure why
            $cert['roleDate'] = $roleDate;
        }

        if ($this->commit) {
            $this->updateCertStmt->execute([$cert['roleDate'], $cert['badge'], $cert['badgeDate'], $fedKey, $role]);
        }
//        echo sprintf("Updated Vols %s for %s\n", $regYear, $fedKey);
//        $this->updateVolsRegYearStmt->execute([$regYear, $sar, $fedKey]);

    }

    private function refreshProjectPersonsAndRole($fedKey, $sar, $regYear, $role, $roleDate, $badge, $badgeDate)
    {
        if (!$this->commit) {
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

        $this->updateProjectPersonsStmt->execute([$sar, $regYear, $registered, $fedKey]);

        $this->selectProjectPersonIDStmt->execute([$this->projectKey, $fedKey]);
        $pp = $this->selectProjectPersonIDStmt->fetch();

        if (!is_bool($pp)) {
            $ppid = $pp['id'];
            $this->checkProjectPersonRolesStmt->execute([$ppid, $role]);
            $ppr = $this->checkProjectPersonRolesStmt->fetch();
            if (is_bool($ppr)) {
                $this->insertProjectPersonRoleStmt->execute([$ppid, $role, $roleDate, $badge, $badgeDate]);
            } else {
                $this->updateProjectPersonRoleStmt->execute([$roleDate, $badge, $badgeDate, $ppid, $role]);
                $this->updateProjectPersonRoleStmt->execute([$roleDate, $badge, $badgeDate, $ppid, 'ROLE_REFEREE']);
                $this->updateProjectPersonRoleStmt->execute([$roleDate, $badge, $badgeDate, $ppid, 'ROLE_VOLUNTEER']);
            }
        }

        return;
    }

}