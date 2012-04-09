/*jslint white:true */
var gotofritz = gotofritz || {};
gotofritz.storage = (function () {
  "use strict";
  var
      state  = "storage has run"
/**
 * @class writes to / reads from permanent storage
 * @author gotofritz
 * @version 0.1.0
 * @since 0.1.0
 */
    , storage = {        
          /**
           * returns a test string
           * @return {String} a test string
           */
          test : function(){
              return state;
          }
      };
        
  return storage;
}());