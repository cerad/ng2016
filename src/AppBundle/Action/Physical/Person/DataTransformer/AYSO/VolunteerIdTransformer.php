<?php
namespace Cerad\Bundle\PersonBundle\DataTransformer\AYSO;

use Symfony\Component\Form\DataTransformerInterface;

class VolunteerIdTransformer implements DataTransformerInterface
{
    protected $fake;
    
    public function __construct($fake = false)
    {
        $this->fake = $fake;
    }
    public function transform($value)
    {   
        if (!$value) return null;

        if (substr($value,0,5) == 'AYSOV') return substr($value,5);

        return $value;
    }
    public function reverseTransform($value)
    {
        $id = preg_replace('/\D/','',$value);
        if (!$id) return null;
        
        if (strlen($id) != 8 && $this->fake)
        {
            // Was 99 but those are valid
            $id = '11' . rand(100000,999999);
        }
        return 'AYSOV' . $id;
    }
}
?>
