<?php
namespace Voce\Thermal\v1;

class PostsModel {

	public function find( $args = array( ), &$found = null ) {
		
		//add filter for before/after handling, hopefully more complex date querying
		//will exist by wp3.7
		if ( isset( $args['before'] ) || isset( $args['after'] ) ) {
			add_filter( 'posts_where', array( __CLASS__, '_filter_posts_where_handleDateRange' ), 10, 2 );
		}
		
		if(isset($args['per_page'])) {
			$args['posts_per_page'] = $args['per_page'];
			unset($args['per_page']);
		}
		
		// I would prefer the permissions handling to be in the controller
		// rather than the model, but WP_Query is a dirty bit of code that
		// doesn't quite give the flexibility needed with it's query_vars
		add_action( 'parse_query', array( __CLASS__, '_force_public_post_status' ) );
		$wp_posts = new \WP_Query( $args );
		remove_action( 'parse_query', array( __CLASS__, '_force_public_post_status' ) );

		if ( $wp_posts->have_posts() ) {
			$found = ( int ) $wp_posts->found_posts;
			return $wp_posts->posts;
		}
		return array();
		
	}
	
	public function findById($id) {
		return get_post($id);
	}
	
	

	/**
	 * 'post_requests' action, force invalid post status to return empty request
	 * @return string
	 */
	public static function _force_blank_request() {
		throw new Exception( "NOT USED" );
		return '';
	}

	/**
	 * 'parse_query' action, force public post_status values for API requests
	 * @param $wp_query
	 */
	public static function _force_public_post_status( $wp_query ) {

		$qv = &$wp_query->query_vars;

		$invalid_status = false;

		// post_status may not be set, give it an empty array to avoid warning when we access it later
		if ( !isset( $qv['post_status'] ) )
			$qv['post_status'] = array( );

		// if a non-public post_status was requested, no posts should be returned
		// remove the stati for now, and set a flag so we can filter the results
		if ( !empty( $qv['post_status'] ) ) {

			$non_public_stati = array_values( get_post_stati( array( 'public' => false ) ) );

			$qv['post_status'] = ( array ) $qv['post_status'];

			$before_count = count( $qv['post_status'] );
			$qv['post_status'] = array_diff( $qv['post_status'], $non_public_stati );

			$invalid_status = ( $before_count !== count( $qv['post_status'] ) );
		}

		// ensure post_status contains valid registered stati
		$post_stati = get_post_stati();
		$qv['post_status'] = array_intersect( $post_stati, ( array ) $qv['post_status'] );

		// if a user tried to specify a non-public post_status, and no valid post_status values
		// remain after filtering, force an empty result set
		if ( empty( $qv['post_status'] ) && $invalid_status ) {
			add_filter( 'posts_request', array( __CLASS__, '_force_blank_request' ) );
		}
	}

	public static function _filter_posts_where_handleDateRange( $where, $wp_query ) {
		if ( ($before = $wp_query->get( 'before' ) ) && $beforets = strtotime( $before ) ) {
			if ( preg_match( '$:[0-9]{2}\s[+-][0-9]{2}$', $before ) || strpos( $before, 'GMT' ) !== false ) {
				//adjust to site time if a timezone was set in the timestamp
				$beforets += ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}

			$where .= sprintf( " AND post_date < '%s'", gmdate( 'Y-m-d H:i:s', $beforets ) );
		}
		if ( ($after = $wp_query->get( 'after' ) ) && $afterts = strtotime( $after ) ) {
			if ( preg_match( '$:[0-9]{2}\s[+-][0-9]{2}$', $after ) || strpos( $after, 'GMT' ) !== false ) {
				//adjust to site time if a timezone was set in the timestamp
				$afterts += ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}

			$where .= sprintf( " AND post_date > '%s'", gmdate( 'Y-m-d H:i:s', $afterts ) );
		}
		return $where;
	}

}