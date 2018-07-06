<?php

namespace AysoBundle\Load;

use Symfony\Component\Console\Input\InputArgument;

class LoadCertRickCommand extends LoadAbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('ayso:load:certs')
            ->setDescription('Load Ricks AYSO Cert Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO Cert File');
    }

    protected function load($filename)
    {
        $fp = fopen($filename, 'r');

        /** @noinspection PhpUnusedLocalVariableInspection */
        $header = fgetcsv($fp);

        while ($row = fgetcsv($fp)) {

            $this->processCert($row);
        }
    }
    protected function processCert(array $row)
    {
        $fedKey = $row[0];
        $role   = $row[1];
        $badge  = $row[3];
        $roleDate = str_replace('/','-',$row[2]);
        $badgeDate = str_replace('/','-',$row[4]);

        if ($role === 'CERT_CONCUSSION' && $badge === 'AYSO') {
            $badge = 'CDC Online Concussion Awareness Training';
        }
        if ($role === 'CERT_SAFE_HAVEN' && $badge === 'AYSO') {
            $badge = 'AYSOs Safe Haven';
        }
        if (!isset($this->certMetas[$badge])) {
            echo "*** Missing cert meta\n";
            var_dump($row); //
            exit();
        }
        $badge = $this->certMetas[$badge]['badge'];

        //var_dump($row);

        $this->checkCertStmt->execute([$fedKey,$role]);
        $cert = $this->checkCertStmt->fetch();

        if (!$cert) { // New

            // 'INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate) VALUES (?,?,?,?,?)';
            $this->insertCertStmt->execute([$fedKey,$role,$roleDate,$badge,$badgeDate]);
            //echo sprintf("Inserted Cert: %s %s %s\n",$row[0],$row[1],$row[3]);
            return;
        }
        $update = false;
        if ($this->badgeSorts[$badge] > $this->badgeSorts[$cert['badge']]) {
            dump($cert); dump($row); die('badge change');
            $update = true;
            $cert['badge'] = $badge;
            $cert['badgeDate'] = $badgeDate;
        }
        if ($badge === $cert['badge']) {
            if ($cert['badgeDate'] === '0000-00-00' && $badgeDate) {
                dump($cert); dump($row); exit('badge date 0000-00-00');
                $cert['badgeDate'] = $badgeDate;
                $update = true;
            }
            if ($cert['badgeDate'] === null && $badgeDate) {
                $cert['badgeDate'] = $badgeDate;
                $update = true;
                dump($cert); dump($row); exit('badge date is null');
            }
        }
        if ($cert['badgeDate'] === '0000-00-00') {
            //dump($cert); dump($row); exit();
        }
        if ($cert['roleDate'] === '0000-00-00' && $roleDate) {
            //dump($cert); dump($row); exit();
            $cert['roleDate'] = $roleDate;
            $update = true;
        }
        if ($cert['roleDate'] > $roleDate && $roleDate) { // Get here, not really sure why
            dump($cert); dump($row); exit('cert roleDate > roleDate');
            $cert['roleDate'] = $roleDate;
            $update = true;
        }
        if (!$update) {
            return;
        }

        dump($cert,$row); die('update');
        //    var_dump($cert);
        //    echo sprintf("Updated Cert %s %s %s\n",$row[0],$row[1],$cert['badge'],$row[3]);
        //    exit();
        //'UPDATE certs SET roleDate = ?, badge = ?, badgeDate = ? WHERE fedKey = ? AND role = ?';
        $this->updateCertStmt->execute([$cert['roleDate'],$cert['badge'],$cert['badgeDate'],$fedKey,$role]);
    }
}