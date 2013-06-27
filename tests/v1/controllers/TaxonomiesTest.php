<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../APITestCase.php' );

class TaxonomiesControllerTest extends APITestCase {

	protected function _registerTaxonomies() {
		register_taxonomy( 'public_taxonomy_a', array( 'post', 'page' ), array(
			'public' => true,
		) );

		register_taxonomy( 'public_taxonomy_b', array( 'page' ), array(
			'public' => true,
		) );

		register_taxonomy( 'private_taxonomy_a', array( 'post' ), array(
			'public' => false,
		) );
	}

	public function testGetTaxonomies() {
		$this->_registerTaxonomies();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'taxonomies', $data );
		$this->assertInternalType( 'array', $data->taxonomies );
	}

	public function testGetTaxonomiesByIds() {
		$this->_registerTaxonomies();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/',
			'QUERY_STRING' => http_build_query( array( 'in' => array( 'public_taxonomy_a', 'public_taxonomy_b' ) ) ),
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'taxonomies', $data );
		$this->assertCount( 2, $data->taxonomies );
	}

	public function testGetTaxonomiesExcludePrivate() {
		$this->_registerTaxonomies();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/',
			'QUERY_STRING' => http_build_query( array( 'in' => array( 'public_taxonomy_a', 'public_taxonomy_b', 'private_taxonomy_a' ) ) ),
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'taxonomies', $data );
		$this->assertCount( 2, $data->taxonomies );
	}

	public function testGetTaxonomiesByType() {
		$this->_registerTaxonomies();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/',
			'QUERY_STRING' => http_build_query( array( 'post_type' => array( 'post' ), 'in' => array( 'public_taxonomy_a', 'public_taxonomy_b', 'private_taxonomy_a' ) ) ),
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'taxonomies', $data );
		$this->assertCount( 1, $data->taxonomies );
	}

	public function testGetTaxonomy() {
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'name', $data );
		$this->assertEquals( 'public_taxonomy_a', $data->name );
	}

	public function testGetTaxonomyEntityFilter() {
		add_filter( 'thermal_taxonomy_entity', function($data, &$taxonomy, $state) {
				$data->test_value = $taxonomy->name;
				return $data;
			}, 10, 3 );

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/taxonomies/public_taxonomy_a',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'name', $data );
		$this->assertEquals( 'public_taxonomy_a', $data->name );
		$this->assertObjectHasAttribute( 'test_value', $data );
		$this->assertEquals( 'public_taxonomy_a', $data->test_value );
	}

}
