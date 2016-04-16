<?php
namespace AppBundle\Action\Physical\Ayso;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

class PhysicalAysoRepository
{
    /** @var  Connection */
    private $conn;

    /** @var  Statement */
    private $findVolStmt;

    /** @var  Statement */
    private $findVolCertStmt;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findVol($fedKey)
    {
        //if (!$fedKey) return null; // Otherwise we get false

        if (!$this->findVolStmt) {
            $sql = <<<EOD
SELECT fedKey,name,email,phone,gender,sar,regYear
FROM   vols
WHERE  fedKey = ?
EOD;
            $this->findVolStmt = $this->conn->prepare($sql);
        }
        $this->findVolStmt->execute([$fedKey]);

        return $this->findVolStmt->fetch() ? : null;
    }
    public function findVolCert($fedKey,$role)
    {
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
}