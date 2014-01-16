<?php

namespace Voce\Thermal\v1\Controllers;

class Taxonomies {

	private static $_model;

	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new \Voce\Thermal\v1\Models\Taxonomies();
		}
		return self::$_model;
	}

	public static function find( $app ) {
		$request_args = $app->request()->get();

		$args = self::convert_request( $request_args );

		$model = self::model();

		$taxonomies = $model->find( $args );

		$taxonomies = array_filter( $taxonomies, function($taxonomy) {
				if ( !$taxonomy->public ) {
					if ( is_user_logged_in() || !current_user_can( $taxonomy->cap->manage_terms ) ) {
							return false;
					}
				}
				return true;
			} );

		array_walk( $taxonomies, array( __CLASS__, 'format' ), 'read' );
		$taxonomies = array_values($taxonomies);
		return compact( 'taxonomies' );
	}

	public static function findById( $app, $id ) {
		$taxonomy = self::model()->findById( $id );
		if ( !$taxonomy ) {
			$app->halt( '404', get_status_header_desc( '404' ) );
		}

		if ( !$taxonomy->public ) {
			if ( is_user_logged_in() ) {
				if ( !current_user_can( $taxonomy->cap->manage_terms, $taxonomy->ID ) ) {
					$app->halt( '403', get_status_header_desc( '403' ) );
				}
			} else {
				$app->halt( '401', get_status_header_desc( '401' ) );
			}
		}

		self::format( $taxonomy, 'read' );
		return $taxonomy;
	}

	/**
	 * Filter and validate the parameters that will be passed to the model.
	 * @param array $request_args
	 * @return array
	 */
	protected static function convert_request( $request_args ) {
		// Remove any args that are not allowed by the API
		$request_filters = array(
			'in' => array( '\\Voce\\Thermal\\v1\\toArray' ),
			'post_type' => array( '\\Voce\\Thermal\\v1\\toArray' ),
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

		return $request_args;
	}

	/**
	 *
	 * @param \WP_Post $taxonomy
	 */
	public static function format( &$taxonomy, $state = 'read' ) {
		if ( !$taxonomy ) {
			return $taxonomy = null;
		}

		$data = array(
			'name' => $taxonomy->name,
			'post_types' => $taxonomy->object_type,
			'hierarchical' => $taxonomy->hierarchical,
			'queryVar' => $taxonomy->query_var,
			'labels' => $taxonomy->labels,
			'meta' => new \stdClass()
		);

		$taxonomy = apply_filters_ref_array( 'thermal_taxonomy_entity', array( ( object ) $data, &$taxonomy, $state ) );
	}

}

?>
