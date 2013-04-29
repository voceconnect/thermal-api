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
	protected $version = '';


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
	public function registerRoute( $method, $pattern, $callback ) {
		$method = strtolower( $method );

		$valid_methods = array(
			'get',
			'post',
			'delete',
			'put',
			'head',
			'options',
		);

		if ( ! in_array( $method, $valid_methods ) ) {
			return false;
		}

		$match = \trailingslashit( WP_API_BASE ) . \trailingslashit( 'v' . $this->version ) . $pattern;

		$app = $this->app;

		return $this->app->$method( $match, function() use ( $app, $callback ) {
			$data = call_user_func_array( $callback, func_get_args() );

			$res = $app->response();
			$app->contentType( 'application/json' );
			$res->write(json_encode($data), true);
		});
	}

}
