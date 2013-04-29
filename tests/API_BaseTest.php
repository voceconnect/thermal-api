<?php

global $wp, $wp_the_query, $wp_query;

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );
require_once( dirname( __FILE__ ) . '/../../../../wp-blog-header.php' );
require_once( dirname( __FILE__ ) . '/../api/API_Base.php' );
require_once( dirname( __FILE__ ) . '/../lib/Slim/Slim/Slim.php' );

class Version_API_Test_1 extends \WP_JSON_API\API_Base {

	protected $version = '1';
	
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

		$apitest1 = new Version_API_Test_1( $slim );

		$test = $apitest1->registerRoute( 'get', 'abc', function(){} );
		$this->assertEquals( 'api/v1/abc', $test->getPattern() );

		$apitest1 = new Version_API_Test_2( $slim );

		$test = $apitest1->registerRoute( 'get', 'abc', function(){} );
		$this->assertEquals( 'api/v2/abc', $test->getPattern() );
	}

}