<?php
namespace Cerad\Bundle\PersonBundle\DataTransformer\USSF;

use Symfony\Component\Form\DataTransformerInterface;

class StateIdTransformer implements DataTransformerInterface
{           
    public function transform($value)
    {
        if (!$value) return null;

        if (substr($value,0,6) == 'USSFS_') return substr($value,6);

        return $value;
    }
    public function reverseTransform($value)
    {
        return 'USSFS_' . $value;        
    }
}
?>
