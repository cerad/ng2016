<?php
namespace AppBundle\Action\Physical\Ayso\Load;

use Symfony\Component\Console\Input\InputArgument;

class LoadOrgsCommand extends LoadAbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('load:ayso:orgs')
            ->setDescription('Load AYSO Regions')
            ->addArgument('filename', InputArgument::REQUIRED, 'AYSO ORG File');
    }
    protected function load($filename)
    {
        $fp = fopen($filename, 'r');

        /** @noinspection PhpUnusedLocalVariableInspection */
        $header = fgetcsv($fp); // var_dump($header);

        $rowCount = 0;
        while($row = fgetcsv($fp)) {

            $rowCount++;
            $region  = (integer)trim($row[0]);
            $section = (integer)trim($row[1]);
            $area    =          trim($row[2]);
            $state   =          trim($row[3]);
            $comms   =          trim($row[4]);

            $this->processOrg($region,$section,$area,$state,$comms);

            if (($rowCount % 100) === 0) {
                echo sprintf("\rProcessed %5d",$rowCount);
            }
        }
        echo sprintf("\rProcessed %5d rows      \n",$rowCount);

        fclose($fp);
    }
    private function processOrg($region,$section,$area,$state,$comms)
    {
        if (!$region) {
            return;
        }
        $state = $this->states[$state];
        $orgKey = sprintf('AYSOR:%04d',$region);
        $sar = sprintf('%d/%s/%04d',$section,$area,$region);

        $sql = 'SELECT * FROM orgs WHERE orgKey = ?';
        $stmt = $this->conn->executeQuery($sql,[$orgKey]);
        $row = $stmt->fetch();
        if (!$row) {

            //echo sprintf("New %s %s %s\n",$orgKey,$sar,$state);
            $org = [
                'orgKey' => $orgKey,
                'sar' => $sar,
                'state' => $state,
                'comms' => $comms,
            ];
            $this->conn->insert('orgs',$org);
            return;
        }
        if ($row['sar'] !== $sar) {
            // No problems with sars
            echo sprintf("*** SAR %s %s %s\n",$row['sar'],$sar,$state);
        }
        if ($row['state'] !== $state) {
            // Lots of issues with states
            echo sprintf("*** State %s %s %s\n",$row['state'],$state,$sar);
            $this->conn->update('orgs',['state' => $state],['orgKey' => $orgKey]);
        }
        if ($row['comms'] !== $comms) {
            $this->conn->update('orgs',['comms' => $comms],['orgKey' => $orgKey]);
        }
    }
    private $states = [
        'Alabama'        => 'AL',
        'Alaska'         => 'AK',
        'Arizona'        => 'AZ',
        'Arkansas'       => 'AR',
        'California'     => 'CA',
        'Colorado'       => 'CO',
        'Connecticut'    => 'CT',
        'Delaware'       => 'DE',
        'Florida'        => 'FL',
        'Georgia'        => 'GA',
        'Hawaii'         => 'HI',
        'Idaho'          => 'ID',
        'Illinois'       => 'IL',
        'Indiana'        => 'IN',
        'Iowa'           => 'IA',
        'Kansas'         => 'KS',
        'Kentucky'       => 'KY',
        'Louisiana'      => 'LA',
        'Maine'          => 'ME',
        'Maryland'       => 'MD',
        'Massachusetts'  => 'MA',
        'Michigan'       => 'MI',
        'Minnesota'      => 'MN',
        'Mississippi'    => 'MS',
        'Missouri'       => 'MO',
        'Montana'        => 'MT',
        'Nebraska'       => 'NE',
        'Nevada'         => 'NV',
        'New Hampshire'  => 'NH',
        'New Jersey'     => 'NJ',
        'New Mexico'     => 'NM',
        'New York'       => 'NY',
        'North Carolina' => 'NC',
        'North Dakota'   => 'ND',
        'Ohio'           => 'OH',
        'Oklahoma'       => 'OK',
        'Oregon'         => 'OR',
        'Pennsylvania'   => 'PA',
        'Rhode Island'   => 'RI',
        'South Carolina' => 'SC',
        'South Dakota'   => 'SD',
        'Tennessee'      => 'TN',
        'Texas'          => 'TX',
        'Utah'           => 'UT',
        'Vermont'        => 'VT',
        'Virginia'       => 'VA',
        'Washington'     => 'WA',
        'West Virginia'  => 'WV',
        'Wisconsin'      => 'WI',
        'Wyoming'        => 'WY',

        'Virgin Islands' => 'VI',
        'TT'             => 'TT', // Trinidad & Tobago
    ];
}