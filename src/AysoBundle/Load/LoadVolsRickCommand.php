<?php

namespace AysoBundle\Load;

use Symfony\Component\Console\Input\InputArgument;

class LoadVolsRickCommand extends LoadAbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('ayso:load:vols')
            ->setDescription('Load Rick AYSO Vol Data')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO Vol File');
    }

    protected function load($filename)
    {
        $fp = fopen($filename, 'r');

        /** @noinspection PhpUnusedLocalVariableInspection */
        $header = fgetcsv($fp);

        while ($row = fgetcsv($fp)) {

            $this->processVol($row);
        }
    }
    private $vols = [];

    protected function processVol(array $row)
    {
        $fedKey  = $row[0];
        $name    = $row[1];
        $email   = $row[2];
        $phone   = $row[3];
        $gender  = $row[4];
        // $dob     = $row[5]; // TODO
        $sar     = $row[6];
        $regYear = $row[7];

        if (!$fedKey) {
            dump($row); die('Missing fed key');
        }
        if (isset($this->vols[$fedKey])) {
            return; // AYSOV:51405587
        }
        $this->vols[$fedKey] = $row;

        $this->checkVolStmt->execute([$fedKey]);
        $vol = $this->checkVolStmt->fetch();

        if (!$vol) { // New

            dump($row); die('Inserting');
            $this->insertVolStmt->execute([$fedKey,$name,$email,$phone,$gender,$sar,$regYear]);
            return;
        }
        $update = false;
        if ($vol['regYear'] < $regYear && ($regYear)) {
            //dump($vol);dump($row); die('regYear');
            $vol['regYear'] = $regYear;
            $update = true;
        }
        if ($vol['name'] !== $name && ($name)) {
            //dump($vol);dump($row); die('name');
            $vol['name'] = $name;
            $update = true;
        }
        if ($vol['email'] !== $email && ($email)) {
            //dump($vol);dump($row); die('email');
            $vol['email'] = $email;
            $update = true;
        }
        if ($vol['phone'] !== $phone && ($phone)) {
            //dump($vol);dump($row); die('phone');
            $vol['phone'] = $phone;
            $update = true;
        }
        if ($vol['gender'] !== $gender && ($gender)) {
            //dump($vol);dump($row); die('gender');
            $vol['gender'] = $gender;
            $update = true;
        }
        if ($vol['sar'] !== $sar && ($sar)) {
            //dump($vol,$row); die('sar');
            $vol['sar'] = $sar;
            $update = true;
        }
        if (!$update) {
            return;
        }

        //  name = ?, email = ?, phone = ?, gender = ?, sar = ?, regYear = ?
        $this->updateVolStmt->execute([
            $vol['name'],  $vol['email'],$vol['phone'],
            $vol['gender'],$vol['sar'],  $vol['regYear'],
            $fedKey]);
        dump($vol); die('update');
    }
}