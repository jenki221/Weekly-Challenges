/*jslint white:true */
var gotofritz = gotofritz || {};
gotofritz.model = (function () {
  "use strict";
  var
      state  = "model has run"
/**
 * @class app business logic
 * @author gotofritz
 * @version 0.1.0
 * @since 0.1.0
 */
    , model = {        
          /**
           * returns a test string
           * @return {String} a test string
           */
          test : function(){
              return state;
          }
      };
        
  return model;
}());