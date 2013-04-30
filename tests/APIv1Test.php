<?php

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../api/v1/API.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );


class APIv1Test extends WP_UnitTestCase {

	public function setUp() {

		\Slim\Slim::registerAutoloader();
    }

	public function testPostFormat() {

		$slim = new \Slim\Slim();

		$apitest = new \WP_JSON_API\APIv1( $slim );

		$expected = array (
			'id' => 1,
			'id_str' => '1',
			'permalink' => 'http://wp.voceconnect.dev/2013/04/hello-world/',
			'parent' => 0,
			'parent_str' => '0',
			'date' => '2013-04-29T12:54:02+00:00',
			'modified' => '2013-04-30T15:00:52+00:00',
			'status' => 'publish',
			'comment_status' => 'open',
			'comment_count' => 1,
			'menu_order' => 0,
			'title' => 'Hello world!',
			'name' => 'hello-world',
			'excerpt_raw' => 'This is an excerpt.',
			'excerpt' => "<p>This is an excerpt.</p>\n",
			'content_raw' => 'Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!',
			'content' => "<p>Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!</p>\n",
			'content_filtered' => '',
			'mime_type' => '',
		);

//		$apitest = $apitest->postregisterRoute( 'yum', 'abc', function(){} );
		$this->assertFalse( false );
	}

}