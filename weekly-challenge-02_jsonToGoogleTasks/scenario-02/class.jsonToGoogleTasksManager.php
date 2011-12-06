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
                $apiConfig[trim($optionpair[0])] = trim($optionpair[1]);
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
 * loadArrayOfMockTaskData
 * loads a hard coded data structure into object property _tasklist
 */
    public function loadArrayOfMockTaskData(){
        require dirname( __FILE__ ) . '/../_data/mock-02.php';
        $this->_tasklist = $mock;
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
 * makeEmptyTaskList
 * loads a hard coded data structure into object property _tasklist
 * $param {String} $title the tasklist name
 */
    public function makeEmptyTaskList( $title="false" ){
        if( !$title ){
            return;
        }
        if( in_array( $title, $this->ignoreList ) ){
            fwrite( STDOUT, "Cannot create a tasklist of name '$title'" );
            return;
        }
        //have we already created it?
        if( in_array( $title, $this->_newTaskLists ) ){
            return;
        }
        $this->deleteTasklistIfExists( $title );

        $newlist = new TaskList();
        $newlist->setTitle( $title );
        $newlist = $this->service->tasklists->insert( $newlist );
        $this->_newTaskLists[$title]    = $newlist["id"];
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
