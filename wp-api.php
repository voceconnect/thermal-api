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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
			add_action( 'init', array( $this, 'setup' ) );
			add_action( 'wp_loaded', array( $this, 'dispatch_api' ) );
		}
	}

	public function setup() {
		require_once __DIR__ . '/api/iAPI.php';
		spl_autoload_register( function($className) {
				if ( 0 === strpos( $className, 'Symfony\Component\HttpFoundation' ) ) {
					$className = ltrim( $className, '\\' );
					$fileName = '';
					$namespace = '';
					if ( $lastNsPos = strrpos( $className, '\\' ) ) {
						$namespace = substr( $className, 0, $lastNsPos );
						$className = substr( $className, $lastNsPos + 1 );
						$fileName = str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
					}
					$fileName .= str_replace( '_', DIRECTORY_SEPARATOR, $className ) . '.php';

					require __DIR__ . '/lib/'. $fileName;
				}
			} );
	}

	public function dispatch_api() {
		//determine API version
		require_once __DIR__ . '/api/v1/API.php';

		//$api = correct api version
		$api = new API();
		$request = Request::createFromGlobals();
		$response = new Response();
		$api->handleRequest( $request, $response );
		$response->send();
		die();
	}

}

new API_Dispatcher();