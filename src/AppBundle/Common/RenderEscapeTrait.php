<?php
namespace AppBundle\Common;

trait RenderEscapeTrait
{
  protected function escape($content)
  {
    return htmlspecialchars($content, ENT_COMPAT);
  }
}
