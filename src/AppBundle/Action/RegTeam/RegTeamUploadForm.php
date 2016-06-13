<?php
namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractForm;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class RegTeamUploadForm extends AbstractForm
{
    private $conn;

    public function __construct(
        Connection $conn
    ) {
        $this->conn           = $conn;
    }
    public function handleRequest(Request $request)
    {
        $this->formDataErrors = [];
        $this->formDataMessages = [];
        
        if (!$request->isMethod('POST')) return;
        
        $this->isPost = true;

        $file_name = $_FILES['team-xls-upload']['name'];
        $file_loc = $_FILES['team-xls-upload']['tmp_name'];
        $file_size = $_FILES['team-xls-upload']['size'];
        $file_type = $_FILES['team-xls-upload']['type'];
        
        $folder = __DIR__.'/uploads/';
                
        $errors = [];
        $messages = [];
        
        if (move_uploaded_file($file_loc,$folder.$file_name) ) {
            $messages['upload'][] = [
                'name' => 'upload',
                'msg'  => "The file $file_name has been uploaded for import."
            ];

        } else {
            $errors['upload'][] = [
                'name' => 'upload',
                'msg'  => 'Error uploading file ' . $file_name
            ];
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
<form id="file-upload-form" role="form" class="form-inline" action="submit" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <input type="hidden" name="_csrf_token" value="{$csrfToken}" />
        <label class="control-label">Choose a file to upload</label>
        <input id="team-xls-upload" type="file" name="team-xls-upload" class="form-control input-file file file-loading" data-show-preview="false"  >
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
