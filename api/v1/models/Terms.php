<?php

namespace Voce\Thermal\v1;

class TermsModel {

	public function find( $taxonomy, $args = array( ), &$found = null ) {

		//setup paging
		if ( empty( $request_args['per_page'] ) || absint($request_args['per_page']) > MAX_TERMS_PER_PAGE ) {
			$number = MAX_TERMS_PER_PAGE;
		} else {
			$number = absint( $request_args['per_page'] );
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