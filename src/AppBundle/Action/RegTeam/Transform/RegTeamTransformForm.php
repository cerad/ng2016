<?php
namespace AppBundle\Action\RegTeam\Transform;

use AppBundle\Action\AbstractForm;

use Symfony\Component\HttpFoundation\Request;

class RegTeamTransformForm extends AbstractForm
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
        $this->formData = array_replace($this->formData,[
            'sheet' => $this->filterScalarString($data,'sheet'),
            'file'  => $file,
        ]);
        $this->formDataErrors = $errors;
    }
    public function render()
    {
        $sheet = $this->formData['sheet'];
        $sheetChoices = [
            'U10B' => 'U10B',
            'U10G' => 'U10G',
            'U12B' => 'U12B',
            'U12G' => 'U12G',
            'U14B' => 'U14B',
            'U14G' => 'U14G',
            'U16B' => 'U16B',
            'U16G' => 'U16G',
            'U19B' => 'U19B',
            'U19G' => 'U19G',
        ];
        $action = $this->generateUrl($this->getCurrentRouteName());
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
<form role="form" style="width: 1200px;" action="{$action}" method="post" enctype="multipart/form-data">
  <div class="form-group">
    <label for="sheet">Sheet</label>
    {$this->renderInputSelect($sheetChoices,$sheet,'sheet')}
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
  <a href="{$this->generateUrl('game_listing')}">Back To Game Listing</a>
</form>
<br/>
EOD;
        return $html;
    }
}