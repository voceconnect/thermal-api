<?php

global $wp, $wp_the_query, $wp_query;

$_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_NAME'] = 'test';
$_SERVER['SERVER_PORT'] = '80';

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );
require_once( dirname( __FILE__ ) . '/../../../../wp-blog-header.php' );
require_once( dirname( __FILE__ ) . '/../api/API_Base.php' );
require_once( dirname( __FILE__ ) . '/../lib/Slim/Slim/Slim.php' );

class Version_API_Test_1 extends \WP_JSON_API\API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $slim ) {
		parent::__construct( $slim );
	}

}

class Version_API_Test_2 extends \WP_JSON_API\API_Base {

	protected $version = '2';

}

class API_BaseTest extends PHPUnit_Framework_TestCase {

	public function setUp() {

		\Slim\Slim::registerAutoloader();
    }

	public function testRegisterRouteWhitelist() {

		$slim = new \Slim\Slim();

		$apitest1 = new Version_API_Test_1( $slim );

		$test = $apitest1->registerRoute( 'get', 'abc', function(){} );
		$this->assertInstanceOf( '\Slim\Route', $test );
		$this->assertContains( 'GET', $test->getHttpMethods() );

		$test2 = $apitest1->registerRoute( 'yum', 'abc', function(){} );
		$this->assertFalse( $test2 );
	}

	public function testAPIVersion() {
		$slim = new \Slim\Slim();

		$apiTest = new Version_API_Test_1( $slim );

		$test = $apiTest->registerRoute( 'get', 'abc', function(){} );
		$this->assertEquals( 'api/v1/abc', $test->getPattern() );

		$apiTest = new Version_API_Test_2( $slim );

		$test = $apiTest->registerRoute( 'get', 'abc', function(){} );
		$this->assertEquals( 'api/v2/abc', $test->getPattern() );
	}

	public function testAPIOutput() {

		\Slim\Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => WP_API_BASE . '/v1/test', //<-- Virtual
        ));
		$slim = new \Slim\Slim();

		$apiTest = new Version_API_Test_1( $slim );

		$testData = array( 'testKey' => WP_API_BASE . '/test' );

		$apiTest->registerRoute( 'GET', 'test', function () use ( $testData ) {
			return $testData;
		});

		ob_start();
		$apiTest->app->run();
		ob_end_clean();

		$res = $apiTest->app->response();
		$this->assertEquals( json_encode( $testData ), $res->body() );
		$this->assertEquals( 'application/json', $res->header( 'Content-Type' ) );
	}

}