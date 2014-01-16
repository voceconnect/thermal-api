<?php

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
		parent::setUp();
		
		\Slim\Slim::registerAutoloader();
	}

}
