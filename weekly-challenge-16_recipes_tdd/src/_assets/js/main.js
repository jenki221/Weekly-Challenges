/*jslint white:true, evil:true*/
/*global document*/
//= require <modules/module.storage>
//= require <modules/view.crud>
//= require <modules/app.model>

var gotofritz = gotofritz || {};


var app = (function(){
  "use strict";
  
    //PRIVATE
    var name    = "recipes-16"
      , app     = this
      , storage = Object.create( gotofritz.storage )
      , view    = Object.create( gotofritz.view )
      , model   = Object.create( gotofritz.model )
      
    /**
     * The main recipe managemenget app
     * @version 0.1.0
     * @author gotofritz
     */
      , main  = {
            
            /**
             * runs all modules test functions and joins the 
             * @return {String} the list of test strings, separated by a <br>
             */
            test : function(){
                var results = [];                      
                
                [ storage, view, model ].forEach( function( module, i ){
                    results.push( module.test() );
                });
                
                return results.join( '<br/>' );
            }
      };
      return main;
}());
document.write( app.test() + "<br>" );