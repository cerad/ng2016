<?php
namespace Tests\AppBundleAction\Physical\Ayso;

use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;

use Tests\AppBundle\AbstractTestDatabase;

class PhysicalAysoRepositoryTest extends AbstractTestDatabase
{
    public function setUp()
    {
        $this->schemaFile = 'src/AppBundle/Action/Physical/Ayso/schema.sql';

        parent::setUp();
    }

    public function testFind()
    {
        $conn = $this->conn;
        $this->resetDatabase($conn);
        
        $sql = <<<EOD
INSERT INTO vols (fedKey,name,email,phone,gender,sar,regYear) VALUES(?,?,?,?,?,?,?);
EOD;
        $insertVolStmt = $conn->prepare($sql);

        $fedKey = 'AYSOV:99990001';

        $insertVolStmt->execute([
            $fedKey,
            'Buffy Summers',
            'buffy@sunnydale.tv',
            '256-555-9999',
            'F',
            '5/C/0894',
            'MY2016',
        ]);

        $aysoRepository = new PhysicalAysoRepository($conn);

        $vol = $aysoRepository->findVol($fedKey);
        $this->assertEquals('MY2016',$vol['regYear']);

        $vol = $aysoRepository->findVol(null);
        $this->assertNull($vol);

        $sql = <<<EOD
INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate) VALUES(?,?,?,?,?);
EOD;
        $insertCertStmt = $conn->prepare($sql);
        $insertCertStmt->execute([
            $fedKey,
            'ROLE_REFEREE',
            '2001-02-03',
            'National Supreme',
            '2010-06-05',
        ]);

        $cert = $aysoRepository->findVolCert($fedKey,'ROLE_REFEREE');

        $this->assertEquals('National Supreme',$cert['badge']);
        $this->assertEquals('2010-06-05',$cert['badgeDate']);

        $badgeDate = new \DateTime($cert['badgeDate']);
        $this->assertEquals('Jun',$badgeDate->format('M'));
    }
}