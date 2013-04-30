<?php

global $wp, $wp_the_query, $wp_query;

$_SERVER['SERVER_PROTOCOL'] = "HTTP/1.1";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_NAME'] = 'test';
$_SERVER['SERVER_PORT'] = '80';

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../../../wp-blog-header.php' );
require_once( __DIR__ . '/../api/API_Base.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );

require_once( __DIR__ . '/../api/v1/API.php' );

class API_BaseTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		\Slim\Slim::registerAutoloader();
	}

	// All parameters are correct
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

		$query_string = build_query( $test_args );

		\Slim\Environment::mock( array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO' => WP_API_BASE . '/v1/test',
			'QUERY_STRING' => $query_string
        ));
		
		$slim = new \Slim\Slim();

		$apiv1 = new \WP_JSON_API\APIv1( $slim );

		$apiv1_get_posts = $apiv1->get_posts();

		$query_vars = $apiv1_get_posts->query_vars;

		//Taxonomies and Categories
		$tax_array = array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'post_tag',
				'terms'    => array( '1', '2', '3' ),
				'field' => 'term_id'
			),
			array(
				'taxonomy' => 'category',
				'terms'    => array( '7', '8'),
				'field' => 'term_id',
				'include_children' => false
			),
			array(
				'taxonomy' => 'category',
				'terms'    => array(
					'9'
				),
				'field' => 'term_id',
				'operator' => 'NOT IN',
				'include_children' => false
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

	// Expect the found_posts filter to be set
	public function testGetPostsPaged() {
		$args = array(
			'paged' => 1
		);
	}

	// All parameters incorrect
	public function testGetPostsIncorrectParameters() {
		$args = array(

		);
	}
}
