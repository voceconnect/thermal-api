<?php

global $wp, $wp_the_query, $wp_query;

define("WP_USE_THEMES", false);
require dirname( __FILE__ ) . '/../../../../wp-blog-header.php';
require dirname( __FILE__ ) . '/../api/API_Base.php';
require dirname( __FILE__ ) . '/../lib/Slim/Slim/Slim.php';

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

		$test2 = $apitest1->registerRoute( 'yum', 'abc', function(){} );
		$this->assertFalse( $test2 );
	}

}