<?php

namespace Voce\Thermal\v1;

require_once(__DIR__ . '/../models/Terms.php');

class TermsController {

	private static $_model;

	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new TermsModel();
		}
		return self::$_model;
	}

	public static function find( $app, $taxonomy_name ) {
		$taxonomy = TaxonomiesController::findById( $app, $taxonomy_name );

		$found = 0;

		$request_args = $app->request()->get();

		$args = self::convert_request( $request_args );

		$model = self::model();

		$terms = $model->find( $taxonomy->name, $args, $found );

		array_walk( $terms, array( __CLASS__, 'format' ), 'read' );
		$terms = array_values( $terms );
		return empty( $args['include_found'] ) ? compact( 'terms' ) : compact( 'terms', 'found' );
	}

	public static function findById( $app, $taxonomy_name, $id ) {
		$taxonomy = TaxonomiesController::findById( $app, $taxonomy_name );

		$term = self::model()->findById( $taxonomy_name, $id );
		if ( !$term ) {
			$app->halt( '404', get_status_header_desc( '404' ) );
		}
		self::format( $term, 'read' );
		return $term;
	}

	/**
	 * Filter and validate the parameters that will be passed to the model.
	 * @param array $request_args
	 * @return array
	 */
	protected static function convert_request( $request_args ) {
		// Remove any args that are not allowed by the API
		$request_filters = array(
			'paged' => array( ),
			'per_page' => array( '\\intval' ),
			'offset' => array( '\\intval' ),
			'orderby' => array( ),
			'order' => array( ),
			'in' => array( __NAMESPACE__ . '\\toArray', __NAMESPACE__ . '\\applyInt', __NAMESPACE__ . '\\toCommaSeparated' ),
			'slug' => array( ),
			'parent' => array( '\\intval' ),
			'hide_empty' => array( __NAMESPACE__ . '\\toBool' ),
			'pad_counts' => array( __NAMESPACE__ . '\\toBool' ),
			'include_found' => array( __NAMESPACE__ . '\\toBool' )
		);
		//strip any nonsafe args
		$request_args = array_intersect_key( $request_args, $request_filters );

		//run through basic sanitation
		foreach ( $request_args as $key => $value ) {
			foreach ( $request_filters[$key] as $callback ) {
				$value = call_user_func( $callback, $value );
			}
			$request_args[$key] = $value;
		}

		//convert 'in' to 'include'
		if ( !empty( $request_args['in'] ) ) {
			$request_args['include'] = $request_args['in'];
			unset( $request_args['in'] );
		}

		if ( !empty( $request_args['paged'] ) && empty( $request_args['include_found'] ) ) {
			$request_args['include_found'] = true;
		}

		return $request_args;
	}

	/**
	 * 
	 * @param \WP_Post $term
	 */
	public static function format( &$term, $state = 'read' ) {
		if ( !$term ) {
			return $term = null;
		}

		//allow for use with array_walk
		if ( func_num_args() > 2 ) {
			$state = func_get_arg( func_num_args() - 1 );
		}
		if ( !in_array( $state, array( 'read', 'new', 'edit' ) ) ) {
			$state = 'read';
		}

		$data = array(
			'name' => $term->name,
			'slug' => $term->slug,
			'parent' => intval( $term->parent ),
			'parent_str' => ( string ) $term->parent,
			'description' => $term->description,
			'post_count' => intval( $term->count ),
		);

		if ( $state == 'read' ) {
			$data = array_merge( $data, array(
				'id' => intval( $term->term_id ),
				'term_id_str' => ( string ) $term->term_id,
				'term_taxonomy_id' => intval( $term->term_taxonomy_id ),
				'term_taxonomy_id_str' => ( string ) $term->term_taxonomy_id,
				'taxonomy' => $term->taxonomy,
				'post_count' => intval( $term->count ),
				'meta' => new \stdClass()
				) );
		}
		
		$term = apply_filters_ref_array( 'thermal_term_entity', array( ( object ) $data, &$term, $state ) );
	}

}

?>
