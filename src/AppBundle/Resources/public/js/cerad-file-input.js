/* =====================================================
 * This manages file uploads for the team management imports.
 *
 * 11 June 2016
 *
*/


//
//var observeDOM = (function(){
//    var MutationObserver = window.MutationObserver || window.WebKitMutationObserver,
//        eventListenerSupported = window.addEventListener;
//
//    return function(obj, callback){
//        if( MutationObserver ){
//            // define a new observer
//            var obs = new MutationObserver(function(mutations, observer){
//                if( mutations[0].addedNodes.length || mutations[0].removedNodes.length )
//                    callback();
//            });
//            // have the observer observe foo for changes in children
//            obs.observe( obj, { childList:true, subtree:true });
//        }
//        else if( eventListenerSupported ){
//            obj.addEventListener('DOMNodeInserted', callback, false);
//            obj.addEventListener('DOMNodeRemoved', callback, false);
//        }
//    };
//})();
//
//// Observe a specific DOM element:
//observeDOM( document.getElementById('team-xls-upload') ,function(){ 
//    var btnCust = '<button type="button" class="btn btn-default file-input-test file-input-test-button" title="Test Upload" onclick="alert(\'Call your custom code here.\')">' +
//        '<i class="glyphicon glyphicon-upload"></i><span class="hidden-xs">Test Upload</span>' +
//        '</button>';
//
//    console.log('document.onhaschange');
//    console.log('dom changed');
//});
