<?php

namespace AppBundle\Action\Physical\Ayso;

use AppBundle\Action\Services\VolCerts;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class PhysicalAysoRepository
{
    /** @var  Connection */
    private $conn;

    /** @var VolCerts */
    private $volCerts;

    /** @var  Statement */
    private $findVolStmt;

    /** @var  Statement */
    private $findVolCertStmt;

    /** @var  Statement */
    private $findOrgStmt;

    public function __construct(Connection $conn, VolCerts $volCerts)
    {
        $this->conn = $conn;
        $this->volCerts = $volCerts;
    }

    /**
     * @param $fedKey
     * @return mixed|null
     * @throws DBALException
     */
    public function findVol($fedKey)
    {
        // Transform fedKey
        if (strlen($fedKey) === 8 OR strlen($fedKey) === 9) {
            $fedKey = 'AYSOV:'.$fedKey;
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
        $fedKeyParts =  explode(':', $fedKey);
        $id = isset($fedKeyParts[1]) ? $fedKeyParts[1] : null;

        /** @var array $e3Certs */
        $e3Certs = $this->volCerts->retrieveVolCertData($id);

        // TODO just add orgKey to record
        $sarParts = explode('/', $e3Certs['SAR']);

        $vol['orgKey'] = sprintf('AYSOR:%04d', $sarParts['2']);

        return $vol;
    }

    /**
     * @param $fedKey
     * @param $role
     * @return null
     * @throws DBALException
     */
    public function findVolCert($fedKey, $role)
    {
        // Transform fedKey
        if (strlen($fedKey) === 8 OR strlen($fedKey) === 9) {
            $fedKey = 'AYSOV:'.$fedKey;
        }

        if (!$this->findVolCertStmt) {
            $sql = <<<EOD
SELECT fedKey,role,roleDate,badge,badgeDate
FROM   certs
WHERE  fedKey = ? AND role = ?
EOD;
            $this->findVolCertStmt = $this->conn->prepare($sql);
        }
        $this->findVolCertStmt->execute([$fedKey, $role]);

        $fedKeyParts =  explode(':', $fedKey);
        $id = isset($fedKeyParts[1]) ? $fedKeyParts[1] : null;

        /** @var array $e3Certs */
        $e3Certs = $this->volCerts->retrieveVolCertData($id);

        $volCert = $this->findVolCertStmt->fetch() ?: null;

        $e3Cert = isset($e3Certs['RefCertDesc']) ? explode(' ',$e3Certs['RefCertDesc'])[0] : '';
        $e3CertDate = isset($e3Certs['RefCertDate']) ? $e3Certs['RefCertDate'] : '';
        $volCert['badge'] = $e3Cert;
        $volCert['badgeDate'] = $e3CertDate;
        $volCert['roleDate']  = $e3CertDate;

        return $volCert;
    }

    /**
     * @param $orgKey
     * @return null
     * @throws DBALException
     */
    public function findOrg($orgKey)
    {
        if (!$this->findOrgStmt) {
            $sql = <<<EOD
SELECT orgKey,sar,state FROM orgs WHERE orgKey = ?
EOD;
            $this->findOrgStmt = $this->conn->prepare($sql);
        }

        $this->findOrgStmt->execute([$orgKey]);

        $org = $this->findOrgStmt->fetch() ?: null;;

        return $org;
    }
}
