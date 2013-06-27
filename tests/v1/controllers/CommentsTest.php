<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../APITestCase.php' );

class CommentsControllerTest extends APITestCase {

	protected function _insertTestData() {

		$post_id_a = wp_insert_post( array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_author' => 1,
			'post_parent' => 0,
			'menu_order' => 0,
			'post_content_filtered' => '',
			'post_excerpt' => 'This is the excerpt.',
			'post_content' => 'This is the content.',
			'post_title' => 'Hello World A!',
			'post_date' => '2013-04-30 20:33:36',
			'post_date_gmt' => '2013-04-30 20:33:36',
			'comment_status' => 'open',
			) );

		$post_id_b = wp_insert_post( array(
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_author' => 1,
			'post_parent' => 0,
			'menu_order' => 0,
			'post_content_filtered' => '',
			'post_excerpt' => 'This is the excerpt 2.',
			'post_content' => 'This is the content 2.',
			'post_title' => 'Hello World A!',
			'post_date' => '2013-04-30 20:33:36',
			'post_date_gmt' => '2013-04-30 20:33:36',
			'comment_status' => 'open',
			) );

		$comment_approved_minus_10 = wp_insert_comment( array(
			'comment_post_ID' => $post_id_a,
			'comment_author' => 'Some Guy',
			'comment_author_email' => 'bob@example.org',
			'comment_content' => 'This is my comment text',
			'user_id' => 1,
			'comment_date' => gmdate( 'Y-m-d H:i:s', ( time() - (10 * MINUTE_IN_SECONDS) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
			'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', ( time() - (10 * MINUTE_IN_SECONDS) ) ),
			'comment_approved' => 1,
			) );

		$comment_approved_minus_20 = wp_insert_comment( array(
			'comment_post_ID' => $post_id_a,
			'comment_author' => 'Some Guy',
			'comment_author_email' => 'bob@example.org',
			'comment_content' => 'This is my earlier comment text',
			'user_id' => 1,
			'comment_date' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
			'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) ) ),
			'comment_approved' => 1,
			) );

		$comment_approved_no_user = wp_insert_comment( array(
			'comment_post_ID' => $post_id_a,
			'comment_author' => 'Some Guy 3',
			'comment_author_email' => 'bobbob@example.org',
			'comment_content' => 'This is another comment text',
			'user_id' => 1,
			'comment_date' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
			'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) ) ),
			'comment_approved' => 1,
			) );

		$comment_pending_minus_20 = wp_insert_comment( array(
			'comment_post_ID' => $post_id_a,
			'comment_author' => 'Some Guy 2',
			'comment_author_email' => 'bob2@example.org',
			'comment_content' => 'This is my pending comment text',
			'user_id' => null,
			'comment_date' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
			'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) ) ),
			'comment_approved' => 0,
			) );

		$comment_approved_post_b = wp_insert_comment( array(
			'comment_post_ID' => $post_id_b,
			'comment_author' => 'Some Guy 2',
			'comment_author_email' => 'bob2@example.org',
			'comment_content' => 'This is my pending comment text',
			'user_id' => null,
			'comment_date' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
			'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', ( time() - (20 * MINUTE_IN_SECONDS) ) ),
			'comment_approved' => 1,
			) );

		return compact( 'post_id_a', 'post_id_b', 'comment_approved_minus_10', 'comment_approved_minus_20', 'comment_pending_minus_20', 'comment_approved_post_b' );
	}

	public function testGetComments() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'comments', $data );
		$this->assertInternalType( 'array', $data->comments );
		$this->assertObjectNotHasAttribute( 'found', $data );
	}

	public function testGetCommentsCount() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => http_build_query( array( 'include_found' => true ) ),
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'found', $data );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => 'paged=1',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'found', $data );
	}

	public function testGetCommentsBefore() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => http_build_query( array( 'before' => gmdate( 'Y-m-d H:i:s', ( time() - (15 * MINUTE_IN_SECONDS) ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) )
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );

		$found_comment_minus_10 = false;
		$found_comment_minus_20 = false;
		foreach ( $data->comments as $comment ) {
			if ( $comment->id == $testdata['comment_approved_minus_10'] ) {
				$found_comment_minus_10 = true;
				var_dump( $comment );
			}
			if ( $comment->id == $testdata['comment_approved_minus_20'] ) {
				$found_comment_minus_20 = true;
			}
		}

		$this->assertFalse( $found_comment_minus_10 );
		$this->assertTrue( $found_comment_minus_20 );
	}

	public function testGetCommentsAfter() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => http_build_query( array( 'after' => gmdate( 'Y-m-d H:i:s', ( time() - (15 * MINUTE_IN_SECONDS) ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) )
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );

		$found_comment_minus_10 = false;
		$found_comment_minus_20 = false;
		foreach ( $data->comments as $comment ) {
			if ( $comment->id == $testdata['comment_approved_minus_10'] ) {
				$found_comment_minus_10 = true;
			}
			if ( $comment->id == $testdata['comment_approved_minus_20'] ) {
				$found_comment_minus_20 = true;
			}
		}

		$this->assertTrue( $found_comment_minus_10 );
		$this->assertFalse( $found_comment_minus_20 );
	}

	public function testGetCommentsSearch() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => http_build_query( array( 's' => 'my comment text' ) )
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );

		$found_comment_minus_10 = false;
		$found_comment_minus_20 = false;
		foreach ( $data->comments as $comment ) {
			if ( $comment->id == $testdata['comment_approved_minus_10'] ) {
				$found_comment_minus_10 = true;
			}
			if ( $comment->id == $testdata['comment_approved_minus_20'] ) {
				$found_comment_minus_20 = true;
			}
		}

		$this->assertTrue( $found_comment_minus_10 );
		$this->assertFalse( $found_comment_minus_20 );
	}

	public function testGetCommentsByPost() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/posts/' . $testdata['post_id_a'] . '/comments/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );

		$found_comment_minus_10 = false;
		$found_comment_approved_post_b = false;
		foreach ( $data->comments as $comment ) {
			if ( $comment->id == $testdata['comment_approved_minus_10'] ) {
				$found_comment_minus_10 = true;
			}
			if ( $comment->id == $testdata['comment_approved_post_b'] ) {
				$found_comment_approved_post_b = true;
			}
		}

		$this->assertTrue( $found_comment_minus_10 );
		$this->assertTrue( $found_comment_approved_post_b );
	}

	public function testGetCommentsIn() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/',
			'QUERY_STRING' => http_build_query( array( 'in' => array( $testdata['comment_approved_minus_10'] ) ) )
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );

		$found_comment_minus_10 = false;
		$found_comment_minus_20 = false;
		foreach ( $data->comments as $comment ) {
			if ( $comment->id == $testdata['comment_approved_minus_10'] ) {
				$found_comment_minus_10 = true;
			}
			if ( $comment->id == $testdata['comment_approved_minus_20'] ) {
				$found_comment_minus_20 = true;
			}
		}

		$this->assertTrue( $found_comment_minus_10 );
		$this->assertFalse( $found_comment_minus_20 );
	}

	public function testGetComment() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/' . $testdata['comment_approved_minus_10'],
			'QUERY_STRING' => ''
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );

		$checks = array(
			'id' => array( 'type' => 'int', 'value' => $testdata['comment_approved_minus_10'] ),
			'id_str' => array( 'type' => 'string', 'value' => $testdata['comment_approved_minus_10'] ),
			'type' => array( 'type' => 'string', 'value' => 'comment' ),
			'author' => array( 'type' => 'string', 'value' => 'Some Guy' ),
			'author_url' => array( 'type' => 'string' ),
			'parent' => array( 'type' => 'int', 'value' => 0 ),
			'parent_str' => array( 'type' => 'string', 'value' => '0' ),
			'date' => array( 'type' => 'string' ),
			'content' => array( 'type' => 'string', 'value' => 'This is my comment text' ),
			'status' => array( 'type' => 'string', 'value' => 'approve' ),
			'user' => array( 'type' => 'int', 'value' => '1' ),
			'content_display' => array( 'type' => 'string', 'value' => "<p>This is my comment text</p>\n" ),
			'avatar' => array( 'type' => 'array' ),
		);

		foreach ( $checks as $attrib => $check ) {
			$this->assertObjectHasAttribute( $attrib, $data );
			$this->assertInternalType( $check['type'], $data->$attrib );
			if ( isset( $check['value'] ) ) {
				$this->assertEquals( $check['value'], $data->$attrib );
			}
		}

		$id = 9999999;

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/' . $id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '404', $status );
	}

	public function testGetCommentEntityFilter() {
		$testdata = $this->_insertTestData();
		add_filter( 'thermal_comment_entity', function($data, &$comment, $state) {
				$data->test_value = $comment->comment_ID;
				return $data;
			}, 10, 3 );
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/comments/' . $testdata['comment_approved_minus_10'],
			'QUERY_STRING' => ''
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'test_value', $data );
		$this->assertEquals( $testdata['comment_approved_minus_10'], $data->test_value );
	}

}
