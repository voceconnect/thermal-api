<?php

namespace Voce\Thermal\v1;

if ( !defined( 'MAX_POSTS_PER_PAGE' ) ) {
	define( 'MAX_POSTS_PER_PAGE', 100 );
}

if ( !defined( 'MAX_TERMS_PER_PAGE' ) ) {
	define( 'MAX_TERMS_PER_PAGE', 100 );
}

if ( !defined( 'MAX_USERS_PER_PAGE' ) ) {
	define( 'MAX_USERS_PER_PAGE', 100 );
}

require_once( __DIR__ . '/../API_Base.php' );
require_once( __DIR__ . '/controllers/Posts.php');
require_once( __DIR__ . '/controllers/Users.php');
require_once( __DIR__ . '/controllers/Taxonomies.php');

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
		$this->registerRoute( 'GET', 'posts/(:id)/?', array( __NAMESPACE__ . '\\PostsController', 'findById' ) );
		$this->registerRoute( 'GET', 'users/?', array( __NAMESPACE__ . '\\UsersController', 'find' ) );
		$this->registerRoute( 'GET', 'users/(:id)/?', array( __NAMESPACE__ . '\\UsersController', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/?', array( __NAMESPACE__ . '\\TaxonomiesController', 'find' ) );
		$this->registerRoute( 'GET', 'taxonomies/?(:name)/?', array( __NAMESPACE__ . '\\TaxonomiesController', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms/?(:term_id)/?', array( $this, 'get_terms' ) );
		$this->registerRoute( 'GET', 'rewrite_rules/?', array( $this, 'get_rewrite_rules' ) );
	}

	/**
	 * taxonomies/:id endpoint.
	 * @param string $name [optional]
	 * @return array
	 */
	public function get_taxonomies( $name = null ) {
		$args = array(
			'public' => true,
		);

		if ( !is_null( $name ) ) {
			$args['name'] = $name;
		}

		$t = get_taxonomies( $args, 'object' );
		$args = $this->app->request()->get();
		$taxonomies = array( );
		foreach ( $t as $taxonomy ) {
			if ( isset( $args['in'] ) ) {
				if ( !in_array( $taxonomy->name, ( array ) $args['in'] ) ) {
					continue;
				}
			}

			if ( isset( $args['post_type'] ) ) {
				if ( 0 === count( array_intersect( $taxonomy->object_type, ( array ) $args['post_type'] ) ) ) {
					continue;
				}
			}

			$taxonomies[] = $this->format_taxonomy( $taxonomy );
		}

		return compact( 'taxonomies' );
	}

	/**
	 * taxonomies/:taxonomy/terms/:term endpoint.
	 * @param string $name
	 * @param int $term_id [optional]
	 * @return array
	 */
	public function get_terms( $name, $term_id = null ) {
		$found = 0;

		$request = $this->app->request();
		$request_args = $request->get();
		$args = self::get_terms_args( $request_args, $term_id );

		$include_found = filter_var( $request->get( 'include_found' ), FILTER_VALIDATE_BOOLEAN );
		$include_found = ( $include_found || $request->get( 'paged' ) );

		$terms = array_map( array( __CLASS__, 'format_term' ), get_terms( $name, $args ) );

		if ( $include_found && count( $terms ) ) {
			$found = ( int ) get_terms( $name, array_merge( $args, array( 'fields' => 'count' ) ) );
		}

		return $include_found ? compact( 'found', 'terms' ) : compact( 'terms' );
	}

	/**
	 * Filter and validate the parameters that will be passed to get_terms.
	 * @param array $request_args
	 * @param int $term_id [optional]
	 * @return array
	 */
	public static function get_terms_args( $request_args, $term_id = null ) {
		$args = array( );

		$args['number'] = MAX_TERMS_PER_PAGE;

		foreach ( array( 'parent', 'offset' ) as $int_var ) {
			if ( isset( $request_args[$int_var] ) &&
				is_int( $value = filter_var( $request_args[$int_var], FILTER_VALIDATE_INT ) ) ) {
				$args[$int_var] = max( 0, $value );
			}
		}

		foreach ( array( 'hide_empty', 'pad_counts' ) as $bool_var ) {
			if ( isset( $request_args[$bool_var] ) ) {
				$args[$bool_var] = filter_var( $request_args[$bool_var], FILTER_VALIDATE_BOOLEAN );
			}
		}

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] >= 1 ) {
			$args['number'] = min( ( int ) $request_args['per_page'], $args['number'] );
		}

		if ( !empty( $request_args['paged'] ) && $request_args['paged'] >= 1 ) {
			$args['offset'] = ( ( int ) $request_args['paged'] - 1 ) * $args['number'];
		}

		$valid_orderby = array( 'name', 'slug', 'count' );
		if ( !empty( $request_args['orderby'] ) && in_array( strtolower( $request_args['orderby'] ), $valid_orderby ) ) {
			$args['orderby'] = strtolower( $request_args['orderby'] );
		}

		$valid_order = array( 'asc', 'desc' );
		if ( !empty( $request_args['order'] ) && in_array( strtolower( $request_args['order'] ), $valid_order ) ) {
			$args['order'] = strtolower( $request_args['order'] );
		}

		if ( !is_null( $term_id ) ) {

			$args['include'] = array( ( int ) $term_id );
		} else if ( !empty( $request_args['include'] ) ) {

			$args['include'] = array_values( array_filter( array_map( 'intval', ( array ) $request_args['include'] ) ) );
		}

		if ( !empty( $request_args['slug'] ) ) {
			$args['slug'] = $request_args['slug'];
		}

		return $args;
	}

	/**
	 * Format the output of a taxonomy.
	 * @param $taxonomy
	 * @return array
	 */
	public function format_taxonomy( $taxonomy ) {
		return array(
			'name' => $taxonomy->name,
			'post_types' => $taxonomy->object_type,
			'hierarchical' => $taxonomy->hierarchical,
			'query_var' => $taxonomy->query_var,
			'labels' => array(
				'name' => $taxonomy->labels->name,
				'singular_name' => $taxonomy->labels->singular_name,
			),
			'meta' => ( object ) array( ),
		);
	}

	/**
	 * @return array
	 */
	public function get_rewrite_rules() {
		$base_url = home_url( '/' );
		$rewrite_rules = array( );

		$rules = get_option( 'rewrite_rules', array( ) );
		foreach ( $rules as $regex => $query ) {
			$patterns = array( '|index\.php\?&?|', '|\$matches\[(\d+)\]|' );
			$replacements = array( '', '\$$1' );

			$rewrite_rules[] = array(
				'regex' => $regex,
				'query_expression' => preg_replace( $patterns, $replacements, $query ),
			);
		}

		return compact( 'base_url', 'rewrite_rules' );
	}

	/**
	 * Format the output of a term.
	 * @param $term
	 * @return array
	 */
	public static function format_term( $term ) {
		return array(
			'id' => ( int ) $term->term_id,
			'id_str' => $term->term_id,
			'term_taxonomy_id' => ( int ) $term->term_taxonomy_id,
			'term_taxonomy_id_str' => $term->term_taxonomy_id,
			'parent' => ( int ) $term->parent,
			'parent_str' => $term->parent,
			'name' => $term->name,
			'slug' => $term->slug,
			'taxonomy' => $term->taxonomy,
			'description' => $term->description,
			'post_count' => ( int ) $term->count,
			'meta' => ( object ) array( ),
		);
	}

}
