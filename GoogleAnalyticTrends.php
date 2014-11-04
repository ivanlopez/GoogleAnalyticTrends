<?php

namespace TenUp\GoogleAnalyticTrends;

/**
 * Class GoogleAnalyticTrends
 * @package TenUp\GoogleAnalyticTrends
 */
class GoogleAnalyticTrends {

	/**
	 * Instance of the Google_Client Class.
	 *
	 * @var Google_Client
	 */
	protected $client;

	/**
	 * Instance of the Google_Service_Analytics Class.
	 *
	 * @var  Google_Service_Analytics
	 */
	protected $analytics;

	/**
	 * Place holder.
	 */
	function __construct() {
	}

	/**
	 * Return a singleton instance of the class.
	 *
	 * @return GoogleAnalyticTrends
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 *  Make sure constants are setup before bootstrapping the
	 *  Google API
	 */
	public function setup() {
		if ( ! defined( 'GAT_CLIENT_ID' ) ) {
			trigger_error( "GAT_CLIENT_ID must be set to use this library", E_USER_ERROR );
		}
		if ( ! defined( 'GAT_SERVICE_ACCOUNT_NAME' ) ) {
			trigger_error( "GAT_SERVICE_ACCOUNT_NAME must be set to use this library", E_USER_ERROR );
		}
		if ( ! defined( 'GAT_KEY_PATH' ) ) {
			trigger_error( "GAT_KEY_PATH must be set to use this library", E_USER_ERROR );
		}

		if ( ! defined( 'GAT_VIEW_ID' ) ) {
			trigger_error( "GAT_VIEW_ID must be set to use this library", E_USER_ERROR );
		}

		//Load Google library
		require_once 'vendor/google/apiclient/src/Google/Client.php';
		require_once 'vendor/google/apiclient/src/Google/Service/Analytics.php';
		GoogleAnalyticTrends::factory()->bootsrapp_api();
	}

	/**
	 * Establish a connection to the Google API.
	 */
	protected function bootsrapp_api() {

		self::factory()->client = new \Google_Client();
		$client                 = self::factory()->client;

		/**
		 * Filter the application name.
		 *
		 * @param string .
		 */
		$client->setApplicationName( apply_filters( 'gat_app_name', 'GoogleAnalyticTrends' ) );

		/**
		 * Filter the API redirect URL.
		 *
		 * @param string .
		 */
		$client->setRedirectUri( apply_filters( 'gat_redirect', get_site_url( get_current_blog_id() )  . '/oauth2callback' ) );


		/**
		 * Filter the client secret.
		 *
		 * @param string .
		 */
		$client->setClientSecret( apply_filters( 'gat_client_secret', 'notasecret' ) );

		$client->setAssertionCredentials(
			new \Google_Auth_AssertionCredentials(
				GAT_SERVICE_ACCOUNT_NAME,
				array( 'https://www.googleapis.com/auth/analytics' ),
				file_get_contents( GAT_KEY_PATH )
			)
		);

		$client->setClientId( GAT_CLIENT_ID );
		$client->setAccessType( 'online' );
		self::factory()->analytics = new \Google_Service_Analytics( $this->client );
	}

	/**
	 * Retrieve an array of the most viewed post
	 *
	 * @param int $limit The number of post to return
	 *
	 * @return array
	 */
	public function get_popular_post( $limit = 5, $cache = true ) {

		/**
		 * Filter the number of results returned from the Google API.
		 *
		 * @param int .
		 */
		$max_results = apply_filters( 'gat_max_popular_results', 50 );

		$params = array(
			'dimensions'  => 'ga:pagePath',
			'metrics'     => 'ga:pageviews,ga:uniquePageviews,ga:timeOnPage,ga:bounces,ga:entrances,ga:exits',
			'sort'        => '-ga:pageviews',
			'max-results' => $max_results
		);

		/**
		 * Filter how many days back to get results from the Google API.
		 *
		 * @param int .
		 */
		$days_back = apply_filters( 'gat_popular_past_days', 7 );

		$data = self::factory()->analytics->data_ga->get( 'ga:' . GAT_VIEW_ID, date( 'Y-m-d', strtotime( '-' . $days_back . ' days' ) ), date( 'Y-m-d', strtotime( 'now' ) ), 'ga:pageviews', $params );

		print_r($data);

		return self::factory()->parse_data( $data, $limit );
	}

	/**
	 * Retrieve an array of the most post with a triggered Google event
	 *
	 * @param string $action the Google event action
	 * @param int    $limit  The number of post to return
	 *
	 * @return array
	 */
	public function get_post_by_event( $action, $limit = 5, $cache = true ) {

		/**
		 * Filter the number of results returned from the Google API.
		 *
		 * @param int .
		 */
		$max_results = apply_filters( 'gat_max_event_results', 50 );

		$params = array(
			'dimensions'  => 'ga:pagePath,ga:eventAction',
			'metrics'     => 'ga:totalEvents',
			'filters'     => 'ga:eventAction==' . $action,
			'sort'        => '-ga:totalEvents',
			'max-results' => apply_filters( 'gat_max_results', 50 )
		);

		/**
		 * Filter how many days back to get results from the Google API.
		 *
		 * @param int .
		 */
		$days_back = apply_filters( 'gat_event_past_days', 7 );

		$data = self::factory()->analytics->data_ga->get( GAT_VIEW_ID, date( 'Y-m-d', strtotime( '-' . $days_back . ' days' ) ), date( 'Y-m-d', strtotime( 'now' ) ), 'ga:totalEvents', $params );

		return self::factory()->parse_data( $data, $limit );
	}

	/**
	 * Parses the data returned from the Google API. Searching for each post
	 * by its url and making sure its only returning the declared post types
	 *
	 * @param  Google_Service_Analytics_GaData $data
	 * @param int                              $limit The number of post to return
	 *
	 * @return array
	 */
	protected function parse_data( $data, $limit = 5 ) {
		$posts    = array();
		$post_ref = array();
		if ( $data ) {
			foreach ( $data->rows as $read_data ) {
				$post_id = url_to_postid( home_url( $read_data[0] ) );

				if ( in_array( get_post_type( $post_id ), apply_filters( 'gat_post_type', array( 'post' ) ) ) ) {
					if ( count( $posts ) < $limit ) {
						$post = get_post( $post_id );
						if ( ! in_array( $post->post_title, $post_ref ) ) {
							$post_ref[] = $post->post_title;
							$posts[]    = $post->ID;
						}
					}
				}
			}
			//Redundant check to make sure that the number of returned post is no greater than the $limit
			if ( count( $posts ) > $limit ) {
				$posts = array_slice( $posts, 0, $limit );
			}
		}

		return $posts;
	}
}