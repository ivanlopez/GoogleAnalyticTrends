<?php

namespace TenUp\GoogleAnalyticTrends;

class GoogleAnalyticTrends {

	protected $client;

	protected $analytics;

	protected $client_secret = 'notasecret';

	function __construct() {

	}

	public static function factory(){
		static $instance = false;

		if ( ! $instance  ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	public function setup(){
		if( ! defined( 'GAT_CLIENT_ID') ){
			trigger_error("GAT_CLIENT_ID must be set to use this library", E_USER_ERROR);
		}
		if(  ! defined( 'GAT_SERVICE_ACCOUNT_NAME') ){
			trigger_error("GAT_SERVICE_ACCOUNT_NAME must be set to use this library", E_USER_ERROR);
		}
		if( ! defined( 'GAT_KEY_PATH' ) ){
			trigger_error("GAT_KEY_PATH must be set to use this library", E_USER_ERROR);
		}
		require_once __DIR__ . '/vendor/google/apiclient/autoload.php';
		$this->bootsrapp();
	}

	public function bootsrapp(){
		$this->client = new Google_Client();
		$this->client->setApplicationName( 'GoogleAnalyticTrends' );
		$this->client->setRedirectUri( apply_filters('gat_client_secret', get_site_url( get_current_blog_id() ) ) . '/oauth2callback' );
		$this->client->setClientSecret( apply_filters('gat_client_secret', $this->client_secret ) );

		$this->client->setAssertionCredentials(
			new Google_Auth_AssertionCredentials(
				GAT_SERVICE_ACCOUNT_NAME,
				array( 'https://www.googleapis.com/auth/analytics' ),
				file_get_contents( GAT_KEY_PATH )
			)
		);

		$this->client->setClientId( GAT_CLIENT_ID );
		$this->client->setAccessType( 'online' );
		$this->analytics = new Google_Service_Analytics( $this->client );
	}


}