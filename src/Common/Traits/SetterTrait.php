<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

trait SetterTrait
{
    public function set(string $key, $value) : self
    {
        $this->$key = $value;
        return $this;
    }
    public function setArray(array $data = []) : self
    {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
}