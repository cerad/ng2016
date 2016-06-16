<?php
namespace AppBundle\Action\Game\Import;

use AppBundle\Action\AbstractForm;

use Symfony\Component\HttpFoundation\Request;

class GameImportForm extends AbstractForm
{
    public function handleRequest(Request $request)
    {
        if (!$request->isMethod('POST')) return;
        
        $this->isPost = true;
        
        $data = $request->request->all();
        $errors = [];

        $file = $request->files->has('file') ? $request->files->get('file') : null;
        if (!$file) {
            $errors[] = 'Missing File';
        }
        dump($file);
        
        $this->formData = array_replace($this->formData,[
            'op'   => $this->filterScalarString($data,'op'),
            'file' => $file,
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $op = $this->formData['op'];
        $opChoices = [
            'verify' => 'Verify',
            'update' => 'Update',
        ];
        $action = $this->generateUrl($this->getCurrentRouteName());
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" style="width: 1200px;" action="{$action}" method="post" enctype="multipart/form-data">
  <div class="form-group">
    <label for="op">Operation</label>
    {$this->renderInputSelect($opChoices,$op,'op')}
  </div>
  <div class="form-group">
    <label for="file">Excel File</label>
    <input type="file" name="file" id="file" required>
  </div>
  <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
  <button type="submit" class="btn btn-sm btn-primary submit">
    <span class="glyphicon glyphicon-search"></span> 
    <span>Import</span>
  </button>
</form>
<br/>
EOD;
        return $html;
    }
}