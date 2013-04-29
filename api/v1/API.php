<?php

namespace WP_JSON_API;

class API extends API_Base {

	protected $version = '1';

	public function __construct() {
		parent::__construct( new \Slim\Slim() );
		$this->registerRoute( 'GET', WP_API_BASE . '/users/:id', array( __CLASS__, 'users' ) );
		$this->registerRoute( 'GET', WP_API_BASE . '/posts', array( __CLASS__, 'posts' ) );
	}

	public static function users( $id = null ) {
		echo WP_API_BASE . '/users/' . $id;
	}

	public static function posts() {
		echo WP_API_BASE . '/posts';
	}
}
