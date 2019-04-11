<?php declare(strict_types=1);

namespace App\Ayso;

/**
 * @property-read string $aysoid
 * @property-read string $fullName
 * @property-read string $type
 * @property-read string $sar
 * @property-read string $memYear
 * @property-read AysoVolCerts $certs
 */
class AysoVol
{
    public $aysoid;   // 8 or 9 digits maybe
    public $fullName; // Hundiak, Arthur
    public $type;     // Adult or one assumes Youth
    public $sar;      // 5/C/0894
    public $memYear;  // MY2016

    public $certs;

    public function __construct(
        string $aysoid,
        string $fullName,
        string $type,
        string $sar,
        string $memYear)
    {
        $this->aysoid   = $aysoid;
        $this->fullName = $fullName;
        $this->type     = $type;
        $this->sar      = $sar;
        $this->memYear  = $memYear;

        $this->certs = new AysoVolCerts(); // Not immutable
    }
}