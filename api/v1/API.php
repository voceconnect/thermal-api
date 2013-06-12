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

require_once( __DIR__ . '/../API_Base.php' );
require_once( __DIR__ . '/controllers/Posts.php');
require_once( __DIR__ . '/controllers/Users.php');
require_once( __DIR__ . '/controllers/Taxonomies.php');
require_once( __DIR__ . '/controllers/Terms.php');
require_once( __DIR__ . '/controllers/RewriteRules.php');
require_once( __DIR__ . '/controllers/Comments.php');

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
		$this->registerRoute( 'GET', 'posts/?', array( __NAMESPACE__ . '\\PostsController', 'find' ) );
		$this->registerRoute( 'GET', 'posts/:id/?', array( __NAMESPACE__ . '\\PostsController', 'findById' ) );
		$this->registerRoute( 'GET', 'posts/:id/comments/?', array( __NAMESPACE__ . '\\CommentsController', 'findByPost' ) );
		$this->registerRoute( 'GET', 'users/?', array( __NAMESPACE__ . '\\UsersController', 'find' ) );
		$this->registerRoute( 'GET', 'users/:id/?', array( __NAMESPACE__ . '\\UsersController', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/?', array( __NAMESPACE__ . '\\TaxonomiesController', 'find' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/?', array( __NAMESPACE__ . '\\TaxonomiesController', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/:taxonomy_name/terms/?', array( __NAMESPACE__ . '\\TermsController', 'find' ) );
		$this->registerRoute( 'GET', 'taxonomies/:taxonomy_name/terms/:term_id/?', array( __NAMESPACE__ . '\\TermsController', 'findById' ) );
		$this->registerRoute( 'GET', 'rewrite_rules/?', array(  __NAMESPACE__ . '\\RewriteRulesController', 'find' ) );
		$this->registerRoute( 'GET', 'comments/?', array( __NAMESPACE__ . '\\CommentsController', 'find' ) );
		$this->registerRoute( 'GET', 'comments/:id?', array( __NAMESPACE__ . '\\CommentsController', 'findById' ) );
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
