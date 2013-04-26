<?php

/*
  Plugin Name: Some API
  Version: 0.1
  Plugin URI: http://voceplatforms.com/
  Description: Interfaces Stuff
  Author: Voce Platforms
  Author URI: http://voceplatforms.com/
 */

namespace WP_API;

if ( !defined( 'WP_API_BASE' ) )
	define( 'WP_API_BASE', '/wp_api' );

function api_base_url() {
	return home_url( user_trailingslashit( WP_API_BASE ) );
}

class API_Dispatcher {

	public function __construct() {
		//if requested url starts with api_base_url()
		//add_action('wp_loaded', array($this, 'dispatch_api'));
		if ( false !== strpos( $_SERVER['REQUEST_URI'], WP_API_BASE ) ) {
			add_action( 'wp_loaded', array( $this, 'dispatch_api' ) );
		}
	}

	public function dispatch_api() {
		//determine API version
		require_once('lib/Slim/Slim.php');

		\Slim\Slim::registerAutoloader();

		$app = new \Slim\Slim();

		$app->get(WP_API_BASE . '/v1/math/add/', function() {
			echo 1;
		});

		$app->get(WP_API_BASE . '/v2/math/add/', function() {
			echo 2;
		});

		$app->run();
	}

}

new API_Dispatcher();