<?php
/**
 * jsonToGoogleTasksManager
 * reads a json file and turns it into Google Tasks Lists
 */
class jsonToGoogleTasksManager {
    private $client;
    public $service;
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
}
?>
