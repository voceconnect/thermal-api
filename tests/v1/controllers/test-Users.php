<?php

class UsersControllerTest extends APITestCase {

	private function getTestUserData() {
		return array(
			array(
				'user_login' => 'test1_login',
				'user_pass' => wp_generate_password(),
				'user_email' => 'test1@example.org',
				'user_nicename' => 'test1',
				'user_url' => 'http://example.org',
				'display_name' => 'Test User1',
				'description' => 'Test Description',
				'first_name' => 'Test',
				'last_name' => 'Last',
				'nickname' => 'test_nick',
				'role' => 'administrator'
			),
			array(
				'user_login' => 'test2_login',
				'user_pass' => wp_generate_password(),
				'user_email' => 'test2@example.org',
				'user_nicename' => 'test2',
				'user_url' => 'http://example.org',
				'display_name' => 'Test User2',
				'description' => 'Test Description',
				'role' => 'editor'
			),
			array(
				'user_login' => 'test3_login',
				'user_pass' => wp_generate_password(),
				'user_email' => 'test3@example.org',
				'user_nicename' => 'test3',
				'user_url' => 'http://example.org',
				'display_name' => 'Test User3',
				'description' => 'Test Description',
				'role' => 'author'
			),
			array(
				'user_login' => 'test4_login',
				'user_pass' => wp_generate_password(),
				'user_email' => 'test4@example.org',
				'user_nicename' => 'test4',
				'user_url' => 'http://example.org',
				'display_name' => 'Test User4',
				'description' => 'Test Description',
				'role' => 'subscriber'
			),
		);
	}

	public function testGetUsers() {

		$users = $this->getTestUserData();
		$user = $users[0];
		$user['role'] = 'subscriber';
		$user['id'] = wp_insert_user($user);

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/users/',
			'QUERY_STRING' => 'who=authors',
			) );

		$data = json_decode( $body );
		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );
		$this->assertObjectHasAttribute( 'users', $data );
		$this->assertInternalType( 'array', $data->users );
		$this->assertGreaterThanOrEqual( 1, count( $data->users ) );
		$this->assertObjectNotHasAttribute( 'found', $data );

		//clean up
		wp_delete_user($user['id']);
	}

	public function testGetUser() {
		$users = $this->getTestUserData();
		$user = $users[0];

		$user['role'] = 'editor';
		$user['id'] = wp_insert_user($user);

		list($status, $headers, $body) = $this->_getResponse( array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => Voce\Thermal\get_api_base() . 'v1/users/' . $user['id'],
			'QUERY_STRING' => '',
			) );

		$data = json_decode( $body );

		$this->assertEquals( '200', $status );
		$this->assertInternalType( 'object', $data );

		$this->assertObjectHasAttribute('id', $data );
		$this->assertInternalType( 'int', $data->id );
		$this->assertEquals( $user['id'], $data->id );

		$this->assertObjectHasAttribute('id_str', $data );
		$this->assertInternalType( 'string', $data->id_str );
		$this->assertEquals( (string) $user['id'], $data->id_str );

		$this->assertObjectHasAttribute('nicename', $data );
		$this->assertInternalType( 'string', $data->nicename);
		$this->assertEquals( $user['user_nicename'], $data->nicename );

		$this->assertObjectHasAttribute('display_name', $data );
		$this->assertInternalType( 'string', $data->display_name );
		$this->assertEquals( $user['display_name'], $data->display_name );

		$this->assertObjectHasAttribute('user_url', $data );
		$this->assertInternalType( 'string', $data->user_url );
		$this->assertEquals( $user['user_url'], $data->user_url );

		$this->assertObjectHasAttribute('posts_url', $data );
		$this->assertInternalType( 'string', $data->posts_url );

		$this->assertObjectHasAttribute('avatar', $data );
		$this->assertInternalType( 'array', $data->avatar );

		$this->assertObjectHasAttribute('meta', $data );
		$this->assertInternalType( 'object', $data->meta );

		$this->assertObjectHasAttribute('first_name', $data->meta );
		$this->assertEquals( $user['first_name'], $data->meta->first_name );

		$this->assertObjectHasAttribute('last_name', $data->meta );
		$this->assertEquals( $user['last_name'], $data->meta->last_name );

		$this->assertObjectHasAttribute('nickname', $data->meta );
		$this->assertEquals( $user['nickname'], $data->meta->nickname );

		$this->assertObjectHasAttribute('description', $data->meta );
		$this->assertEquals( $user['description'], $data->meta->description );

		$data = json_decode( $body );

		//clean up
		wp_delete_user($user['id']);
	}
}


