<?php
namespace AppBundle\Common;

interface ArrayableInterface
{
    public function toArray();
    public function fromArray($data);
}