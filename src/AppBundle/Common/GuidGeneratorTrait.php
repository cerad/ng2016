<?php
namespace AppBundle\Common;

trait GuidGeneratorTrait
{
    public function generateGuid()
    {
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),     mt_rand(0, 65535),     mt_rand(0, 65535),
            mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535),
            mt_rand(0, 65535),     mt_rand(0, 65535)
        );
    }
}