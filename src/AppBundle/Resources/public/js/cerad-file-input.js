/* =====================================================
 * This manages file uploads for the team management imports.
 *
 * 11 June 2016
 *
*/

$(document).on("ready", function () {
    
    var btnCust = '<button type="button" class="btn btn-default" title="Test Upload" ' + 
        'onclick="alert(\'Call your custom code here.\')">' +
        '<i class="glyphicon glyphicon-upload"></i>' +
        '<span class="hidden-xs">Test Upload</span>' +
        '</button>';
    
    $("#team-xls-upload").fileinput({
        msgErrorClass: 'alert alert-block alert-danger',
        maxFileCount: 1,
        allowedFileExtensions: ["xls", "xlsx"],
        layoutTemplates: {
            main2: '{remove} ' + btnCust + ' {upload} {browse}',
        }},

        console.log("File uploaded limited to 1 XLS or XLSX")
        );

});

$("#team-xls-upload").on("fileuploaded", function(event, data, previewId, index) {

    alert("#team-import-id.on.fileuploaded");

    var form = data.form, files = data.files, extra = data.extra,
        response = data.response, reader = data.reader;
        
    console.log("#team-import-id.on.fileuploaded");
});
