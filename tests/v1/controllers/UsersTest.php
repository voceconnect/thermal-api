<?php

define( 'Voce\\Thermal\\API_BASE', 'api' );
define( 'WP_USE_THEMES', false );

require_once( __DIR__ . '/../../APITestCase.php' );


class UsersControllerTest extends APITestCase {

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
