<?php

namespace WP_JSON_API;

class API extends API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $slim ) {
		parent::__construct( $slim );
		$this->registerRoute( 'GET', 'users/:id', array( __CLASS__, 'users' ) );
		$this->registerRoute( 'GET', 'posts', array( __CLASS__, 'posts' ) );
	}

	public static function users( $id = null ) {
		echo WP_API_BASE . '/users/' . $id;
	}

	public static function posts() {
		echo WP_API_BASE . '/posts';
	}
}
