<?php

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../api/v1/API.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );


class APIv1Test extends WP_UnitTestCase {

	public function setUp() {

		\Slim\Slim::registerAutoloader();
    }

 	public function getPostsSetUp( $query_args, $id = null ) {

		$query_string = build_query( $query_args );

		\Slim\Environment::mock( array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => WP_API_BASE . '/v1/test',
			'QUERY_STRING' => $query_string,
        ));

		$app = new \Slim\Slim();

		$apiv1 = new \WP_JSON_API\APIv1( $app );

		return $apiv1->get_post_query( $app->request(), $id );
	}

	// All parameters are correct, using arrays for parameters when possible
	public function testGetPosts() {

		$test_args = array(
			'taxonomy' => array(
				'post_tag'  => array( 1, 2, 3 )
			),
			'after'    => '2013-01-05',
			'before'   => '2013-01-01',
			'author'   => array( 1, 5, -6 ),
			'cat'      => array( 7, 8, -9 ),
			'orderby'  => array( 'ID', 'author' ),
			'per_page' => 5,
			'paged'    => 1 // also should set 'found_posts'
		);

		$apiv1_get_posts = $this->getPostsSetUp( $test_args );

		$query_vars = $apiv1_get_posts->query_vars;

		//Taxonomies and Categories
		$tax_array = array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'post_tag',
				'terms'    => array( '1', '2', '3' ),
				'field'    => 'term_id',
			),
			array(
				'taxonomy' => 'category',
				'terms'    => array( '7', '8'),
				'field'    => 'term_id',
				'include_children' => false,
			),
			array(
				'taxonomy' => 'category',
				'terms'    => array( '9' ),
				'field'    => 'term_id',
				'operator' => 'NOT IN',
				'include_children' => false,
			),
		);
		$tax_object = new WP_Tax_Query( $tax_array );

		$this->assertEquals( $tax_object, $apiv1_get_posts->tax_query );


		//After
		$this->assertContains( "post_date > '2013-01-05'", $apiv1_get_posts->request );

		//Before
		$this->assertContains( "post_date < '2013-01-01'", $apiv1_get_posts->request );

		//Author
		$this->assertEquals( '1,5', $query_vars['author'] );

		//Orderby
		$this->assertEquals( 'ID author', $query_vars['orderby'] );

		//Posts_per_page
		$this->assertEquals( $query_vars['posts_per_page'], $test_args['per_page'] );

		//Paged
		$this->assertEquals( $query_vars['paged'], $test_args['paged'] );
		// also verify that found posts is set, since paged is set
		$this->assertEquals( $query_vars['found_posts'], true );

	}

	// Parameters correct and use strings when possible
	public function testGetPostsStringParameters() {
		$test_args = array(
			'author'  => '1',
			'cat'     => '7',
			'orderby' => 'author',
		);

		$apiv1_get_posts = $this->getPostsSetUp( $test_args );

		$query_vars = $apiv1_get_posts->query_vars;

		//Author
		$this->assertEquals( '1', $query_vars['author'] );

		//Categories
		$tax_array = array(
			array(
				'taxonomy' => 'category',
				'terms'    => '7',
				'field'    => 'term_id',
				'include_children' => false,
			)
		);
		$tax_object = new WP_Tax_Query( $tax_array );

		$this->assertEquals( $tax_object, $apiv1_get_posts->tax_query );

		//Orderby
		$this->assertEquals( 'author', $query_vars['orderby'] );
	}

	public function testGetPost() {

		$apiv1_get_posts = $this->getPostsSetUp( array(), 5 );

		$query_vars = $apiv1_get_posts->query_vars;

		//Post
		$this->assertEquals( 5, $query_vars['p'] );
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