<?php

namespace WP_JSON_API;

class APIv1 extends API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $slim ) {
		parent::__construct( $slim );
		$this->registerRoute( 'GET', 'users(/:id)', array( __CLASS__, 'get_users' ) );
		$this->registerRoute( 'GET', 'posts(/:id)', array( __CLASS__, 'get_posts' ) );
		$this->registerRoute( 'GET', 'taxonomies(/:name)', array( __CLASS__, 'get_taxonomies' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms(/:term_id)', array( __CLASS__, 'get_terms' ) );
	}

	public static function get_users( $id = null ) {
		return WP_API_BASE . '/users/' . $id;
	}

	public static function get_posts( $id = null ) {
		return WP_API_BASE . '/posts/' . $id;
	}

	public static function get_taxonomies( $name = null ) {
		return WP_API_BASE . '/taxonomies/' . $name;
	}

	public static function get_terms( $name, $term_id = null ) {
		return WP_API_BASE . '/taxonomies/' . $name . '/terms/' . $term_id;
	}
}
