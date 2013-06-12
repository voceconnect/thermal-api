<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../APITestCase.php' );

class PostsControllerTest extends APITestCase {

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
			$upload = $this->_upload_file( dirname( dirname( __DIR__ ) ) . '/data/' . $filename );
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
		$this->assertInternalType( 'array', $data->posts );
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
		$test_data = $this->_insert_post( null, array( '100x200.png', '100x300.png' ) );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $test_data['post_id'],
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );

		$post = get_post( $test_data['post_id'] );

		$checks = array(
			'id' => array( 'type' => 'int', 'value' => $post->ID ),
			'id_str' => array( 'type' => 'string', 'value' => $post->ID ),
			'type' => array( 'type' => 'string', 'value' => 'post' ),
			'permalink' => array( 'type' => 'string', 'value' => get_permalink( $post->ID ) ),
			'parent' => array( 'type' => 'int', 'value' => $post->post_parent ),
			'parent_str' => array( 'type' => 'string', 'value' => $post->post_parent ),
			'date' => array( 'type' => 'string', 'value' => get_post_time( 'c', true, $post ) ),
			'modified' => array( 'type' => 'string', 'value' => get_post_modified_time( 'c', true, $post ) ),
			'status' => array( 'type' => 'string', 'value' => $post->post_status ),
			'comment_status' => array( 'type' => 'string', 'value' => $post->comment_status ),
			'comment_count' => array( 'type' => 'int', 'value' => 0 ),
			'menu_order' => array( 'type' => 'int', 'value' => 0 ),
			'title' => array( 'type' => 'string', 'value' => $post->post_title ),
			'name' => array( 'type' => 'string', 'value' => $post->post_name ),
			'excerpt' => array( 'type' => 'string' ),
			'excerpt_display' => array( 'type' => 'string' ),
			'content' => array( 'type' => 'string' ),
			'content_display' => array( 'type' => 'string' ),
			'author' => array( 'type' => 'object' ),
			'mime_type' => array( 'type' => 'string', 'value' => '' ),
			'meta' => array( 'type' => 'object' ),
			'taxonomies' => array( 'type' => 'object' ),
			'media' => array( 'type' => 'array' )
		);

		foreach ( $checks as $attrib => $check ) {
			$this->assertObjectHasAttribute( $attrib, $data );
			$this->assertInternalType( $check['type'], $data->$attrib );
			if ( isset( $check['value'] ) ) {
				$this->assertEquals( $check['value'], $data->$attrib );
			}
		}

		$this->assertEquals( 2, count( $data->media ) );



		$id = 9999999;

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '404', $status );

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

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => http_build_query( $test_args ),
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertEquals( 1, count( $data->posts ) );
	}

}
