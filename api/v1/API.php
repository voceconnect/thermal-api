<?php

namespace Voce\Thermal\v1;

if ( !defined( __NAMESPACE__ . '\\MAX_POSTS_PER_PAGE' ) ) {
	define( __NAMESPACE__ . '\\MAX_POSTS_PER_PAGE', 100 );
}

if ( !defined( __NAMESPACE__ . '\\MAX_TERMS_PER_PAGE' ) ) {
	define( __NAMESPACE__ . '\\MAX_TERMS_PER_PAGE', 100 );
}

if ( !defined( __NAMESPACE__ . '\\MAX_USERS_PER_PAGE' ) ) {
	define( __NAMESPACE__ . '\\MAX_USERS_PER_PAGE', 100 );
}

if ( !defined( __NAMESPACE__ . '\\MAX_COMMENTS_PER_PAGE' ) ) {
	define( __NAMESPACE__ . '\\MAX_COMMENTS_PER_PAGE', 100 );
}

/**
 *
 */
class API extends \Voce\Thermal\API_Base {

	protected $version = '1';

	/**
	 * Register the allowed routes.
	 * @param \Slim\Slim $app
	 */
	public function __construct( \Slim\Slim $app ) {
		parent::__construct( $app );
		$this->registerRoute( 'GET', 'posts/?', array( __NAMESPACE__ . '\\controllers\\Posts', 'find' ) );
		$this->registerRoute( 'GET', 'posts/:id/?', array( __NAMESPACE__ . '\\controllers\\Posts', 'findById' ) );
		$this->registerRoute( 'GET', 'posts/:id/comments/?', array( __NAMESPACE__ . '\\controllers\\Comments', 'findByPost' ) );
		$this->registerRoute( 'GET', 'users/?', array( __NAMESPACE__ . '\\controllers\\Users', 'find' ) );
		$this->registerRoute( 'GET', 'users/:id/?', array( __NAMESPACE__ . '\\controllers\\Users', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/?', array( __NAMESPACE__ . '\\controllers\\Taxonomies', 'find' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/?', array( __NAMESPACE__ . '\\controllers\\Taxonomies', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/:taxonomy_name/terms/?', array( __NAMESPACE__ . '\\controllers\\Terms', 'find' ) );
		$this->registerRoute( 'GET', 'taxonomies/:taxonomy_name/terms/:term_id/?', array( __NAMESPACE__ . '\\controllers\\Terms', 'findById' ) );
		$this->registerRoute( 'GET', 'rewrite_rules/?', array(  __NAMESPACE__ . '\\controllers\\RewriteRules', 'find' ) );
		$this->registerRoute( 'GET', 'comments/?', array( __NAMESPACE__ . '\\controllers\\Comments', 'find' ) );
		$this->registerRoute( 'GET', 'comments/:id?', array( __NAMESPACE__ . '\\controllers\\Comments', 'findById' ) );
	}

}

function toBool( $value ) {
	return ( bool ) $value;
}

function toArray( $value ) {
	return ( array ) $value;
}

function applyInt( $value ) {
	return array_map( 'intval', $value );
}

function toCommaSeparated( $value ) {
	return implode( ',', $value );
}
