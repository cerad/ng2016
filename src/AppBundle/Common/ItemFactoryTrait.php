<?php
namespace AppBundle\Common;

trait ItemFactoryTrait
{
    private function loadFromArray($data)
    {
        foreach ($this->keys as $key => $type) {
            if (isset($data[$key])) {
                $this->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            } else if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }
        return $this;
    }
}
