<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../dispatcher.php' );
require_once( __DIR__ . '/../../api/v1/API.php' );
require_once( __DIR__ . '/../../lib/Slim/Slim/Slim.php' );

class APITest extends WP_UnitTestCase {

	protected function _insert_post( $args = array( ), $imgs = array( ) ) {

		$test_post_id = wp_insert_post( wp_parse_args( $args, array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_author' => 1,
			'post_parent' => 0,
			'menu_order' => 0,
			'post_content_filtered' => '',
			'post_excerpt' => 'This is the excerpt.',
			'post_content' => 'This is the content.',
			'post_title' => 'Hello World!',
			'post_date' => '2013-04-30 20:33:36',
			'post_date_gmt' => '2013-04-30 20:33:36',
			'comment_status' => 'open',
			) ) );

		if ( empty( $imgs ) ) {
			return $test_post_id;
		}

		$attachment_ids = array( );
		foreach ( $imgs as $filename ) {
			$upload = $this->_upload_file( __DIR__ . '/data/' . $filename );
			$attachment_ids[] = $this->_make_attachment( $upload, $test_post_id );
		}

		return array(
			'post_id' => $test_post_id,
			'attachment_ids' => $attachment_ids,
		);
	}

	protected function _upload_file( $filename ) {

		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( basename( $filename ), null, $contents );

		return $upload;
	}

	protected function _delete_attachment( $attachment ) {
		foreach ( ( array ) $attachment as $id ) {
			wp_delete_attachment( $id, true );
		}
	}

	/*
	 * Method pulled from WordPress testing suite
	 */

	protected function _make_attachment( $upload, $parent_post_id = -1 ) {
		$type = '';
		if ( !empty( $upload['type'] ) ) {
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
			'guid' => $upload['url'],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;
	}

	/**
	 * 
	 * @return array ['status', 'headers', 'body']
	 */
	protected function _getResponse( $envArgs ) {
		\Slim\Environment::mock( $envArgs );
		$app = new \Slim\Slim();
		new \Voce\Thermal\v1\API( $app );
		$app->call();
		return $app->response()->finalize();
	}

	public function setUp() {

		\Slim\Slim::registerAutoloader();
	}

	public function testGetPosts() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertObjectNotHasAttribute( 'found', $data );
	}

	public function testGetPostsCount() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'include_found=true',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertObjectHasAttribute( 'found', $data );


		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'paged=1',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertObjectHasAttribute( 'found', $data );


		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'paged=1',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertObjectHasAttribute( 'found', $data );
	}

	public function testGetPost() {
		$test_post_id = wp_insert_post( array(
			'post_status' => 'publish',
			'post_title' => 'testGetPost',
			'post_author' => 1,
			) );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $test_post_id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals('200', $status);
		$this->assertInternalType( 'object', $data );
		$this->assertEquals( $test_post_id, $data->id );

		$id = 9999999;

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals('404', $status);

		$test_post_id = wp_insert_post( array(
			'post_status' => 'draft',
			'post_title' => 'testGetPostDraft',
			) );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $test_post_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( '401', $status );
	}

	public function testGetPostsByIdParameterNotRoute() {

		$test_post_id = $this->_insert_post( array(
			'post_status' => 'publish',
			'post_title' => 'testGetPostsByIdParameterNotRoute',
			'post_author' => 1,
			) );

		$test_args = array(
			'p' => $test_post_id
		);

		list($status, $headers, $body) = $this->_getResponse(array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => http_build_query( $test_args ),
		));

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertEquals( 1, count( $data->posts ) );
	}

	public function testGetUsers() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/users/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals('401', $status);
	}
	
	public function testGetUser() {
		$user_id = wp_insert_user(array(
			'user_login' => 'test_get_user',
		));
		if(is_wp_error($user_id)) {
			$user_id = get_user_by('login', 'test_get_user')->ID;
		}
		
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/users/' . $user_id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals('401', $status);
	}
}
