<?php

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

	public function testGetPostsStatus() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$this->assertEquals( '200', $status );
	}

	public function testGetPostsContentTypeHeaderJSON() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$this->assertTrue( $headers->offsetExists( 'Content-Type' ) );
		$this->assertStringStartsWith( 'application/json', $headers['Content-Type'] );
	}

	public function testGetPostsContentTypeHeaderJSONP() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'callback=test',
			) );

		$this->assertTrue( $headers->offsetExists( 'Content-Type' ) );
		$this->assertStringStartsWith( 'application/javascript', $headers['Content-Type'] );
	}

	public function testLastGetPostsIfModifiedResponse() {
		$this->_insert_post();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$last_modified = $headers['last-modified'];

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			'IF_MODIFIED_SINCE' => $last_modified
			) );

		$this->assertEquals( '304', $status );
		$this->assertEmpty( $body );
	}

	public function testLastGetPostsLastModifiedHeader() {
		$this->_insert_post();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$this->assertTrue( $headers->offsetExists( 'Last-Modified' ) );
		$this->assertNotEmpty( $headers['last-modified'] );
	}

	public function testGetPostsValidJSON() {
		$this->_insert_post();
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertInternalType( 'object', $data );
	}

	/**
	 * @depends testGetPostsValidJSON
	 */
	public function testGetPostsNotHasFound() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertObjectNotHasAttribute( 'found', $data );
	}

	/**
	 * @depends testGetPostsValidJSON
	 */
	public function testGetPostsHasFoundTrue() {
		$this->_insert_post();
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'include_found=1',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'found', $data );
		$this->assertEquals( 1, $data->found );
	}

	/**
	 * @depends testGetPostsValidJSON
	 */
	public function testGetPostsHasFoundForPaging() {
		$this->_insert_post();
		$this->_insert_post();
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'paged=2&per_page=1',
			) );

		$data = json_decode( $body );
		$this->assertObjectHasAttribute( 'found', $data );
		$this->assertEquals( 2, $data->found );
	}

	/**
	 * @depends testGetPostsValidJSON
	 */
	public function testGetPostsHasPostsArray() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertCount(0, $data->posts);
	}

	/**
	 * @depends testGetPostsValidJSON
	 */
	public function testGetPostsHasPostsContent() {
		$this->_insert_post();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertCount(1, $data->posts);
	}

	public function testGetAttachments() {
		wp_set_current_user( 1 );
		$upload = $this->_upload_file( dirname( dirname( __DIR__ ) ) . '/data/250x250.png' );
		$attachment_id = $this->_make_attachment( $upload );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'post_type=attachment',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertNotEmpty( $data->posts );
		$this->assertObjectNotHasAttribute( 'found', $data );
		wp_set_current_user( 0 );
	}

	public function testGetPostsByPostStatusFutureUnprivelaged() {
		$post_id = $this->_insert_post( array(
			'post_status' => 'future',
			'post_date' => date( 'Y-m-d H:i:s', time() + 604800 ),
			'post_date_gmt' => '',
			) );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'post_status=future&post__in=' . $post_id,
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertCount( 0, $data->posts );
		$this->assertObjectNotHasAttribute( 'found', $data );
	}

	public function testGetPostsByPostStatusFuturePrivelaged() {
		wp_set_current_user( 1 );

		$post_id = $this->_insert_post( array(
			'post_status' => 'future',
			'post_date' => date( 'Y-m-d H:i:s', time() + 604800 ),
			'post_date_gmt' => '',
			) );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'post_status=future&post__in=' . $post_id,
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertCount( 1, $data->posts );
		$this->assertObjectNotHasAttribute( 'found', $data );
		wp_set_current_user( 0 ); //log back out for other tests.

	}

	public function testGetPostsPerPage() {
		$this->_insert_post();
		$this->_insert_post();
		$this->_insert_post();
		$this->_insert_post();
		$this->_insert_post();
		$this->_insert_post();
		$this->_insert_post();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'per_page=2&include_found=1',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertObjectHasAttribute( 'found', $data );
		$this->assertCount( 2, $data->posts );
	}

	public function testGetPostsByPostTypeDefaultPublicPostTypes() {
		$this->_insert_post(array('post_type'=> 'page'));
		$this->_insert_post(null, array('250x250.png'));

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		//should have a post for each post, page, attachment as all are publicly_queryable
		$this->assertCount( 3, $data->posts );
	}

	public function testGetPostsByPostTypePost() {
		$this->_insert_post(array('post_type'=> 'page'));
		$this->_insert_post(null, array('250x250.png'));

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => 'post_type=post&include_found=1',
			) );

		$data = json_decode( $body );

		$this->assertObjectHasAttribute( 'found', $data );
		$this->assertCount( 1, $data->posts );
	}


	public function testGetPost() {

		//add media item to test unattached images in content
		$upload = $this->_upload_file( dirname( dirname( __DIR__ ) ) . '/data/250x250.png' );
		$att_id = $this->_make_attachment( $upload );
		$imgHtml = get_image_send_to_editor( $att_id, 'Test Caption', 'Test Title', 'left' );

		$content = "Proin nec risus a metus mattis eleifend. Quisque ullamcorper porttitor aliquam. " .
			"Donec ut vulputate diam. Etiam eu dui pretium, condimentum nisi eu, tincidunt elit. \n\n" .
			$imgHtml . " \n\n Morbi ipsum dolor, tristique quis lorem sit amet, blandit ornare arcu. " .
			"Phasellus facilisis varius porttitor. Nam gravida neque eros, id pellentesque nibh aliquam a.";
		//get_image_send_to_editor
		$test_data = $this->_insert_post( array( 'post_content' => $content ), array( '100x200.png', '100x300.png' ) );

		$upload = $this->_upload_file( dirname( dirname( __DIR__ ) ) . '/data/100x500.png' );
		$att_id = $this->_make_attachment( $upload );
		set_post_thumbnail( $test_data['post_id'], $att_id );

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
			//'id_str' => array( 'type' => 'string', 'value' => $post->ID ),
			'type' => array( 'type' => 'string', 'value' => 'post' ),
			'permalink' => array( 'type' => 'string', 'value' => get_permalink( $post->ID ) ),
			'parent' => array( 'type' => 'int', 'value' => $post->post_parent ),
			//'parent_str' => array( 'type' => 'string', 'value' => $post->post_parent ),
			'date' => array( 'type' => 'string', 'value' => get_post_time( 'c', true, $post ) ),
			'modified' => array( 'type' => 'string', 'value' => get_post_modified_time( 'c', true, $post ) ),
			'status' => array( 'type' => 'string', 'value' => $post->post_status ),
			'comment_status' => array( 'type' => 'string', 'value' => $post->comment_status ),
			'comment_count' => array( 'type' => 'int', 'value' => 0 ),
			'menu_order' => array( 'type' => 'int', 'value' => 0 ),
			'title' => array( 'type' => 'string', 'value' => $post->post_title ),
			'name' => array( 'type' => 'string', 'value' => $post->post_name ),
			'excerpt' => array( 'type' => 'string' ),
			//'excerpt_display' => array( 'type' => 'string' ),
			'content' => array( 'type' => 'string' ),
			//'content_display' => array( 'type' => 'string' ),
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

		$this->assertEquals( 4, count( $data->media ) );
	}

	public function testGetPostNotExist() {
		$id = 9999999;

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '404', $status );
	}

	public function testGetPostHasPermissionForDraftStatus() {
		wp_set_current_user(1);
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
		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		wp_set_current_user(0);
	}

	public function testGetPostNotHasPermissionForDraftStatus() {
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

	public function testGetPostLastModified() {
		$test_data = $this->_insert_post();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $test_data['post_id'],
			'QUERY_STRING' => '',
			) );

		$this->assertEquals( '200', $status );
		$this->assertNotEmpty( $headers['last-modified'] );
		$last_modified = $headers['last-modified'];

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $test_data['post_id'],
			'QUERY_STRING' => '',
			'IF_MODIFIED_SINCE' => $last_modified
			) );

		$this->assertEquals( '304', $status );
		$this->assertEmpty( $body );
	}

	public function testGetPostEntityFilter() {
		$test_data = $this->_insert_post( null, array( '100x200.png', '100x300.png' ) );

		add_filter( 'thermal_post_entity', function($data, &$post, $state) {
				$data->test_value = $post->ID;
				return $data;
			}, 10, 3 );


		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $test_data['post_id'],
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'test_value', $data );
		$this->assertEquals( $test_data['post_id'], $data->test_value );
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

	public function testGetPostsCustomTaxonomy() {
		register_taxonomy( 'test_tax', 'post', array( 'public' => true ) );
		$post_id = $this->_insert_post();

		if ( $term_obj = get_term_by( 'name', 'Test Term', 'test_tax' ) ) {
			$term_id = $term_obj->term_id;
		} else {
			$term_data = wp_insert_term( 'Test Term', 'test_tax' );
			$term_id = $term_data['term_id'];
		}
		wp_set_object_terms( $post_id, 'Test Term', 'test_tax' );

		$args = array(
			'taxonomy' => array( 'test_tax' => array( $term_id ) )
		);
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts',
			'QUERY_STRING' => http_build_query( $args )
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'posts', $data );
		$this->assertInternalType( 'array', $data->posts );
		$this->assertObjectNotHasAttribute( 'found', $data );
	}

	public function testPostMetaGallery() {
		$post_content = 'Lorem Ipsum [gallery]';
		$post_args = array( 'post_content' => $post_content );
		$post_images = array( '100x200.png', '100x300.png', '100x400.png' );
		$post_data = $this->_insert_post( $post_args, $post_images );
		$post_id = $post_data['post_id'];
		$attachment_ids = $post_data['attachment_ids'];

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $post_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( $attachment_ids, $data->meta->gallery[0]->ids );
	}

	public function testGetPostGalleryExclude() {
		$post_content = 'Lorem Ipsum';
		$post_args = array( 'post_content' => $post_content );
		$post_images = array( '100x200.png', '100x300.png', '100x400.png' );
		$post_data = $this->_insert_post( $post_args, $post_images );
		$post_id = $post_data['post_id'];
		$attachment_ids = $post_data['attachment_ids'];

		$this->_insert_post(
			array(
				'ID'           => $post_id,
				'post_content' => sprintf( '[gallery exclude="%d"]', array_shift($attachment_ids) ),
			)
		);

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $post_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( $attachment_ids, $data->meta->gallery[0]->ids );
	}

	public function testGetPostGallerySort() {
		$post_content = 'Lorem Ipsum [gallery order="DESC" orderby="ID"]';
		$post_args = array( 'post_content' => $post_content );
		$post_images = array( '100x200.png', '100x300.png', '100x400.png' );
		$post_data = $this->_insert_post( $post_args, $post_images );
		$post_id = $post_data['post_id'];
		$attachment_ids = $post_data['attachment_ids'];

		rsort($attachment_ids);

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $post_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( 'ID', $data->meta->gallery[0]->orderby[0] );
		$this->assertEquals( 'DESC', $data->meta->gallery[0]->order );
		$this->assertEquals( $attachment_ids, $data->meta->gallery[0]->ids );
	}

	public function testGetPostGalleryID() {
		$post_content = 'Lorem Ipsum';
		$post_args = array( 'post_content' => $post_content );
		$post_images = array( '100x200.png', '100x300.png', '100x400.png' );
		$post_data = $this->_insert_post( $post_args, $post_images );
		$post_id = $post_data['post_id'];
		$attachment_ids = $post_data['attachment_ids'];

		$post_2_content = sprintf( 'Lorem Ipsum [gallery id="%d"]', $post_id );
		$post_2_args = array( 'post_content' => $post_2_content );
		$post_2_id = $this->_insert_post( $post_2_args );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $post_2_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( $attachment_ids, $data->meta->gallery[0]->ids );
	}

	public function testGetPostGalleryIDs() {
		$post_content = 'Lorem Ipsum';
		$post_args = array( 'post_content' => $post_content );
		$post_images = array( '100x200.png', '100x300.png', '100x400.png' );
		$post_data = $this->_insert_post( $post_args, $post_images );
		$post_id = $post_data['post_id'];
		$attachment_ids = $post_data['attachment_ids'];

		array_shift($attachment_ids);

		$this->_insert_post(
			array(
				'ID'           => $post_id,
				'post_content' => sprintf( '[gallery ids="%s"]', implode(',', $attachment_ids) )
			)
		);

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $post_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( $attachment_ids, $data->meta->gallery[0]->ids );
	}

	public function testGetPostGalleryInclude() {
		$post_content = 'Lorem Ipsum';
		$post_args = array( 'post_content' => $post_content );
		$post_images = array( '100x200.png', '100x300.png', '100x400.png' );
		$post_data = $this->_insert_post( $post_args, $post_images );
		$post_id = $post_data['post_id'];
		$attachment_ids = $post_data['attachment_ids'];

		array_shift($attachment_ids);

		$this->_insert_post(
			array(
				'ID'           => $post_id,
				'post_content' => sprintf( '[gallery include="%s"]', implode(',', $attachment_ids) )
			)
		);

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $post_id,
			'QUERY_STRING' => '',
		) );

		$data = json_decode( $body );

		$this->assertEquals( $attachment_ids, $data->meta->gallery[0]->ids );
	}

}
