<?php

namespace TenUp\GoogleAnalyticTrends;

class GoogleAnalyticTrends {

	protected $client;

	protected $analytics;

	protected $client_secret;

	function __construct() { }

	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

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

		if ( ! defined( 'GAT_ACCOUNT' ) ) {
			trigger_error( "GAT_KEY_PATH must be set to use this library", E_USER_ERROR );
		}

		require_once 'vendor/google/apiclient/autoload.php';
		GoogleAnalyticTrends::factory()->bootsrapp();
	}

	protected function bootsrapp() {
		self::factory()->client_secret = apply_filters( 'tag_client_secret', 'notasecret' );
		self::factory()->client = new \Google_Client();
		$client = self::factory()->client;
		$client->setApplicationName( 'GoogleAnalyticTrends' );
		$client->setRedirectUri( apply_filters( 'gat_client_secret', get_site_url( get_current_blog_id() ) ) . '/oauth2callback' );
		$client->setClientSecret( apply_filters( 'gat_client_secret', $this->client_secret ) );

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

	public function get_popular_post( $limit = 5 ){
		$params    = array(
			'dimensions'  => 'ga:pagePath',
			'metrics'     => 'ga:pageviews,ga:uniquePageviews,ga:timeOnPage,ga:bounces,ga:entrances,ga:exits',
			'sort'        => '-ga:pageviews',
			'max-results' => apply_filters( 'gat_max_results', 50 )
		);

		$days_back = apply_filters( 'gat_past_days', 7 );

		$data = self::factory()->analytics->data_ga->get( GAT_ACCOUNT, date( 'Y-m-d', strtotime( '-' . $days_back . ' days' ) ), date( 'Y-m-d', strtotime( 'now' ) ), 'ga:pageviews', $params );
		return self::factory()->parse_data( $data, $limit );
	}

	public function get_post_by_event( $action, $limit = 5 ){
		$params    = array(
			'dimensions'  => 'ga:pagePath,ga:eventAction',
			'metrics'     => 'ga:totalEvents',
			'filters'     => 'ga:eventAction==' . $action,
			'sort'        => '-ga:totalEvents',
			'max-results' => apply_filters( 'gat_max_results', 50 )
		);

		$days_back = apply_filters( 'gat_past_days', 7 );

		$data = self::factory()->analytics->data_ga->get( GAT_ACCOUNT, date( 'Y-m-d', strtotime( '-' . $days_back . ' days' ) ), date( 'Y-m-d', strtotime( 'now' ) ), 'ga:totalEvents', $params );
		print_r($data);
		return self::factory()->parse_data( $data, $limit );
	}

	protected function parse_data( $data, $limit = 5 ) {
		$posts    = array();
		$post_ref = array();
		if( $data ){
			foreach ( $data->rows as $read_data ) {
				$post_id = url_to_postid( home_url( $read_data[0] ) );

				if ( in_array( get_post_type( $post_id ), apply_filters( 'gat_post_type', array( 'post' ) ) ) ) {
					if ( count( $posts ) < $limit ) {
						$post = get_post( $post_id );
						if ( ! in_array( $post->post_title, $post_ref ) ) {
							$post_ref[] = $post->post_title;
							$posts[]    = $post;
						}
					}
				}
			}

			if ( count( $posts ) > $limit ) {
				$posts = array_slice( $posts, 0, $limit );
			}
		}

		return $posts;
	}
}