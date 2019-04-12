<?php declare(strict_types=1);

namespace Zayso\Fed\Ayso;

use Symfony\Component\Form\DataTransformerInterface;

class AysoOrgViewTransformer implements DataTransformerInterface
{
    public function transform($orgId) : string
    {
        $orgId = trim($orgId);

        $orgParts = explode(':',$orgId);
        $orgKey = isset($orgParts[1]) ? $orgParts[1] : $orgId;

        if (!$orgKey) return 'Missing';

        return isset($this->orgs[$orgKey]) ? $this->orgs[$orgKey] : $orgId;
    }
    public function reverseTransform($orgView) : string
    {
        return $orgView;
    }
    private $orgs = [
        '10/D'      => 'X 10/D',
        '7'         => 'X 7',
        '7/O'       => 'X 7/O',
        '70/A/7001' => 'X 70/A/7001',
        '70/C/7015' => 'X 70/C/7015',

        '1/D/0092'  => '01/D/0092/CA',
        '1/N/0137'  => '01/N/0137/CA',
        '1/P/0019'  => '01/P/0019/CA',
        '10/D/0638' => '10/D/0638/CA',
        '10/V/0058' => '10/V/0058/CA',
        '10/W/0304' => '10/W/0304/CA',
        '11/K/0056' => '11/K/0056/CA',
        '11/K/0117' => '11/K/0117/CA',
        '11/L/0084' => '11/L/0084/CA',
        '11/L/0085' => '11/L/0085/CA',
        '2/C/0281'  => '02/C/0281/CA',
        '5/C/0894'  => '05/C/0894/AL',
        '6/D/0891'  => '06/D/0891/IL',
        '6/U/0208'  => '06/U/0208/KS',
        '7/A/0274'  => '07/A/0274/HI',
        '7/A/0403'  => '07/A/0403/HI',
        '7/E/0118'  => '07/E/0118/HI',
        '7/E/0119'  => '07/E/0119/HI',
        '7/E/0188'  => '07/E/0188/HI',
        '7/E/0269'  => '07/E/0269/HI',
        '7/E/0358'  => '07/E/0358/HI',
        '7/E/0381'  => '07/E/0381/HI',
        '7/E/0769'  => '07/E/0769/HI',
        '7/O/0048'  => '07/O/0048/HI',
        '7/O/0100'  => '07/O/0100/HI',
        '7/O/0113'  => '07/O/0113/HI',
        '7/O/0178'  => '07/O/0178/HI',
        '8/E/0250'  => '08/E/0250/MI',
    ];
}