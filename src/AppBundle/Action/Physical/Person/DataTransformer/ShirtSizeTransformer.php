<?php
namespace AppBundle\Action\Physical\Person\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

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
?>
