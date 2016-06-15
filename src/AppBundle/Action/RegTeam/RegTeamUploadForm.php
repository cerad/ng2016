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
                    
                $request->request->set('isTest', 'yes');
                
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
    
                $request->request->remove('isTest');
    
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
<style>
.file-input-test-button { display: none; }
</style>

<form id="file-upload-form" role="form" class="form-inline" action="{$this->generateUrl("regteam_import")}" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
        <label class="control-label">Choose a file to upload</label>
        <input id="team-xls-upload" type="file" name="team-xls-upload" class="form-control input-file file file-loading"  >
    </div>
</form>
<script>
    var btnCust = '<button type="submit" name="file-input-test" class="btn btn-default file-input-test file-input-test-button" title="Test Upload" data-toggle="modal" data-target="#modalTestSuccess">' +
        '<i class="glyphicon glyphicon-upload"></i><span class="hidden-xs">Test Upload</span>' +
        '</button>';

    $('#team-xls-upload').fileinput({
        allowedFileExtensions: ["xls", "xlsx"],
        maxFileCount: 1,
        showCaption: false,
        elErrorContainer: '#file-input-upload-errors',
        msgErrorClass: 'alert alert-block alert-danger',
        uploadAsync: false,
        layoutTemplates: {
            main2: '{preview} {remove}' + btnCust + '{upload} {browse}'
        },
        }).on('change', function(e) {
            console.log('File changed');
        }).on('fileuploaded', function(e, params) {
            console.log('File uploaded');
        }).on('fileselect', function(e) {
            $(".file-input-test-button").css("display","inline");
            console.log('File selected');            
        }).on('filecleared', function(e) {
            $(".file-input-test-button").css("display","none");
            console.log('File cleared');            
        }).on('fileerror', function(e) {
            $(".file-input-test-button").css("display","none");
            console.log('File error');            
        });
    
</script>

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
