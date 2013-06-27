<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../APITestCase.php' );

class TermsControllerTest extends APITestCase {

	protected function _insertTestData() {

		register_taxonomy( 'public_taxonomy_a', array( 'post', 'page' ), array(
			'public' => true,
		) );

		register_taxonomy( 'private_taxonomy_a', array( 'post' ), array(
			'public' => false,
		) );

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
			'post_excerpt' => 'This is the excerpt.',
			'post_content' => 'This is the content.',
			'post_title' => 'Hello World B!',
			'post_date' => '2013-04-30 20:33:36',
			'post_date_gmt' => '2013-04-30 20:33:36',
			'comment_status' => 'open',
			) );

		$term_a = wp_create_term( 'Term In Both', 'public_taxonomy_a' );
		$term_b = wp_create_term( 'Term In A', 'public_taxonomy_a' );
		$term_c = wp_create_term( 'Term In B', 'public_taxonomy_a' );
		$term_d = wp_create_term( 'Term In None', 'public_taxonomy_a' );

		$term_e = wp_create_term( 'Term In Private', 'private_taxonomy_a' );

		wp_set_object_terms( $post_id_a, array( intval( $term_a['term_id'] ), intval( $term_b['term_id'] ) ), 'public_taxonomy_a' );
		wp_set_object_terms( $post_id_b, array( intval( $term_c['term_id'] ) ), 'public_taxonomy_a' );
		wp_set_object_terms( $post_id_a, array( intval( $term_e['term_id'] ) ), 'private_taxonomy_a' );

		return compact( 'post_id_a', 'post_id_b', 'term_a', 'term_b', 'term_c', 'term_d', 'term_e' );
	}

	public function testGetTerms() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'terms', $data );
		$this->assertInternalType( 'array', $data->terms );
		$this->assertObjectNotHasAttribute( 'found', $data );
		$this->assertEquals( 3, count( $data->terms ) );
	}

	public function testGetTermsCount() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/',
			'QUERY_STRING' => http_build_query( array( 'include_found' => true ) ),
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'found', $data );
		$this->assertEquals( 3, $data->found );



		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/',
			'QUERY_STRING' => 'paged=1',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'found', $data );
		$this->assertEquals( 3, $data->found );
	}

	public function testGetTerm() {
		$testdata = $this->_insertTestData();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/' . $testdata['term_a']['term_id'],
			'QUERY_STRING' => ''
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'name', $data );
		$this->assertEquals( 'Term In Both', $data->name );

		$id = 9999999;

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/' . $id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		
		$this->assertEquals( '404', $status );
	}
	
	public function testGetTermEntityFilter() {
		$testdata = $this->_insertTestData();

		add_filter('thermal_term_entity',  function($data, $term, $state) {
			$data->test_value = $term->term_id;
			return $data;
		}, 10, 3);
		
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/' . $testdata['term_a']['term_id'],
			'QUERY_STRING' => ''
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'name', $data );
		$this->assertEquals( 'Term In Both', $data->name );
		$this->assertObjectHasAttribute('test_value', $data);
		$this->assertEquals($testdata['term_a']['term_id'], $data->test_value);

		$id = 9999999;

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a/terms/' . $id,
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		
		$this->assertEquals( '404', $status );
	}

}
