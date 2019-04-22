<?php declare(strict_types=1);

namespace Zayso\Fed\Ayso;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Zayso\Fed\FedPerson;
use Zayso\Fed\FedPersonCert;

class AysoFinder
{
    // add on aysoid=
    private $urlCert = "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData";

    // Use this to get a page of info
    //private $urlVolCerts = "https://national.ayso.org/Volunteers/ViewCertification?UserName=";

    private $guzzleClient;
    private $idTransformer;
    private $orgViewTransformer;

    public function __construct(AysoIdTransformer $idTransformer, AysoOrgViewTransformer $orgViewTransformer)
    {
        $this->idTransformer      = $idTransformer;
        $this->orgViewTransformer = $orgViewTransformer;

        $this->guzzleClient = new GuzzleClient([
            'verify'  => false,
            'timeout' => 5.0,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }
    // Mainly for trouble shooting
    public function findData(?string $fedPersonId) : ?array
    {
        $fedPersonKey = $this->idTransformer->transform($fedPersonId);
        if ($fedPersonKey === '') return null;

        try {
            $guzzleResponse = $this->guzzleClient->get($this->urlCert, [
                'query' => ['AYSOID' => $fedPersonKey]
            ]);
        } catch (ClientException $e) {
            return null;
            // Time out or what not
            // For console commands at least the exception still goes through
            //die($e->getMessage());
        }

        $results = $this->getResponseData($guzzleResponse);

        return isset($results['VolunteerCertificationDetails']) ? $results : null;
    }
    public function find(?string $fedPersonId) : ?FedPerson
    {
        $results = $this->findData($fedPersonId);

        if ($results === null) return null;

        $details = $results['VolunteerCertificationDetails'];

        $fedPersonId = $this->idTransformer->reverseTransform($details["VolunteerAYSOID"]);
        $fedOrgId    = $this->idTransformer->reverseTransform($details["VolunteerSAR"]);
        $fedOrgView  = $this->orgViewTransformer->transform($fedOrgId);

        $fedPerson = new FedPerson(
            $fedPersonId,
            'AYSO',
            $details["VolunteerFullName"],
            $details["Type"],
            $fedOrgId,
            $details["VolunteerMembershipYear"],
            $fedOrgView
        );

        foreach($details as $group => $certs) {
            if (is_array($certs)) {
                foreach($certs as $certData) {
                    $cert = $this->createCert($group,$certData);
                    if ($cert) {
                        $fedPerson->certs->add($cert);
                    }
                }
            }
        }
        return $fedPerson;
    }
    // Make public for testing
    public function createCert(string $group, array $certData) : ?FedPersonCert
    {
        $certMeta = $this->getCertMeta($group,$certData['CertificationDesc']);

        if ($certMeta === null) return null;

        $date = $certData['CertificationDateIsNull'] ? null : $this->transformDate($certData['CertificationDate']);

        $cert = new FedPersonCert(
            $certMeta['role'],  $date,
            $certMeta['badge'], $date,
            $certMeta['sort']
        );
        return $cert;
    }
    // public for testing
    public function getCertMeta(string $group, string $desc) : ?array
    {
        switch($group) {
            case 'VolunteerCertificationsCoach':
            case 'VolunteerCertificationsInstructor':
            case 'VolunteerCertificationsManagement':
                return null;
        }
        $certMeta = isset($this->certMetas[$desc]) ? $this->certMetas[$desc] : null;
        if ($certMeta === null) {
            //die('Unknown cert type ' . $desc . ' ' . $group . "\n");
            // Lots of coaches and some older stuff
            return null;
        }
        if ($certMeta['sort'] === -1) {
            return null;
        }
        return $certMeta;
    }
    private function transformDate(string $certDate) : string
    {
        if($certDate == '/Date(-62135568000000)/') {
            return '1964-09-15'; // Why???
        }

        $ts = preg_replace('/[^0-9]/', '', $certDate);
        $date = date("Y-m-d", $ts / 1000);

        return $date;
    }
    // Return array from either json or name-value
    private function getResponseData(ResponseInterface $guzzleResponse)
    {
        $content = (string)$guzzleResponse->getBody();

        if (!$content) return [];

        $json = json_decode($content, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        }

        $data = [];
        parse_str($content, $data);
        return $data;
    }
    private $certMetas = [
        'U-8 Official & Safe Haven Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'U-8',
            'sort'  => 5,
        ],
        'Assistant Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Assistant',
            'sort'  => 8,
        ],
        'Regional Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Regional',
            'sort'  => 10,
        ],
        'Regional Referee & Safe Haven Referee' => [
            'role'  => 'CERT_REFEREE', // Ignores the safe haven, obsolete anyways
            'badge' => 'Regional',
            'sort'  => 10,
        ],
        'z-Online Regional Referee without Safe Haven' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Regional',
            'sort'  => 10,
        ],
        'Intermediate Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Intermediate',
            'sort'  => 20,
        ],
        'Advanced Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Advanced',
            'sort'  => 30,
        ],
        'National Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National',
            'sort'  => 90,
        ],
        'National 1 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National_1',
            'sort'  => 80,
        ],
        'National 2 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National_2',
            'sort'  => 70,
        ],
        'Z-Online AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Z-Online Safe Haven Coach' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Coach',
            'sort'  => 80,
        ],
        'AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Webinar-AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Z-Online Refugio Seguro de AYSO' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Safe Haven Referee' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'Z-Online Safe Haven Referee' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'Safe Haven Update' => [
            'sort'  => -1,
            'role'  => null,
            'badge' => null,
        ],
        'Webinar-Safe Haven Update' => [
            'sort'  => -1,
            'role'  => null,
            'badge' => null,
        ],
        'Z-Online CDC Concussion Awareness Training' => [
            'role'  => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort'  => 90,
        ],
        'CDC Online Concussion Awareness Training' => [
            'role'  => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort'  => 90,
        ],
        'U-10 Coach' => [
            'role'  => 'CERT_COACH',
            'badge' => 'U-10',
            'sort'  => 10,
        ],
        'U-12 Coach' => [
            'role'  => 'CERT_COACH',
            'badge' => 'U-12',
            'sort'  => 30,
        ],
        'Intermediate Coach' => [
            'role'  => 'CERT_COACH',
            'badge' => 'Intermediate',
            'sort'  => 30,
        ],
        'Advanced Referee Course' => [
            'role'  => null,
            'badge' => null,
            'sort'  => -1,
        ],

    ];
}