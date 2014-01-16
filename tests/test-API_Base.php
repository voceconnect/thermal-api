<?php

require_once( __DIR__ . '/stubs/API_Test_v1.php' );
require_once( __DIR__ . '/stubs/API_Test_v2.php' );

class API_BaseTest extends WP_UnitTestCase {

	public function setUp() {

		\Slim\Slim::registerAutoloader();
		\Slim\Environment::mock();
	}

	public function testRegisterRouteWhitelist() {

		$slim = new \Slim\Slim();

		$apitest1 = new API_Test_v1( $slim );

		$test = $apitest1->registerRoute( 'get', 'abc', function() {
				
			} );
		$this->assertInstanceOf( '\Slim\Route', $test );
		$this->assertContains( 'GET', $test->getHttpMethods() );

		$test2 = $apitest1->registerRoute( 'yum', 'abc', function() {
				
			} );
		$this->assertFalse( $test2 );
	}

	public function testAPIVersion() {
		$slim = new \Slim\Slim();

		$apiTest = new API_Test_v1( $slim );

		$test = $apiTest->registerRoute( 'get', 'abc', function() {
				
			} );
		$this->assertEquals( '/' . Voce\Thermal\API_BASE . '/v1/abc', $test->getPattern() );

		$apiTest = new API_Test_v2( $slim );

		$test = $apiTest->registerRoute( 'get', 'abc', function() {
				
			} );
		$this->assertEquals( '/' . Voce\Thermal\API_BASE . '/v2/abc', $test->getPattern() );
	}

	public function testAccessControlHeader() {

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/test',
		) );

		$slim = new \Slim\Slim();
		$apiTest = new API_Test_v1( $slim );

		$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'test_header';

		$apiTest->registerRoute( 'GET', 'test', function() {
				return '';
			} );

		ob_start();
		$apiTest->app->run();
		ob_end_clean();

		$res = $apiTest->app->response();
		$this->assertEquals( 'test_header', $res->header( 'access-control-allow-headers' ) );
	}

	public function testAPIOutput() {

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/test',
		) );
		$slim = new \Slim\Slim();

		$apiTest = new API_Test_v1( $slim );

		$testData = array( 'testKey' => Voce\Thermal\API_BASE . '/test' );

		$apiTest->registerRoute( 'GET', 'test', function () use ( $testData ) {
				return $testData;
			} );

		ob_start();
		$apiTest->app->run();
		ob_end_clean();

		$res = $apiTest->app->response();
		$this->assertEquals( json_encode( $testData ), $res->body() );
		$this->assertEquals( 'application/json; charset=utf-8;', $res->header( 'Content-Type' ) );
	}

	public function testJSONP() {

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/test',
			'QUERY_STRING' => 'callback=doStuff',
		) );

		$slim = new \Slim\Slim();

		$apiTest = new API_Test_v1( $slim );

		$apiTest->registerRoute( 'GET', 'test', function () {
				return 'test';
			} );

		ob_start();
		$apiTest->app->run();
		ob_end_clean();

		$res = $apiTest->app->response();
		$this->assertEquals( 'doStuff("test")', $res->body() );
		$this->assertEquals( 'application/javascript; charset=utf-8;', $res->header( 'Content-Type' ) );
	}

	public function testBadRoute() {
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/foobar',
		) );
		$app = new \Slim\Slim();

		$apiTest = new API_Test_v1( $app );

		ob_start();
		$apiTest->app->run();
		ob_end_clean();

		$response = $apiTest->app->response();
		$this->assertObjectHasAttribute( 'error', json_decode( $response->body() ) );
	}

}