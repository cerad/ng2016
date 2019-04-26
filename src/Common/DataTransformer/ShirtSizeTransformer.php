<?php declare(strict_types=1);

namespace Zayso\Common\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

// TODO This needs to be project specific
// And it should support generating a choice list
class ShirtSizeTransformer implements DataTransformerInterface
{
    private $shirtSizes = [
        'na'         =>     'na',
        'youths'     =>     'YS',
        'youthm'     =>     'YM',
        'youthl'     =>     'YL',
        'adults'     =>     'AS',
        'adultm'     =>     'AM',
        'adultl'     =>     'AL',
        'adultlx'    =>    'ALX',
        'adultlxx'   =>   'ALXX',
        'adultlxxx'  =>  'ALXXX',
        'adultlxxxx' => 'ALXXXX',
    ];
    public function transform($value)
    {
        return isset($this->shirtSizes[$value]) ? $this->shirtSizes[$value] : '???';
    }
    public function reverseTransform($value)
    {
        $key = array_search($value,$this->shirtSizes);
        return $key ? $key : 'na';
    }
}