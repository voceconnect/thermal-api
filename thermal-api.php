<?php

/*
  Plugin Name: Thermal API
  Version:     0.5.1
  Plugin URI:  http://thermal-api.com/
  Description: The power of WP_Query in a RESTful API.
  Author:      Voce Platforms
  Author URI:  http://voceplatforms.com/
 */

namespace Voce\Thermal;

if ( !defined( 'Voce\Thermal\API_BASE' ) ) {
	define( 'Voce\Thermal\API_BASE', '/wp_api' );
}

function get_api_base() {
	return '/' . trim( API_BASE, '/' ) . '/';
}

function api_base_url() {
	return home_url( get_api_base() );
}

class API_Dispatcher {

	public function __construct() {
		//if requested url starts with api_base_url()
		if ( false !== strpos( $_SERVER['REQUEST_URI'], get_api_base() ) ) {
			add_action( 'wp_loaded', array( $this, 'dispatch_api' ) );
		}
	}

	public function dispatch_api() {
		//determine API version
		require_once( 'lib/Slim/Slim/Slim.php' );
		require_once( 'api/v1/API.php' );

		\Slim\Slim::registerAutoloader();

		$app = new \Slim\Slim();

		$v1 = new APIv1( $app );

		$app->run();

		exit;
	}

}

new API_Dispatcher();