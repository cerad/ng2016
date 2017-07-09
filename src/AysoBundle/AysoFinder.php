<?php
namespace Cerad\Bundle\AysoBundle;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

class AysoFinder
{
    /** @var  Connection */
    private $conn;

    /** @var  Statement */
    private $findVolStmt;

    /** @var  Statement */
    private $findVolCertStmt;

    /** @var  Statement */
    private $findOrgStmt;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findVol($fedKey)
    {
        // Transform fedKey
        if (strlen($fedKey) === 8) {
            $fedKey = 'AYSOV:' . $fedKey;
        }
        if (!$this->findVolStmt) {
            $sql = <<<EOD
SELECT fedKey,name,email,phone,gender,sar,regYear
FROM   vols
WHERE  fedKey = ?
EOD;
            $this->findVolStmt = $this->conn->prepare($sql);
        }
        $this->findVolStmt->execute([$fedKey]);

        $vol = $this->findVolStmt->fetch();
        if (!$vol) {
            return null;
        }
        // TOSO just add orgKey to record
        $sarParts = explode('/',$vol['sar']);

        $vol['orgKey'] = sprintf('AYSOR:%04d',$sarParts['2']);

        return $vol;
    }
    public function findVolCert($fedKey,$role)
    {
        // Transform fedKey
        if (strlen($fedKey) === 8) {
            $fedKey = 'AYSOV:' . $fedKey;
        }

        if (!$this->findVolCertStmt) {
            $sql = <<<EOD
SELECT fedKey,role,roleDate,badge,badgeDate
FROM   certs
WHERE  fedKey = ? AND role = ?
EOD;
            $this->findVolCertStmt = $this->conn->prepare($sql);
        }
        $this->findVolCertStmt->execute([$fedKey,$role]);

        return $this->findVolCertStmt->fetch() ? : null;
    }
    public function findOrg($orgKey)
    {
        if (!$this->findOrgStmt) {
            $sql = <<<EOD
SELECT orgKey,sar,state FROM orgs WHERE orgKey = ?
EOD;
            $this->findOrgStmt = $this->conn->prepare($sql);
        }
        $this->findOrgStmt->execute([$orgKey]);

        return $this->findOrgStmt->fetch() ? : null;
    }
}
