/* =====================================================
 * This manages file uploads for the team management imports.
 *
 * 11 June 2016
 *
*/

$(document).ready(function() {
    var btnCust = '<button type="submit" name="file-input-test" class="btn btn-default file-input-test file-input-test-button" title="Test Upload" data-toggle="modal" data-target="#modalTestSuccess"><i class="glyphicon glyphicon-upload"></i><span class="hidden-xs">Test Upload</span></button>';

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

})
