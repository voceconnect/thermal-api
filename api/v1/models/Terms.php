<?php

namespace Voce\Thermal\v1\Models;

class Terms {

	public function find( $taxonomy, $args = array( ), &$found = null ) {

		//setup paging
		if ( empty( $args['per_page'] ) || absint($args['per_page']) > \Voce\Thermal\v1\MAX_TERMS_PER_PAGE ) {
			$number = \Voce\Thermal\v1\MAX_TERMS_PER_PAGE;
		} else {
			$number = absint( $args['per_page'] );
		}
		if ( isset( $args['offset'] ) ) {
			$offset = $args['offset'];
		} elseif ( isset( $args['paged'] ) ) {
			$offset = ( absint( $args['paged'] ) - 1) * $number;
		} else {
			$offset = 0;
		}

		$term_args = array_merge( $args, array(
			'offset' => $offset,
			'number' => $number
			) );

		$terms = get_terms( $taxonomy, $term_args );

		if ( !empty( $args['include_found'] ) ) {
			$found = get_terms( $taxonomy, array_merge( $args, array(
				'fields' => 'count'
				) ) );
		}
		return array_values( $terms );
	}

	public function findById( $taxonomy, $id ) {
		return get_term( $id, $taxonomy );
	}

}