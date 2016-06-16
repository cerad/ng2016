/* =====================================================
 * This turns toggles the color of the selection yes/no
 * selection boxes on project/person/admin/update/ to update
 * status colors dynamically on change.
 *
 * 11 June 2016
 *
*/

function updateSelectClass(obj,id) { 

	var $dropdown = $(obj);
	var key = $dropdown.val();

	switch (key) {
        case 'MY2016':
        case 'MY2015':
        case 'yes':
            document.getElementById(id).setAttribute( "class", "col-xs-4 form-control bg-success" );
            break;
        case 'nr':
            document.getElementById(id).setAttribute( "class", "col-xs-4 form-control" );
            break;
        default:
            document.getElementById(id).setAttribute( "class", "col-xs-4 form-control bg-danger" );
    }
}

$("#userSH").change(function() { 
    updateSelectClass(this, 'userSH'); 
});

$("#userConc").change(function () {
    updateSelectClass(this,'userConc'); 
});

$("#userBackground").change(function () {
    updateSelectClass(this, 'userBackground'); 
});

$("#regYear").change(function() {
    updateSelectClass(this, 'regYear'); 
});

