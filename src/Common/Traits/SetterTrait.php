<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

/*
 * Basically a war to update readonly properties
 * The assumption here is that data being passed is always the correct type
 * And that the keys are always valid
 *
 * TODO Too many assumptions! make this go away
 */
trait SetterTrait
{
    public function set(string $key, $value) : self
    {
        $this->$key = $value;
        return $this;
    }
    public function setFromArray(array $data = []) : self
    {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
}