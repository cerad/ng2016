<?php
namespace AppBundle\Common;

trait ArrayAccessTrait
{
  public function offsetSet($offset, $value) {
    $this->$offset = $value;
  }
  public function offsetGet($offset) {
    return $this->$offset;
  }
  public function offsetExists($offset) {
    return isset($this->$offset);
  }
  public function offsetUnset($offset) {
    $this->$offset = null;
  }
}
