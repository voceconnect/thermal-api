<?php
namespace Voce\Thermal\v1\Models;

class Users {

	public function find( $args = array( ), &$found = null) {
		$wp_users = new \WP_User_Query($args);
		if($wp_users->results) {
			$found = $wp_users->total_users;
			return $wp_users->results;
		}

		return array();
		
	}
	
	public function findById($id) {
		return get_user_by( 'id', $id );
	}

}