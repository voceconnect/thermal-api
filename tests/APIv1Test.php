<?php

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../api/v1/API.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );


class APIv1Test extends WP_UnitTestCase {

	public function setUp() {

		\Slim\Slim::registerAutoloader();

		add_filter( 'pre_option_permalink_structure', function() {
			return '';
		});

		add_filter( 'pre_option_gmt_offset', '__return_zero' );
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
			'QUERY_STRING' => $query_string,
        ));

		$slim = new \Slim\Slim();

		$apiv1 = new \WP_JSON_API\APIv1( $slim );

		$apiv1_get_posts = $apiv1->get_post_query( $slim->request() );

		$query_vars = $apiv1_get_posts->query_vars;

		//Taxonomies and Categories
		$tax_array = array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'post_tag',
				'terms'    => array( '1', '2', '3' ),
				'field' => 'term_id',
			),
			array(
				'taxonomy' => 'category',
				'terms'    => array( '7', '8'),
				'field' => 'term_id',
				'include_children' => false,
			),
			array(
				'taxonomy' => 'category',
				'terms' => array( '9' ),
				'field' => 'term_id',
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

	public function testPostFormat() {

		$slim = new \Slim\Slim();

		$api = new \WP_JSON_API\APIv1( $slim );

		$test_post_id = wp_insert_post( array(
			'post_status'           => 'publish',
			'post_type'             => 'post',
			'post_author'           => 1,
			'post_parent'           => 0,
			'menu_order'            => 0,
			'post_content_filtered' => '',
			'post_excerpt'          => 'This is the excerpt.',
			'post_content'          => 'This is the content.',
			'post_title'            => 'Hello World!',
			'post_date'             => '2013-04-30 20:33:36',
			'post_date_gmt'         => '2013-04-30 20:33:36',
			'comment_status'        => 'open',
		) );

		$expected = array(
			'id'               => $test_post_id,
			'id_str'           => (string)$test_post_id,
			'permalink'        => home_url( '?p=' . $test_post_id ),
			'parent'           => 0,
			'parent_str'       => '0',
			'date'             => '2013-04-30T20:33:36+00:00',
			'modified'         => '2013-04-30T20:33:36+00:00',
			'status'           => 'publish',
			'comment_status'   => 'open',
			'comment_count'    => 0,
			'menu_order'       => 0,
			'title'            => 'Hello World!',
			'name'             => 'hello-world',
			'excerpt_raw'      => 'This is the excerpt.',
			'excerpt'          => "<p>This is the excerpt.</p>\n",
			'content_raw'      => 'This is the content.',
			'content'          => "<p>This is the content.</p>\n",
			'content_filtered' => '',
			'mime_type'        => '',
		);

		$test_post = get_post( $test_post_id );

		$actual = $api->format_post( $test_post );

		$this->assertEquals( $expected, $actual );

	}

}