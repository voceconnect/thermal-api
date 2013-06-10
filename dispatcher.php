<?php
namespace Voce\Thermal;

if ( !defined( __NAMESPACE__ . '\\API_BASE' ) ) {
	define( __NAMESPACE__ . '\\API_BASE', '/wp_api' );
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
		require_once( 'lib/jsonp/jsonp.php' );

		require_once( 'api/v1/API.php' );

		\Slim\Slim::registerAutoloader();

		$app = new \Slim\Slim();

		new \Voce\Thermal\v1\API( $app );

		$app->run();

		exit;
	}

}
