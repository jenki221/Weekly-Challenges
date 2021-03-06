<?php
/**
 * jsonToGoogleTasksManager
 * reads a json file and turns it into Google Tasks Lists
 */
class jsonToGoogleTasksManager {
    private $client;
    public $service;
    private $_tasklist;
    private $_oldTaskLists = array();   //tasklists currently in GTasks
    private $_newTaskLists = array();   //tasklists we are creating
    private $ignoreList = array( "Default List" );  //blacklist of tasklist names
    private $options = array();
//
/**
 * constructor
 * initializes google-api-php client
 * @param {String} config path to config file to load
 */
    function __construct( $config=false ){
        $config = $config? $config: dirname( __FILE__ ) . "/config.properties";
        include_once dirname( __FILE__ ) . '/../_lib/google-api-php-client/src/apiClient.php';
        include_once dirname( __FILE__ ) . '/../_lib/google-api-php-client/src/contrib/apiTasksService.php';
        global $apiConfig;

        //some default data
        $apiConfig['oauth2_redirect_uri'] ='urn:ietf:wg:oauth:2.0:oob';
        $apiConfig['authClass'] ='apiOAuth2';
        //gets the remaining config data from file
        if( file_exists( $config ) ){
            $options_file = file( $config );
            foreach( $options_file as $option ){
                $optionpair = explode( "=", trim($option) );
                if( 2 != sizeof( $optionpair ) ){
                    continue;
                }
                $k = trim($optionpair[0]);
                $v = trim($optionpair[1]);
                //some options are required by google, the rest are kept in options
                if( 0 === strpos( $k, "oauth2_" ) ){
                    $apiConfig[$k] = $v;
                } else {
                    $this->options[$k] = $v;
                 }
            }
        }
        $this->client = new apiClient();
        $this->service = new apiTasksService($this->client);
        $this->client->setScopes(array(
          'https://www.googleapis.com/auth/buzz',
          'https://www.googleapis.com/auth/latitude',
          'https://www.googleapis.com/auth/moderator',
          'https://www.googleapis.com/auth/tasks',
          'https://www.googleapis.com/auth/siteverification',
          'https://www.googleapis.com/auth/urlshortener',
        ));
    }
/**
 * authorize
 * runs google's oauth authentication
 * @return {Object} accessToken
 */
    public function authorize(){
        $authUrl = $this->client->createAuthUrl();
        //quick os x check
        if( file_exists( "/Users/" ) ) {
            system( "open \"$authUrl\"");
        }    else {
            fwrite( STDOUT,  "Please visit:\n$authUrl\n\n" );
        };
        fwrite( STDOUT,  "Please enter the auth code:\n" );
        $authCode = trim(fgets(STDIN));
        $_GET['code'] = $authCode;
        $accessToken = $this->client->authenticate();

        return $accessToken;
    }


/**
 * fetchCouchDBView
 * gets a view
 * @param {String} url the adres of the view
 * @param {String} method one of the REST HTTP method, default GET
 * @param {String} post_data
 * @param {String} host the host without protocol, default localhost
 * @param {String} port defualt 5984
 * @return {String} a list of tasks as JSON data
 */
    public function fetchCouchDBView( $url="", $method="GET", $post_data=false, $host="localhost", $port="5984" ){
      fwrite( STDOUT, "Connecting to CouchDB.. $url \n" );
      $s = fsockopen( $host, $port, $errno, $errstr);
      if(!$s) {
         echo "$errno: $errstr\n";
         return false;
      }
      $request = "$method $url HTTP/1.0\r\nHost: $host\r\n";
      if($post_data) {
         $request .= "Content-Length: ".strlen($post_data)."\r\n\r\n";
         $request .= "$post_data\r\n";
      }
      else {
         $request .= "\r\n";
      }
      fwrite($s, $request);
      $response = "";
      while(!feof($s)) {
         $response .= fgets($s);
      }
      list( $headers, $body ) = explode("\r\n\r\n", $response);
      return $body;
    }


/**
 * loadDataFromCouchDB
 * loads a hard coded data structure into object property _tasklistJSON
 */
    public function loadDataFromCouchDB(){
		$json = json_decode( $this->fetchCouchDBView( $this->options[ "view" ] ) );
		if( JSON_ERROR_NONE != json_last_error() ){
        	throw "Incorrect JSON " . json_last_error();
       	}

        $transformed = array();
       	for( $i=0,$i2=sizeof( $json->{"rows"} ); $i<$i2; $i ++ ){
       	    $row = $json->{"rows"}[$i];
       	    $tasklist = array( "key" => strtoupper( $row->{"key"} ), "value" => array() );
       	    foreach( $row->{"value"} as $k=>$v ){
                $tasklist["value"][$k] = $v;
       	    }
            array_push( $transformed, $tasklist );
       	}
        $this->_tasklist = $transformed;
		return $transformed;
    }

/**
 * __get
 * overloads inacessible properties
 */
    public function __get( $property ){
        if( "tasks" === $property ){
            return $this->_tasklist;
        }
    }

/**
 * makeEmptyTaskListIfNeeded
 * loads a hard coded data structure into object property _tasklist
 * $param {String} $title the tasklist name
 */
    public function makeEmptyTaskListIfNeeded( $title="false" ){
        if( !$title ){
            return;
        }
        if( in_array( $title, $this->ignoreList ) ){
            fwrite( STDOUT, "Cannot create a tasklist of name '$title'" );
            return;
        }
        //have we already created it?
        if( array_key_exists( $title, $this->_newTaskLists ) ){
            return;
        }
        $this->deleteTasklistIfExists( $title );

        $newlist = new TaskList();
        $newlist->setTitle( $title );

        $tries = 20;
        while( $tries-- ){
            try{
                $newlist = $this->service->tasklists->insert( $newlist );
                $this->_newTaskLists[$title]    = $newlist["id"];
                $tries = 0;
            } catch( Exception $e ){
                if( 0 === $tries ){
                    die ( "Tried 20 times to create tasklist '$title' and failed" );
                } else {
                    sleep( 5 );
                }
            }
        }
    }

/**
 * deleteTasklistIfExists
 * $param {String} $title the tasklist name
 */
    public function deleteTasklistIfExists( $title="false" ){
        if( !$title ){
            return;
        }
        if( !array_key_exists( $title, $this->_oldTaskLists ) ){
            return;
        }
        foreach( $this->_oldTaskLists[$title] as $id ){
            $this->service->tasklists->delete( $id );
            unset( $this->_oldTaskLists[$title] );
        }
    }

/**
 * addTaskToList
 * $param {String} $title the tasklist name
 */
    public function addTaskToList( $tasklistTitle="false", $taskData=false ){
        if( !$tasklistTitle or !$taskData
            or !array_key_exists( $tasklistTitle, $this->_newTaskLists )
            or !is_array( $taskData )
            or !array_key_exists( "title", $taskData )
            ){
            return;
        }
        $taskObj = new Task();
        $taskObj->setTitle( $taskData["title"] );
        if( array_key_exists( "updated", $taskData ) ){
            $taskObj->setUpdated( $taskData["updated"] );
        }
        if( array_key_exists( "notes", $taskData ) ){
            $taskObj->setNotes( $taskData["notes"] );
        }
        if( array_key_exists( "due", $taskData ) ){
            $taskObj->setDue( $taskData["due"] );
        }
        $this->service->tasks->insert( $this->_newTaskLists[$tasklistTitle], $taskObj );
    }



/**
 * loadOldTasklists
 * gets a list of taskslists from Google Tasks and creates a map of titles/id
 * ignores Default List because that can't be deleted
 */
    public function loadOldTasklists(){
        $tasklists = $this->service->tasklists->listTasklists();
        $tasklists = $tasklists["items"];
        $this->_oldTaskLists = array();

		for( $i=0,$i2=sizeof( $tasklists ); $i<$i2; $i++ ){
		    $tasklist = $tasklists[$i];
		    $title = $tasklist["title"];

		    //allows for multiple tasklists with the same name - it happens a lot during development
		    if( !in_array( $title, $this->ignoreList ) ){
		        if( !array_key_exists( $title, $this->_oldTaskLists ) ){
                    $this->_oldTaskLists[$title] = array();
		        }
    		    array_push( $this->_oldTaskLists[$title], $tasklist["id"] );
    		}
		}
    }

}
?>
