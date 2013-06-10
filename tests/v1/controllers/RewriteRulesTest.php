<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../APITestCase.php' );

class RewriteRulesControllerTest extends APITestCase {

	public function testGetRewriteRules() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$wp_rewrite->flush_rules();
		
		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/rewrite_rules/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertObjectHasAttribute( 'base_url', $data );
		$this->assertEquals( 'http://' . trim( WP_TESTS_DOMAIN, '/' ) . '/', $data->base_url );
		$this->assertObjectHasAttribute( 'rewrite_rules', $data );
		$this->assertInternalType( 'array', $data->rewrite_rules );
	}

	public function testGetRewriteRulesNotSet() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '' );
		$wp_rewrite->flush_rules();

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/rewrite_rules/',
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '404', $status );
	}

}
