<?php
namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Action\RegTeam\RegTeamFileReader;

class RegTeamUploadForm extends AbstractForm
{
    private $conn;
    private $reader;

    public function __construct(
        Connection $conn
    ) {
        $this->conn           = $conn;
        
        $this->reader = new RegTeamFileReader;
    }
    public function handleRequest(Request $request)
    {
        
        if (!$request->isMethod('POST')) return;

        $this->isPost = true;

        $params = $request->request->all();

        $file_name = $_FILES['team-xls-upload']['name'];
        $file_loc = $_FILES['team-xls-upload']['tmp_name'];
        $file_size = $_FILES['team-xls-upload']['size'];
        $file_type = $_FILES['team-xls-upload']['type'];
        
        $folder = __DIR__.'/uploads/';
                
        $errors = [];
        $messages = [];
        
        $this->formDataErrors = [];
        $this->formDataMessages = [];

        if (!move_uploaded_file($file_loc,$folder.$file_name) ) {
            $errors['upload'][] = [
                'name' => 'upload',
                'msg'  => 'Error uploading file ' . $file_name
            ];                
        }
        if (isset($params['file-input-test'])){
                
            $request->request->set('isTest', 'yes');

            $dataArray = $this->reader->fileToArray($folder.$file_name);

            if (!empty($dataArray)) {
                $messages['test'][] = [
                    'name' => 'test',
                    'msg'  => "The file successfully has been tested for import."
                    ];
            } else {
                $errors['test'][] = [
                    'name' => 'test',
                    'msg'  => "The file import failed."
                    ];
            }

        } else {
            $messages['import'][] = [
                'name' => 'import',
                'msg'  => "The file $file_name has been successfully imported."
                ];
            $request->request->remove('isTest');
            //do processing here
        }

        $this->formDataErrors = $errors;
        $this->formDataMessages = $messages;
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

<form id="file-upload-form" role="form" class="form-inline" action="submit" method="post" enctype="multipart/form-data">
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
