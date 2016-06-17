<?php
namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Action\RegTeam\RegTeamFileReader;
use Symfony\Component\Filesystem\Filesystem;

class RegTeamUploadForm extends AbstractForm
{
    /*  var RegTeamFileReader */
    private $importer;

    public function __construct(
        RegTeamFileReader $importer
    ) {
        $this->importer = $importer;
    }
    public function handleRequest(Request $request)
    {
        
        if (!$request->isMethod('POST')) return;

        $this->isPost = true;

        $params = $request->request->all();

        $file = $request->files->get('team-xls-upload');

        $inFilename = $file->getClientOriginalName();
        
        $fs = new Filesystem();

        $errors = [];
        $messages = [];
        
        $this->formDataErrors = [];
        $this->formDataMessages = [];
        
        //error if not successful upload
        if (!$fs->exists($file) ) {
            $errors['upload'][] = [
                'name' => 'upload',
                'msg'  => 'Error uploading file ' . $file_name
            ];                
        } else {
        
            //get the data from the file as array
            $dataArray = $this->importer->fileToArray($file);
    
            //see if was test button or upload button
            if (isset($params['file-input-test'])){
                    
                $request->attributes->set('isTest', 'yes');
                
                $this->setData(array());
    
                if (!empty($dataArray)) {
                    $messages['test'][] = [
                        'name' => 'test',
                        'msg'  => "$inFilename has been successfully tested for import."
                        ];
                } else {
                    $errors['test'][] = [
                        'name' => 'test',
                        'msg'  => "The file import failed."
                        ];
                }
    
            } else {  //upload button
    
                $request->attributes->remove('isTest');
    
                $messages['import'][] = [
                    'name' => 'import',
                    'msg'  => "$inFilename has been successfully imported."
                    ];
    
                $this->setData($dataArray);
            }
        }
        
        $this->formDataErrors = $errors;
        $this->formDataMessages = $messages;
        
        $fs->remove($file);
    }
    public function render()
    {
        $html = $this->renderUploadForm();

        return $html;
    }
    private function renderUploadForm()
    {
        $csrfToken = 'TODO';

        $html = <<<EOD
{$this->renderFormErrors()}
{$this->renderFormMessages()}
<hr>
<div id="file-input-upload-errors" class="center-block" style="width:800px;display:none"></div>

<form id="file-upload-form" role="form" class="form-inline" action="{$this->generateUrl("regteam_import")}" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
        <label class="control-label">Choose a file to upload</label>
        <input id="team-xls-upload" type="file" name="team-xls-upload" class="form-control input-file file-loading"  >
    </div>
</form>
<br/>
EOD;
        
        return $html;
    }
    public function renderMessages()
    {
        $html = $this->renderFormErrors() . $this->renderFormMessages();

        return $html;
    }
}
