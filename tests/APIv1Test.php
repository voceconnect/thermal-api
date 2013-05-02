<?php

define( 'WP_API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../api/v1/API.php' );
require_once( __DIR__ . '/../lib/Slim/Slim/Slim.php' );


class APIv1Test extends WP_UnitTestCase {

	protected function _upload_file( $filename ) {

		$contents = file_get_contents($filename);

		$upload = wp_upload_bits(basename($filename), null, $contents);

		return $upload;
	}

	protected function _make_attachment($upload, $parent_post_id = -1 ) {

		$type = '';
		if ( !empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title'     => basename( $upload['file'] ),
			'post_content'   => '',
			'post_type'      => 'attachment',
			'post_parent'    => $parent_post_id,
			'post_mime_type' => $type,
			'guid'           => $upload['url'],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;

	}

	public function setUp() {

		\Slim\Slim::registerAutoloader();

	}

	public function testGetPosts() {
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => WP_API_BASE . '/v1/posts',
			'QUERY_STRING'   => '',
		) );

		$app  = new \Slim\Slim();
		$api  = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts();

		$this->assertInternalType( 'array', $data );
		$this->assertArrayHasKey( 'posts', $data );
		$this->assertArrayNotHasKey( 'found', $data );
	}
	
	public function testGetPostsCount() {
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => WP_API_BASE . '/v1/posts',
			'QUERY_STRING'   => 'include_found=true',
		) );

		$app  = new \Slim\Slim();
		$api  = new \WP_JSON_API\APIv1( $app );
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
			'post_status' => 'publish',
			'post_title'  => 'testGetPost',
		) );

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => WP_API_BASE . '/v1/posts/' . $test_post_id,
			'QUERY_STRING'   => '',
		) );

		$app  = new \Slim\Slim();
		$api  = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts( $test_post_id );

		$this->assertArrayHasKey( 'posts', $data );
		$this->assertEquals( $test_post_id, $data['posts'][0]['id'] );


		$id = 9999999;
		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts/' . $id,
			'QUERY_STRING' => '',
		));

		$app  = new \Slim\Slim();
		$api  = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts( $id );

		$this->assertArrayHasKey( 'posts', $data );
		$this->assertEmpty( $data['posts'] );


		$test_post_id = wp_insert_post( array(
			'post_status'           => 'draft',
			'post_title'            => 'testGetPostDraft',
		) );

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => WP_API_BASE . '/v1/posts/' . $test_post_id,
			'QUERY_STRING' => '',
		));

		$app = new \Slim\Slim();
		$api = new \WP_JSON_API\APIv1( $app );
		$data = $api->get_posts( $test_post_id );

		$this->assertEmpty( $data['posts'] );
	}

	// All parameters are correct, using arrays for parameters when possible
	public function testGetPostArgs() {

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
			'paged'    => 1,
			'fake'     => 'data not in whitelist',
		);

		$query_vars = \WP_JSON_API\APIv1::get_post_args( $test_args );
		$query = new \WP_Query( $query_vars );

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

		$this->assertEquals( $tax_object, $query->tax_query );


		//After
		$this->assertContains( "post_date > '2013-01-05'", $query->request );

		//Before
		$this->assertContains( "post_date < '2013-01-01'", $query->request );

		//Author
		$this->assertEquals( '1,5', $query_vars['author'] );

		//Orderby
		$this->assertEquals( 'ID author', $query_vars['orderby'] );

		//Posts_per_page
		$this->assertEquals( $test_args['per_page'], $query_vars['posts_per_page'] );

		//Paged
		$this->assertEquals( $test_args['paged'], $query_vars['paged'] );
		
		//No forbidded vars
		$this->assertArrayNotHasKey( 'fake', $query_vars );

	}

	// Parameters correct and use strings when possible
	public function testGetPostsStringParameters() {
		$test_args = array(
			'author'  => '1',
			'cat'     => '7',
			'orderby' => 'author',
		);

		$query_vars = \WP_JSON_API\APIv1::get_post_args( $test_args );
		$query = new \WP_Query( $query_vars );

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

		$this->assertEquals( $tax_object, $query->tax_query );

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

		$query_vars = \WP_JSON_API\APIv1::get_post_args( $test_args );

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

		$blank_permalink = function() {
			return '';
		};

		add_filter( 'pre_option_permalink_structure', $blank_permalink );
		add_filter( 'pre_option_gmt_offset', '__return_zero' );

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

		$actual = \WP_JSON_API\APIv1::format_post( $test_post );

		$this->assertEquals( $expected, $actual );

		remove_filter( 'pre_option_permalink_structure', $blank_permalink );
		remove_filter( 'pre_option_gmt_offset', '__return_zero' );

	}

	public function testPostMetaFeaturedID() {

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

		$filename      = __DIR__ . '/data/250x250.png';
		$upload        = $this->_upload_file( $filename );
		$attachment_id = $this->_make_attachment($upload, $test_post_id);

		set_post_thumbnail( $test_post_id, $attachment_id );

		$formatted_post = \WP_JSON_API\APIv1::format_post( get_post( $test_post_id ) );

		$this->assertArrayHasKey( 'meta', $formatted_post );
		$this->assertObjectHasAttribute( 'featured_image', $formatted_post['meta'] );
		$this->assertEquals( $attachment_id, $formatted_post['meta']->featured_image );

	}

	public function testFormatImageMediaItem() {

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

		$filename      = __DIR__ . '/data/250x250.png';
		$upload        = $this->_upload_file( $filename );
		$attachment_id = $this->_make_attachment( $upload, $test_post_id );

		$full_image_attributes  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$thumb_image_attributes = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );

		$expected = array(
			'id'        => $attachment_id,
			'id_str'    => (string)$attachment_id,
			'mime_type' => 'image/png',
			'alt_text'  => '',
			'sizes'     => array(
				array(
					'height' => 250,
					'width'  => 250,
					'name'   => 'full',
					'url'    => $full_image_attributes[0],
				),
				array(
					'height' => 150,
					'width'  => 150,
					'name'   => 'thumbnail',
					'url'    => $thumb_image_attributes[0],
				),
			),
		);

		$post = get_post( $attachment_id );
		$formatted_post = \WP_JSON_API\APIv1::format_image_media_item( $post );

		$this->assertEquals( $expected, $formatted_post );
	}

	public function testGetRewriteRules() {
		$slim = new \Slim\Slim();
		$api  = new \WP_JSON_API\APIv1( $slim );

		add_filter( 'pre_option_rewrite_rules', '__return_empty_array' );
		$api_rules = $api->get_rewrite_rules();
		remove_filter( 'pre_option_rewrite_rules', '__return_empty_array' );

		$this->assertEquals( home_url( '/' ), $api_rules['base_url'] );
		$this->assertEmpty( $api_rules['query_expression'] );

		$test_rewrites = function() {
			return array(
				'.*wp-register.php$'                           => 'index.php?register=true',
				'comments/page/?([0-9]{1,})/?$'                => 'index.php?&paged=$matches[1]',
				'search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?s=$matches[1]&feed=$matches[2]',
			);
		};

		add_filter( 'pre_option_rewrite_rules', $test_rewrites );
		$api_rules = $api->get_rewrite_rules();
		remove_filter( 'pre_option_rewrite_rules', $test_rewrites );

		$expected = array(
			'base_url'      => home_url( '/' ),
			'rewrite_rules' => array(
				array(
					'regex'            => '.*wp-register.php$',
					'query_expression' => 'register=true',
				),
				array(
					'regex'            => 'comments/page/?([0-9]{1,})/?$',
					'query_expression' => 'paged=$1',
				),
				array(
					'regex'            => 'search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$',
					'query_expression' => 's=$1&feed=$2',
				),
			),
		);

		$this->assertEquals( $expected, $api_rules );
	}

	public function testGetTaxonomyBadArrayParameters() {
		$test_args = array(
			'post_type' => array( 'foo' )
		);

		$query_string = build_query( $test_args );

		\Slim\Environment::mock( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => WP_API_BASE . '/v1/taxonomies',
			'QUERY_STRING'   => $query_string,
		) );

		$slim = new \Slim\Slim();

		$api = new \WP_JSON_API\APIv1( $slim );

		$api_get_taxonomies = $api->get_taxonomies();

		$this->assertArrayHasKey( 'taxonomies', $api_get_taxonomies );

		$api_get_taxonomies = array_shift( $api_get_taxonomies );

		$this->assertCount( 0, $api_get_taxonomies );
	}

	public function testGetTaxonomiesArrayParameters() {

		$test_args = array(
			'in'        => array( 'category', 'post_tag' ),
			'post_type' => array( 'post', 'page' )
		);

		$query_string = build_query( $test_args );

		\Slim\Environment::mock( array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO'      => WP_API_BASE . '/v1/taxonomies',
			'QUERY_STRING'   => $query_string,
        ));

		$slim = new \Slim\Slim();

		$api = new \WP_JSON_API\APIv1( $slim );

		$api_get_taxonomies = $api->get_taxonomies();

		$this->assertArrayHasKey( 'taxonomies', $api_get_taxonomies );

		$api_get_taxonomies = array_shift( $api_get_taxonomies );

		// Checking all results for invalid values
		$name_not_in = false;
		$post_type_not_in = false;
		foreach ( $api_get_taxonomies as $taxonomy_result ) {
			if ( !in_array( $taxonomy_result['name'], $test_args['in'] ) ){
				$name_not_in = true;
			}
			if ( 0 === count( array_intersect( $test_args['post_type'], $taxonomy_result['post_types'] ) ) ) {
				$post_type_not_in = true;
			}
		}

		// Checking taxonomy names in results
		$this->assertFalse( $name_not_in );

		// Checking taxonomy post type in results
		$this->assertFalse( $post_type_not_in );
	}

	public function testGetTaxonomiesStringParameters() {

		$test_args = array(
			'in'        => 'category',
			'post_type' => 'post'
		);

		$query_string = build_query( $test_args );

		\Slim\Environment::mock( array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO'      => WP_API_BASE . '/v1/taxonomies',
			'QUERY_STRING'   => $query_string,
        ));

		$slim = new \Slim\Slim();

		$api = new \WP_JSON_API\APIv1( $slim );

		$api_get_taxonomies = $api->get_taxonomies();

		$this->assertArrayHasKey( 'taxonomies', $api_get_taxonomies );

		$api_get_taxonomies = array_shift( $api_get_taxonomies );

		// Checking all results for invalid values
		$name_not_in = false;
		$post_type_not_in = false;
		foreach ( $api_get_taxonomies as $taxonomy_result ) {
			if ( $taxonomy_result['name'] !== $test_args['in'] ){
				$name_not_in = true;
			}
			if ( !in_array( $test_args['post_type'], $taxonomy_result['post_types'] ) ) {
				$post_type_not_in = true;
			}
		}

		// Checking taxonomy names in results
		$this->assertFalse( $name_not_in );

		// Checking taxonomy post type in results
		$this->assertFalse( $post_type_not_in );
	}

	public function testGetTaxonomy() {

		\Slim\Environment::mock( array(
            'REQUEST_METHOD' => 'GET',
            'PATH_INFO'      => WP_API_BASE . '/v1/taxonomies',
			'QUERY_STRING'   => '',
        ));

		$slim = new \Slim\Slim();

		$api = new \WP_JSON_API\APIv1( $slim );

		$api_get_taxonomies = $api->get_taxonomies('category');

		$this->assertArrayHasKey( 'taxonomies', $api_get_taxonomies );

		$api_get_taxonomies = array_shift( $api_get_taxonomies );

		// Checking only 1 result was returned
		$this->assertCount( 1, $api_get_taxonomies );

		$this->assertEquals( 'category', $api_get_taxonomies[0]['name'] );
	}

	public function testTaxonomyFormat() {

		$slim = new \Slim\Slim();

		$api = new \WP_JSON_API\APIv1( $slim );

		$formatted_taxonomy = $api->format_taxonomy( get_taxonomy( 'category' ) );

		$expected = array(
			'name'         => 'category',
			'post_types'   => array(
				'post',
			),
			'hierarchical' => true,
			'query_var'    => 'category_name',
			'labels'       => array(
				'name'          => 'Categories',
				'singular_name' => 'Category',
			),
			'meta'         => new stdClass(),
		);

		$this->assertEquals( $expected, $formatted_taxonomy );
	}
}
