<?php

namespace Voce\Thermal\v1\Models;

class Taxonomies {

	public function find( $args = array( ) ) {
		$taxonomies = get_taxonomies( array( ), 'objects' );

		if ( !empty( $args['in'] ) ) {
			$in = $args['in'];
			$taxonomies = array_filter( $taxonomies, function($taxonomy) use ($in) {
					return in_array( $taxonomy->name, $in );
				} );
		}

		if ( !empty( $args['post_type'] ) ) {
			$post_types = $args['post_type'];
			$taxonomies = array_filter( $taxonomies, function($taxonomy) use ($post_types) {
					foreach ( $post_types as $post_type ) {
						foreach ( $taxonomy->object_type as $object_type ) {
							if ( $object_type == $post_type || 0 === strpos( $object_type, $post_type . ':' ) ) {
								return true;
							}
						}
					}
					return false;
				} );
		}
		
		return array_values($taxonomies);
	}

	public function findById( $name ) {
		return get_taxonomy( $name );
	}


}