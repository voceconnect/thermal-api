<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../dispatcher.php' );
require_once( __DIR__ . '/../api/v1/API.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );

abstract class APITestCase extends WP_UnitTestCase {

	/**
	 * 
	 * @return array ['status', 'headers', 'body']
	 */
	protected function _getResponse( $envArgs ) {
		\Slim\Environment::mock( $envArgs );
		$app = new \Slim\Slim();
		new \Voce\Thermal\v1\API( $app );
		$app->call();
		return $app->response()->finalize();
	}

	public function setUp() {

		\Slim\Slim::registerAutoloader();
	}

}
