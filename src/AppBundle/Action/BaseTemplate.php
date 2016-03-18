<?php
namespace AppBundle\Action;

class BaseTemplate extends AbstractTemplate
{
    protected $title = 'NG2016';
    protected $content = null;

    public function setContent($content)
    {
        $this->content = $content;
    }
    public function render()
    {
        return <<<EOT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->escape($this->title)}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="/css/normalize.css" media="all" />
    <link rel="stylesheet" type="text/css" href="/css/zayso.css" media="all" />
  </head>
  <body>
    <div id="layout-body">
      <div id="layout-content">
{$this->content}
      </div>
    </div>
{$this->renderJavascripts()}
  </body>
</html>
EOT;
    }
    /* ====================================================
     * Maybe implement blocks later
     */
    protected function renderStylesheets()
    {
        return null;
    }
    protected function renderJavascripts()
    {
        return null;
    }
    public function addStylesheet() {}
    public function addJavascript() {}
}