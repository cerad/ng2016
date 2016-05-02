<?php
namespace AppBundle\Common;

trait DirectoryTrait
{
    protected function getRootDirectory()
    {
        $dir = __DIR__;
        foreach (['src', 'tests' . 'vendor'] as $key) {
            $pos = strpos($dir, DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR);
            if ($pos !== false) {
                $dir = substr($dir, 0, $pos);
            }
        }
        return $dir;
    }
}