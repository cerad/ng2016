<?php

namespace AysoBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class TimeValueToTime implements DataTransformerInterface
{
    public function transform($timeValue){

        return ($timeValue * 86400) - ((70 * 365 + 19) * 86400);
    }

    public function reverseTransform($time){

        return $time + ((70 * 365 + 19) * 86400) / 86400;
    }

}