/*jslint white:true */
var gotofritz = gotofritz || {};
gotofritz.view = (function () {
  "use strict";
  var
      state  = "view has run"
/**
 * @class the app's only view
 * @author gotofritz
 * @version 0.1.0
 * @since 0.1.0
 */
    , view = {        
          /**
           * returns a test string
           * @return {String} a test string
           */
          test : function(){
              return state;
          }
      };
        
  return view;
}());