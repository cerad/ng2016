/* ===========================================================
 * Group all my js functions into the Cerad namespace
 */
Cerad = {};

Cerad.alert = function(msg)
{
    alert('A Cerad Alert: ' + msg);
};
/* =====================================================
 * This turns all the checkboxes in a given named group
 * On or Off
 * 31 May 2014
 * 
 * This was developed under jquery 1.8.3
 * I used attr('checked') to determine if the checkbox was checked
 * Turns out that this was changed way back in 1.6.
 * prop shuld be used.
 * 
 * This function stopped working when upgrading to jquery 1.10
 * Changed attr to prop and all was well
 * 
 * Also at one point had names like refSchedSearchData[ages][All]
 * Now we get form[ages][]
 * Not sure if having elements with the same name is good.
 * Need to look at
 * 
 * 23 June 2014
 * Checking the all box when the rest of the boxes are unchecked should have caused them to be checked
 * Stupid error.  Still used attr when checking the boxes.
 *
 * 5 Apr 2016
 * Fixed the checkbox call.  JS library change?
 * 
 */

$('.cerad-checkbox-all').change( function(e)
{   
    var nameRoot = $(this).attr('name');
        
    nameRoot = nameRoot.substring(0,nameRoot.lastIndexOf('['));
    
    var group = 'input[type=checkbox][name^="' + nameRoot + '"]';
    
    var checked = $(this).prop('checked') ? true : false;
        
    $(group).prop('checked', checked);
});