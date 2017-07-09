<?php
namespace Cerad\Bundle\AysoBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class VolunteerKeyTransformer implements DataTransformerInterface
{
    public function transform($fedKey)
    {   
        if (!$fedKey) return null;

        if (substr($fedKey,0,6) == 'AYSOV:') return substr($fedKey,6);

        return $fedKey;
    }
    public function reverseTransform($aysoid)
    {
        $id = preg_replace('/\D/','',$aysoid);
        
        if (!$id) return null;
        
        return 'AYSOV:' . $id;
    }
}
?>
