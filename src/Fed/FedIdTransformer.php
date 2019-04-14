<?php declare(strict_types=1);

namespace Zayso\Fed;

// Want a base class for transforming various fed oriented ids
use Symfony\Component\Form\DataTransformerInterface;

class FedIdTransformer implements DataTransformerInterface
{
    protected $FED_ID = 'FED';

    public function transform($id) : string // Or should it be ?string
    {
        if ($id === null) return '';

        $id = trim($id);

        $idParts = explode(':',$id);

        return isset($idParts[1]) ? $idParts[1] : $id;
    }
    public function reverseTransform($value) : string
    {
        if ($value === null) return '';

        $value = trim((string)$value);

        if ($value === '') return '';

        return $this->FED_ID . ':' . $value;
    }
}