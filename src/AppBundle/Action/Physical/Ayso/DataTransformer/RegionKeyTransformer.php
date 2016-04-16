<?php
namespace AppBundle\Action\Physical\Ayso\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class RegionKeyTransformer implements DataTransformerInterface
{           
    public function transform($value)
    {
        if (!$value) return null;

        if (substr($value,0,6) == 'AYSOR:') return (int)substr($value,6);

        return (int)$value;
    }
    public function reverseTransform($value)
    {
        $key = (int)preg_replace('/\D/','',$value);
        
        if (!$key) return null;
        
        return sprintf('AYSOR:%04u',$key);
    }
}
?>
