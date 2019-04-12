<?php declare(strict_types=1);

namespace Zayso\Fed;

class FedFinder
{
    private $feds;

    public function __construct()
    {
        $this->feds = new Feds(...[
            new Fed('AYSO','American Youth Soccer Organization'),
            new Fed('USSF','United States Something Football'),
            new Fed('NFHS','National Federation High School')
        ]);
    }
    public function find(string $fedId) : ?Fed
    {
        return isset($this->feds[$fedId]) ? $this->feds[$fedId] : null;
    }
}