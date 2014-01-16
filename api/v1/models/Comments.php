<?php

namespace Voce\Thermal\v1\Models;

class Comments {

	public function find( $args = array( ), &$found = null ) {

		//add filter for before/after handling, hopefully more complex date querying
		//will exist by wp3.7
		if ( isset( $args['before'] ) || isset( $args['after'] ) ) {
			add_filter( 'comments_clauses', array( __CLASS__, '_filter_comments_clauses_handleDateRange' ), 10, 2 );
		}

		//setup paging
		if ( empty( $args['per_page'] ) ) {
			$number = get_option( 'comments_per_page' );
			if ( $number < 1 )
				$number = \Voce\Thermal\v1\MAX_COMMENTS_PER_PAGE;
		}
		if ( isset( $args['offset'] ) ) {
			$offset = $args['offset'];
		} elseif ( isset( $args['paged'] ) ) {
			$offset = ( absint( $args['paged'] ) - 1) * $number;
		} else {
			$offset = 0;
		}

		//normalize search arg
		if ( isset( $args['s'] ) ) {
			$args['search'] = $args['s'];
			unset( $args['s'] );
		}

		//allow 'in' arg
		if ( !empty( $args['in'] ) ) {
			add_filter( 'comments_clauses', array( __CLASS__, '_filter_comments_clauses_handleInArg' ), 10, 2 );
		}

		//status handling
		if ( empty( $args['status'] ) ) {
			$args['status'] = 'approve';
		}

		//make sure count isn't set to true
		$args['count'] = false;

		$wp_comments = new \WP_Comment_Query();

		$comments = $wp_comments->query( $args );

		if ( !empty( $args['include_found'] ) ) {
			$args['count'] = true;
			//@todo - counts don't cache in core
			$found = $wp_comments->query( $args );
		}

		return $comments;
	}

	public function findById( $id ) {
		return get_comment( $id );
	}

	public static function _filter_comments_clauses_handleDateRange( $clauses, &$comment_query ) {
		$query_vars = $comment_query->query_vars;

		if ( isset( $query_vars['before'] ) && ($before = $query_vars['before']) && ($beforets = strtotime( $before ) ) ) {
			if ( preg_match( '$:[0-9]{2}\s[+-][0-9]{2}$', $before ) || strpos( $before, 'GMT' ) !== false ) {
				//adjust to site time if a timezone was set in the timestamp
				$beforets += ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}
			$clauses['where'] .= sprintf( " AND comment_date < '%s'", gmdate( 'Y-m-d H:i:s', $beforets ) );
		}
		if ( isset( $query_vars['after'] ) && ($after = $query_vars['after']) && ($afterts = strtotime( $after )) ) {
			if ( preg_match( '$:[0-9]{2}\s[+-][0-9]{2}$', $after ) || strpos( $after, 'GMT' ) !== false ) {
				//adjust to site time if a timezone was set in the timestamp
				$afterts += ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}

			$clauses['where'] .= sprintf( " AND comment_date > '%s'", gmdate( 'Y-m-d H:i:s', $afterts ) );
		}
		
		return $clauses;
	}

	public static function _filter_comments_clauses_handleInArg( $clauses, &$comment_query ) {
		$query_vars = $comment_query->query_vars;
		if ( !empty( $query_vars['in'] ) ) {
			$ids = ( array ) $query_vars['in'];
			$ids = array_map( '\\intval', $ids );
			$clauses['where'] .= ' AND comment_ID in (' . implode( ', ', $ids ) . ') ';
		}
		return $clauses;
	}

}