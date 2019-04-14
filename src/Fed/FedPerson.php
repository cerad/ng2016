<?php declare(strict_types=1);

namespace Zayso\Fed;

/**
 * @property-read string $fedPersonId
 * @property-read string $fedId
 * @property-read string $fullName
 * @property-read string $ageGroup
 * @property-read string $fedOrgId
 * @property-read string $fedOrgView
 * @property-read string $memYear
 * @property-read FedPersonCerts $certs
 */
class FedPerson
{
    public $fedPersonId; // 8 or 9 digits maybe
    public $fedId;       // 'AYSO'
    public $fullName;    // Hundiak, Arthur
    public $ageGroup;    // Adult or one assumes Youth
    public $fedOrgId;    // 5/C/0894
    public $fedOrgView;  // 05/C/0894/AL
    public $memYear;     // MY2016

    public $certs;

    public function __construct(
         string $fedPersonId,
         string $fedId,
         string $fullName,
        ?string $ageGroup   = null,
        ?string $fedOrgId   = null,
        ?string $memYear    = null,
        ?string $fedOrgView = null)
    {
        $this->fedPersonId = $fedPersonId;
        $this->fedId       = $fedId;
        $this->fullName    = $fullName;
        $this->ageGroup    = $ageGroup;
        $this->fedOrgId    = $fedOrgId;
        $this->fedOrgView  = $fedOrgView;
        $this->memYear     = $memYear;

        $this->certs = new FedPersonCerts(); // Not immutable
    }
}