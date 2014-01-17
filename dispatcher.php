<?php
namespace Voce\Thermal;

spl_autoload_register(__NAMESPACE__ . "\\autoload");
require_once __DIR__ . '/lib/jsonp/jsonp.php';

function autoload($class) {
	$lenNS = strlen( __NAMESPACE__ );
	if ( __NAMESPACE__ === substr( $class, 0, $lenNS ) ) {
		$class = substr( $class, $lenNS + 1);

		if( false !== ( $lastNsPos = strripos( $class, '\\' ) ) ) {
			$filename = substr( $class, $lastNsPos + 1 ) . '.php';
			$path = strtolower( str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, 0, $lastNsPos ) ) );
		} else {
			$filename = $class . '.php';
			$path = '';
		}
		
		@include( __DIR__ . DIRECTORY_SEPARATOR . 'api' .DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $filename );
	}
}

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
			require_once( 'api/v1/API.php' );

			add_action( 'wp_loaded', array( $this, 'dispatch_api' ) );
		}
	}

	public function dispatch_api() {
		require_once( 'lib/jsonp/jsonp.php' );


		\Slim\Slim::registerAutoloader();

		$app = new \Slim\Slim();

		new \Voce\Thermal\v1\API( $app );

		$app->run();

		exit;
	}

}
