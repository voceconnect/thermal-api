<?php
namespace WP_JSON_API;

abstract class API_Base {

	/**
	 *
	 * @var \Slim\Slim $app Slim app instance
	 */
	public $app;

	/**
	 *
	 * @var string $version API version
	 */
	public $version = '';


	/**
	 * Constructor
	 * 
	 * @param \Slim\Slim $app Slim app reference
	 */
	public function __construct( \Slim\Slim $app) {

		$this->app = $app;

		// TODO: Check that version is specified

		
		
		
		
		

		// $this->app-run();
	}

	/**
	 * Registers route
	 * 
	 * @param string $method HTTP method
	 * @param string $pattern The path pattern to match
	 * @param callback $callback Callback function for route
	 */
	public function registerRoute( string $method, string $pattern, $callback ) {
		return $this->app->$method( trailingslashit( PW_API_BASE ) . trailingslashit( $this->version ) . $pattern, $callback);
	}

}
