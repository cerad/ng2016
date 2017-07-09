<?php
namespace AppBundle\Action\Physical\Ayso\Load;

use Symfony\Component\Console\Input\InputArgument;

class LoadTomCommand extends LoadAbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('load:ayso:tom')
            ->setDescription('Load Toms AYSO Cert Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO Cert File');
    }
    protected function load($filename)
    {
        $rowCount = 0;
        while($row = fgetcsv($fp)) {
            $fp = fopen($filename, 'r');

            /** @noinspection PhpUnusedLocalVariableInspection */
            $header = fgetcsv($fp); // var_dump($header);


            $rowCount++;

            $regYear = trim($row[1]); // "Membership Year"

            if (substr($regYear, 0, 2) === 'MY') {

                $fedKey = 'AYSOV:' . (string)trim($row[0]); // "AYSOID";

                //$this->loadVol  ($fedKey,$regYear,$row);
                //$this->loadCerts($fedKey,trim($row[2]),trim($row[3])); // Referee
                //$this->loadCerts($fedKey,trim($row[4]),null);          // Concussion
                //$this->loadCerts($fedKey,trim($row[5]),null);          // Safe Haven
                //$this->loadOrg     (trim($row[6]),trim($row[10]));
                $this->loadOrgState(trim($row[6]),trim($row[10]));
            }
            if (($rowCount % 100) === 0) {
                echo sprintf("\rProcessed %5d",$rowCount);
            }
        }
        echo sprintf("\rProcessed %5d rows      \n",$rowCount);

        fclose($fp);
    }
    private function loadVol($fedKey, $regYear, $row)
    {
        $item = [
            $fedKey,
            trim($row[ 7]),  // "Name"
            trim($row[13]),  // "Email"
            trim($row[12]),  // "HomePhone"
            null,            // "Gender M or F
            trim($row[ 6]),  // "SectionAreaRegion"
            $regYear,
        ];

        $this->checkVolStmt->execute([$fedKey]);
        $vol = $this->checkVolStmt->fetch();
        if (!$vol) {
            $this->insertVolStmt->execute($item);
            //echo sprintf("Inserted Vol: %s,%s\n",$fedKey,$row[7]);
            return;
        }
        if ($regYear <= $vol['regYear']) {
            return;
        }
        unset($item[0]);
        $item = array_values($item);
        $item[] = $fedKey;

        $this->updateVolStmt->execute($item);
    }
    private function loadCerts($fedKey,$certDescs,$certDate)
    {
        $certDescs = str_replace(' & ',',',$certDescs);
        $certDescs = explode(',',$certDescs);

        foreach($certDescs as $certDesc) {
            $this->loadCert($fedKey,trim($certDesc),$certDate);
        }
    }
    private function loadCert($fedKey,$certDesc,$certDate)
    {
        // Safe haven and concussion
        if (!$certDesc || $certDesc === 'No') {
            return;
        }
        $certMeta = isset($this->certMetas[$certDesc]) ? $this->certMetas[$certDesc] : null;
        if (!$certMeta) {
            die('Missing cert: ' . $fedKey . ' ' . $certDesc);
        }
//if (1) return;
        $role  = $certMeta['role'];
        if (!$role) {
            return;
        }
        $badge = $certMeta['badge'];

        $badgeDate = \DateTime::createFromFormat('d-M-y',$certDate); // 04-Feb-06
        if ($badgeDate) {
            $badgeDate = $badgeDate->format('Y-m-d');
            if (!$badgeDate) {
                $badgeDate = null;
            }
        }
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

                //die(sprintf('Update Role date %s %s > %s',$fedKey,$roleDate, $roleDateExisting));
                return;
            }
            return;
        }
        $roleDate = $roleDateExisting ? : $roleDate;

        $this->updateCertStmt->execute([$roleDate,$badge,$badgeDate,$fedKey,$role]);

        return;
    }
    private function loadOrg($sar,$state)
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

        $this->checkOrgStmt->execute([$orgKey]);
        if ($org = $this->checkOrgStmt->fetch()) {
            if ($state && !trim($org['state'])) {
                $this->updateOrgStmt->execute([$state,$orgKey]);
            }
            return;
        }
        $this->insertOrgStmt->execute([$orgKey,$sar,$state]);
    }
    private function loadOrgState($orgKey,$state)
    {
        if (!$state) return;

        $this->checkOrgStateStmt->execute([$orgKey,$state]);
        if ($this->checkOrgStateStmt->fetch()) {
            return;
        }
        $this->insertOrgStateStmt->execute([$orgKey,$state]);
    }
    /*
        [ 0] => "AYSOID"
        [ 1] => "Membership Year"
        [ 2] => "Certification Level"
        [ 3] => "CertDate"
        [ 4] => "Concussion"
        [ 5] => "Safe Haven"
        [ 6] => "SectionAreaRegion"
        [ 7] => "Name"
        [ 8] => "Street"
        [ 9] => "City"
        [10] => "State"
        [11] => "Zip"
        [12] => "HomePhone"
        [13] => "Email"
    */
}