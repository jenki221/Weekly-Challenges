var masterKey = "0Avx75VC2FEmAdEVmWl9uQkRuVjlxc0tmVDFSQ2wtaFE"
  , thisVersion = "0.1.0"
  , thisID = "script"
  ;

//note that these need to be above the onOpen function below
function menu1(){ if( gotofritz.utils.isUpToDate( masterKey, thisID, thisVersion ) ){ ns.menu1(); } }
function menu2(){ if( gotofritz.utils.isUpToDate( masterKey, thisID, thisVersion ) ){ ns.menu2(); } }
/*
* onOpen
* creates script menus
*/
function onOpen() {
    //the Google servers are in California, and sometime timezone get all messed up
    //trying to remedy taht
    SpreadsheetApp.getActiveSpreadsheet().setSpreadsheetTimeZone( "GMT" );

    //this will hold the meny entrie
    var menuEntries = [];
    menuEntries.push({name: "Entry 1", functionName: "menu1"});
    //a null entry shows a divider
    menuEntries.push(null);
    menuEntries.push({name: "Entry 2", functionName: "menu2"});

    //create the menu
    SpreadsheetApp.getActiveSpreadsheet().addMenu( "MY MENU", menuEntries );
}

var ns = (function(){

// =========================================================
// PROPERTIES
// =========================================================
  var spr  =  SpreadsheetApp.getActiveSpreadsheet()      //shortcut
    
    /**
     * VAR NAMING CONVENTION
     * r1xxx = row for range (where first cell has ref 1,1)
     * c1xxx = column for range (where first cell has ref 1,1)
     * r0xxx = row for js arrays (where first cell has ref 0,0)
     * c0xxx = column js arrays (where first cell has ref 0,0)
     * rc1xx = a pair of range coordinates
     * rc0xx = a pair of js coordinates
     */

    // RANGE INDICES
    , r1Start = 1  //starting Row
    , c1Start = 1  //starting column

    // JS INDICES - as typically returned from range.getValues()
    , r0End = spr.getLastRow() - 1
    , c0End = spr.getLastColumn() - 1

    //pairs of points
    , rc1Start = [ 1, 1 ]  //where the value of Page name is
    ;



// =========================================================
// PUBLIC METHODS
// =========================================================

    var facade = {


      /**
       * menu1
       * only doing JavaDoc from habit
       */
      menu1 : function(){
        Browser.msgBox( "Entry 1 works");
      }


      /**
       * menu2
       * only doing JavaDoc from habit
       */
     , menu2 : function(){
        Browser.msgBox( "Entry 2 works too");
      }

  }
  return facade;

 })();




var gotofritz = gotofritz || {}
gotofritz.utils = (function(){

    //before checking other scripts, I need to check this object itself is up to date
    var utilsMasterKey = "0Avx75VC2FEmAdEVmWl9uQkRuVjlxc0tmVDFSQ2wtaFE"
      , utilsVersion = "0.1.0"
      , utilsID = "gotofritz.utils"
      , facade //wraps public nethods
      ;

    facade = {
      /**
       * isUpToDate
       * checks a version number against one in a master document
       * @param {String} masterKey the key of the master document
       * @param {String} thisID a label for this script to allow us to find it in the master spreadsheet
       * @param {String} thisVersion the semantic versioning number
       * @return Booelan whetner thisVersion is loewer or equal than the one in the master document
       */
      isUpToDate : function( masterKey, thisID, thisVersion ){

          var shMaster = SpreadsheetApp.openById( masterKey ).getActiveSheet()
            , coords = facade.indexOf( thisID, shMaster )
            , latestVersion
            , aLatestVersion, aThisVersion  //used to compare the infividual components A.B.C in the version number
            , found = false
            , errorMsg = ""
            ;
          if( -1 === coords[0] ){
            errorMsg = "Could not compare " + thisID + " with master version: label not found";
          } else {
            latestVersion = shMaster.getRange( coords[0], 1+coords[1] ).getValue();
            if( "" !== latestVersion ){
              aLatestVersion = String( latestVersion ).split( "." );
              aThisVersion = String( thisVersion ).split( "." );
              if( 3 === aLatestVersion.length && 3 === aThisVersion.length ){
                if( ( +aLatestVersion[0] <= +aThisVersion[0] ) && ( +aLatestVersion[1] <= +aThisVersion[1] )  && ( +aLatestVersion[2] <= +aThisVersion[2] ) ){
                  found = true;
                } else {
                  errorMsg = "Your version is out of date. Please update "+thisID +" from the master spreadsheet";
                }
              } else {
                errorMsg = "Version number in wrong format. Master:" + latestVersion +", this: " + thisVersion;
              }
            } else {
              errorMsg = "Could not find a version number for " + thisID;
            }
          }
          if( !found ){
            Browser.msgBox( errorMsg );
          }
          return found;
      }

    /**
     * _indexOf
     * @private
     * goes through a sheet, doing each row. It can be limited to a range ( by rows and by cell) and finds the first occurrence of a value
     * @param {String} findme the value to find
     * @param {Sheet} shFindme the sheet with the range
     * @param {Number} rFindmeFrom the range in which to search, default 1
     * @param {Number} cFindmeFrom the range in which to search, default 1
     * @param {Number} cFindmeTo the range in which to search, default getLastRow
     * @param {Number} cFindme the range in which to search, default getLastColumn
     * @return {Array} the row and column indexes of the cell, -1, -1 if not found
     */
      ,indexOf : function  ( findme, shFindme, rFindmeFrom, cFindmeFrom, rFindmeTo, cFindmeTo ){
        var r, c  //coords
          , r2, c2 //loop bounds
          , grid  //gets the range as js multi array
          ;
        if( !shFindme ){
          return [ -1, -1 ];
        }
        rFindmeFrom || ( rFindmeFrom = 1 );
        cFindmeFrom || ( cFindmeFrom = 1 );
        rFindmeTo || ( rFindmeTo = shFindme.getLastRow() );
        cFindmeTo || ( cFindmeTo = shFindme.getLastColumn() );

        grid = shFindme.getRange( rFindmeFrom, cFindmeFrom, rFindmeTo, cFindmeTo ).getValues();
        for( r = 0, r2 = grid.length; r < r2; r++ ){
          for( c = 0, c2 = grid[r].length; c < c2; c++ ){
            if( grid[r][c] === findme ){
              return [ r+rFindmeFrom, c+cFindmeFrom ];
            }
          }
        }
        return [ -1, -1 ];
      }

    }

      if( facade.isUpToDate( utilsMasterKey, utilsID, utilsVersion ) ){
          return facade;
      } else {
          return {
            isUpToDate : function(){ Browser.msgBox( "please update gotofritz.utils" ); return false; }
          }
      }


 })();
