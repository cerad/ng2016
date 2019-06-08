<?php
namespace AysoBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class RegionKeyTransformer implements DataTransformerInterface
{           
    public function transform($orgKey)
    {
        if (!$orgKey) return null;

        if (substr($orgKey,0,6) == 'AYSOR:') return (int)substr($orgKey,6);

        return (int)$orgKey;
    }
    public function reverseTransform($regionNumber)
    {
        $key = (int)preg_replace('/\D/','',$regionNumber);
        
        if (!$key) return null;
        
        return sprintf('AYSOR:%04u',$key);
    }
}
?>
