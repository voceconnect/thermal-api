<?php

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../api/v1/API.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );


class APIv1Test extends WP_UnitTestCase {

	protected function _upload_file( $filename ) {
		$contents = file_get_contents( $filename );
		$upload = wp_upload_bits( basename( $filename ), null, $contents );

		return $upload;
	}

	protected function _make_attachment($upload, $parent_post_id = -1 ) {
		$type = '';
		if ( ! empty( $upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( $mime ) {
				$type = $mime['type'];
			}
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent_post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;
	}

	public function _get_posts_config( $query_args = array(), $id = null ) {
		$query_string = build_query( $query_args );

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/test',
			'QUERY_STRING' => $query_string,
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );

		return $api->get_post_query( $app->request(), $id );
	}

	public function setUp() {
		\Slim\Slim::registerAutoloader();

		add_filter( 'pre_option_permalink_structure', function() {
			return '';
		});

		add_filter( 'pre_option_gmt_offset', '__return_zero' );
	}

	public function testGetPosts() {
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts',
			'QUERY_STRING' => '',
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts();

		$this->assertInternalType( 'array', $data );
		$this->assertArrayHasKey( 'posts', $data );
		$this->assertArrayNotHasKey( 'found', $data );
	}
	
	public function testGetPostsCount() {
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts',
			'QUERY_STRING' => 'include_found=true',
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts();

		$this->assertArrayHasKey( 'posts', $data );
		$this->assertArrayHasKey( 'found', $data );


		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts',
			'QUERY_STRING' => 'paged=1',
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts();

		$this->assertArrayHasKey( 'posts', $data );
		$this->assertArrayHasKey( 'found', $data );
	}

	public function testGetPost() {
		$test_post_id = wp_insert_post( array(
			'post_status'           => 'publish',
			'post_title'            => 'testGetPost',
		) );

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts/' . $test_post_id,
			'QUERY_STRING' => '',
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts( $test_post_id );

		$this->assertArrayHasKey( 'posts', $data );
		$this->assertEquals( $test_post_id, $data['posts'][0]['id'] );


		$id = 9999999;
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts/' . $id,
			'QUERY_STRING' => '',
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts( $id );

		$this->assertArrayHasKey( 'posts', $data );
		$this->assertEmpty( $data['posts'] );
	}

	// All parameters are correct, using arrays for parameters when possible
	public function testGetPostQuery() {
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

		$api_get_posts = $this->_get_posts_config( $test_args );

		$query_vars = $api_get_posts->query_vars;

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

		$this->assertEquals( $tax_object, $api_get_posts->tax_query );


		//After
		$this->assertContains( "post_date > '2013-01-05'", $api_get_posts->request );

		//Before
		$this->assertContains( "post_date < '2013-01-01'", $api_get_posts->request );

		//Author
		$this->assertEquals( '1,5', $query_vars['author'] );

		//Orderby
		$this->assertEquals( 'ID author', $query_vars['orderby'] );

		//Posts_per_page
		$this->assertEquals( $query_vars['posts_per_page'], $test_args['per_page'] );

		//Paged
		$this->assertEquals( $query_vars['paged'], $test_args['paged'] );

	}

	// Parameters correct and use strings when possible
	public function testGetPostsStringParameters() {
		$test_args = array(
			'author'  => '1',
			'cat'     => '7',
			'orderby' => 'author',
		);

		$api_get_posts = $this->_get_posts_config( $test_args );

		$query_vars = $api_get_posts->query_vars;

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

		$this->assertEquals( $tax_object, $api_get_posts->tax_query );

		//Orderby
		$this->assertEquals( 'author', $query_vars['orderby'] );
	}

	public function testGetPostInvalidData() {
		$test_args = array(
			'after'    => 'incorrect',             // Will work with unexpected time result
			'before'   => 'time',                  // Will work with unexpected time result
			'author'   => array( -1, -5, -6 ),     // No author filter
			'cat'      => '',                      // WP should ignore
			'orderby'  => array( 'ID', 'WRONG' ),  // using a orderby that is not valid
			'per_page' => 20,                      // MAX_POSTS_PER_PAGE set to 10 in API
		);

		$api_get_posts = $this->_get_posts_config( $test_args );

		$query_vars = $api_get_posts->query_vars;

		//Author
		$this->assertEmpty( $query_vars['author'] );

		//Categories
		$this->assertEmpty( $query_vars['cat'] );

		//Orderby
		$this->assertEquals( 'ID', $query_vars['orderby'] );

		//PerPage
		$this->assertEquals( 10, $query_vars['posts_per_page'] );
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
			'post_title'            => 'testPostFormat!',
			'post_date'             => '2013-04-30 20:33:36',
			'post_date_gmt'         => '2013-04-30 20:33:36',
			'comment_status'        => 'open',
		) );

		$expected = array(
			'id'               => $test_post_id,
			'id_str'           => (string)$test_post_id,
			'type'             => 'post',
			'permalink'        => home_url( '?p=' . $test_post_id ),
			'parent'           => 0,
			'parent_str'       => '0',
			'date'             => '2013-04-30T20:33:36+00:00',
			'modified'         => '2013-04-30T20:33:36+00:00',
			'status'           => 'publish',
			'comment_status'   => 'open',
			'comment_count'    => 0,
			'menu_order'       => 0,
			'title'            => 'testPostFormat!',
			'name'             => 'testpostformat',
			'excerpt_raw'      => 'This is the excerpt.',
			'excerpt'          => "<p>This is the excerpt.</p>\n",
			'content_raw'      => 'This is the content.',
			'content'          => "<p>This is the content.</p>\n",
			'content_filtered' => '',
			'mime_type'        => '',
			'meta'             => (object)array(),
			'media'            => array(),
		);

		$test_post = get_post( $test_post_id );

		$actual = $api->format_post( $test_post );

		$this->assertEquals( $expected, $actual );

	}

	public function testPostMetaFeaturedID() {
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

		$filename = __DIR__ . '/data/250x250.png';
		$upload = $this->_upload_file( $filename );
		$attachment_id = $this->_make_attachment( $upload, $test_post_id );

		set_post_thumbnail( $test_post_id, $attachment_id );

		$formatted_post = $api->format_post( get_post( $test_post_id ) );

		$this->assertArrayHasKey( 'meta', $formatted_post );
		$this->assertObjectHasAttribute( 'featured_image', $formatted_post['meta'] );
		$this->assertEquals( $attachment_id, $formatted_post['meta']->featured_image );

	}

	public function testFormatImageMediaItem() {
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

		$filename = __DIR__ . '/data/250x250.png';
		$upload = $this->_upload_file( $filename );
		$attachment_id = $this->_make_attachment($upload, $test_post_id);

		$full_image_attributes = wp_get_attachment_image_src( $attachment_id, 'full' );
		$thumb_image_attributes = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );

		$expected = array(
			'id' => $attachment_id,
			'id_str' => (string)$attachment_id,
			'mime_type' => 'image/png',
			'alt_text' => '',
			'sizes' => array(
				array(
					'height' => 250,
					'width' => 250,
					'name' => 'full',
					'url' => $full_image_attributes[0],
				),
				array(
					'height' => 150,
					'width' => 150,
					'name' => 'thumbnail',
					'url' => $thumb_image_attributes[0],
				),
			),
		);

		$post = get_post( $attachment_id );
		$formatted_post = $api->format_image_media_item( $post );

		$this->assertEquals( $expected, $formatted_post );
	}

	public function testGetTermsArgs() {
		$test_args = array( 'invalid_key' => true );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['invalid_key'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertEquals( MAX_TERMS_PER_PAGE, $args['number'] );

		$test_args = array( 'per_page' => -5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( MAX_TERMS_PER_PAGE, $args['number'] );

		$test_args = array( 'per_page' => 0 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( MAX_TERMS_PER_PAGE, $args['number'] );

		$test_args = array( 'per_page' => 5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 5, $args['number'] );

		$test_args = array( 'per_page' => 15 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( MAX_TERMS_PER_PAGE, $args['number'] );

		$test_args = array( 'per_page' => 'five' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( MAX_TERMS_PER_PAGE, $args['number'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['offset'] );

		$test_args = array( 'offset' => -5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['offset'] );

		$test_args = array( 'offset' => 0 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['offset'] );

		$test_args = array( 'offset' => 5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 5, $args['offset'] );

		$test_args = array( 'offset' => 'five' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['offset'] );


		$test_args = array( 'paged' => -5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['offset'] );

		$test_args = array( 'paged' => 0 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['offset'] );

		$test_args = array( 'paged' => 1 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 0, $args['offset'] );

		$test_args = array( 'paged' => 2 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 10, $args['offset'] );

		$test_args = array( 'paged' => 'five' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['offset'] );


		$test_args = array( 'per_page' => 3, 'paged' => 1 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 0, $args['offset'] );

		$test_args = array( 'per_page' => 3, 'paged' => 5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 12, $args['offset'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['orderby'] );

		$test_args = array( 'orderby' => 'slug' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 'slug', $args['orderby'] );

		$test_args = array( 'orderby' => 'SLUG' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 'slug', $args['orderby'] );

		$test_args = array( 'orderby' => 'invalid' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['orderby'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['order'] );

		$test_args = array( 'order' => 'desc' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 'desc', $args['order'] );

		$test_args = array( 'order' => 'DESC' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 'desc', $args['order'] );

		$test_args = array( 'order' => 'invalid' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['order'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['include'] );

		$test_args = array( 'include' => 5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( array( 5 ), $args['include'] );

		$test_args = array( 'include' => 'fail' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['include'] );

		$test_args = array( 'include' => array( 5 ) );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( array( 5 ), $args['include'] );

		$test_args = array( 'include' => array( 5, 10, 15 ) );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( array( 5, 10, 15 ), $args['include'] );

		$test_args = array( 'include' => array( 5, 'fail', 15 ) );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( array( 5, 15 ), $args['include'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['slug'] );

		$test_args = array( 'slug' => 'anything' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 'anything', $args['slug'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['parent'] );

		$test_args = array( 'parent' => 5 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertEquals( 5, $args['parent'] );

		$test_args = array( 'parent' => 'fail' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['parent'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['hide_empty'] );

		$test_args = array( 'exclude_empty' => 'true' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['hide_empty'] );

		$test_args = array( 'exclude_empty' => 'anything' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['hide_empty'] );

		$test_args = array( 'exclude_empty' => 'false' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertFalse( $args['hide_empty'] );

		$test_args = array( 'exclude_empty' => 'FALSE' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertFalse( $args['hide_empty'] );

		$test_args = array( 'exclude_empty' => 0 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertFalse( $args['hide_empty'] );

		$test_args = array( 'exclude_empty' => '0' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertFalse( $args['hide_empty'] );


		$args = \WP_JSON_API\APIv1::get_terms_args();
		$this->assertNull( $args['pad_count'] );

		$test_args = array( 'pad_count' => 'true' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertTrue( $args['pad_count'] );

		$test_args = array( 'pad_count' => 'anything' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertTrue( $args['pad_count'] );

		$test_args = array( 'pad_count' => 'false' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['pad_count'] );

		$test_args = array( 'pad_count' => 'FALSE' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['pad_count'] );

		$test_args = array( 'pad_count' => 0 );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['pad_count'] );

		$test_args = array( 'pad_count' => '0' );
		$args = \WP_JSON_API\APIv1::get_terms_args( $test_args );
		$this->assertNull( $args['pad_count'] );
	}

	public function testGetTerms() {
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/taxonomies/category/terms/1',
			'QUERY_STRING' => '',
		));

		$slim = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $slim );

		$terms = $api->get_terms( 'category', 1 );

		$this->assertArrayNotHasKey( 'found', $terms );
		$this->assertGreaterThan( 0, count( $terms['terms'] ) );


		add_filter( 'get_terms_fields', function( $selects, $args ) {
			if ( 'count' == $args['fields'] ) {
				return array( '3 AS count' );
			}

			return $selects;
		}, 999, 2 );

		add_filter( 'get_terms', function( $terms, $taxonomies, $args ) {
			return array(
				(object)array(
					'term_id' => '2',
					'name' => 'Test Cat 1',
					'slug' => 'test-cat-1',
					'term_group' => '0',
					'term_taxonomy_id' => '2',
					'taxonomy' => 'category',
					'description' => '',
					'parent' => '0',
					'count' => '0',
				),
				(object)array(
					'term_id' => '6',
					'name' => 'Test Cat 2',
					'slug' => 'test-cat-2',
					'term_group' => '0',
					'term_taxonomy_id' => '6',
					'taxonomy' => 'category',
					'description' => '',
					'parent' => '0',
					'count' => '0',
				),
				(object)array(
					'term_id' => '7',
					'name' => 'Test Cat 3',
					'slug' => 'test-cat-3',
					'term_group' => '0',
					'term_taxonomy_id' => '7',
					'taxonomy' => 'category',
					'description' => '',
					'parent' => '2',
					'count' => '0',
				)
			);
		}, 999, 3 );


		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/taxonomies/category/terms',
			'QUERY_STRING' => 'include_found=true',
		));

		$slim = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $slim );

		$terms = $api->get_terms( 'category' );

		$this->assertEquals( 3, $terms['found'] );
		$this->assertEquals( 3, count( $terms['terms'] ) );


		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/taxonomies/category/terms',
			'QUERY_STRING' => 'paged=1',
		));

		$slim = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $slim );

		$terms = $api->get_terms( 'category' );

		$this->assertEquals( 3, $terms['found'] );


		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/taxonomies/category/terms',
			'QUERY_STRING' => '',
		));

		$slim = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $slim );

		$terms = $api->get_terms( 'category' );

		$this->assertArrayNotHasKey( 'found', $terms );
	}

	public function testFormatTerm() {
		$expected = array(
			'id' => 1,
			'id_str' => '1',
			'term_taxonomy_id' => 1,
			'term_taxonomy_id_str' => '1',
			'parent' => 0,
			'parent_str' => '0',
			'name' => 'Uncategorized',
			'slug' => 'uncategorized',
			'taxonomy' => 'category',
			'description' => '',
			'post_count' => '4',
			'meta' => array(),
		);
		$actual = \WP_JSON_API\APIv1::format_term( get_term( 1, 'category' ) );

		$this->assertEquals( $actual, $expected );
	}

}