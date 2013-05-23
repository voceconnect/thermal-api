<?php

namespace Voce\Thermal;

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

		$this->app->notFound( function () use ( $app ) {
			$data = array(
				'error' => array(
					'message' => 'Invalid route'
				),
			);
			$app->contentType( 'application/json' );
			$app->halt( 400, json_encode( $data ) );
		} );

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

		$match = get_api_base() . trailingslashit( 'v' . $this->version ) . $pattern;

		$app = $this->app;

		return $this->app->$method( $match, function() use ( $app, $callback ) {

			$data = call_user_func_array( $callback, func_get_args() );
			$json = json_encode( $data );
			$res  = $app->response();

			$res->header('Access-Control-Allow-Origin', '*');
			if ( isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ) ){
				$res->header('Access-Control-Allow-Headers', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
			}

			if ( $json_p = $app->request()->get( 'callback' ) ) {

				$app->contentType( 'application/javascript; charset=utf-8;' );
				$res->write( sprintf( '%s(%s)', $json_p, $json ) );

			} else {

				$app->contentType( 'application/json; charset=utf-8;' );
				$res->write(json_encode($data), true);

			}

		});
	}

}
