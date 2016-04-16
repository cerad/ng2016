<?php
namespace AppBundle\Action\Physical\Ayso\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class VolunteerKeyTransformer implements DataTransformerInterface
{
    public function transform($value)
    {   
        if (!$value) return null;

        if (substr($value,0,6) == 'AYSOV:') return substr($value,6);

        return $value;
    }
    public function reverseTransform($value)
    {
        $id = preg_replace('/\D/','',$value);
        
        if (!$id) return null;
        
        return 'AYSOV:' . $id;
    }
}
?>
