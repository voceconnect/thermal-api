<?php
namespace Voce\Thermal\v1\Models;

class Posts {

	public function find( $args = array( ), &$found = null ) {
		
		//add filter for before/after handling, hopefully more complex date querying
		//will exist by wp3.7
		if ( isset( $args['before'] ) || isset( $args['after'] ) ) {
			add_filter( 'posts_where', array( $this, '_filter_posts_where_handleDateRange' ), 10, 2 );
		}

		if( isset( $args['post_type'] ) && in_array('attachment', (array) $args['post_type'])) {
			if(empty($args['post_status'])) {
				$args['post_status'] = array('inherit');
			} else {
				$args['post_status'] = array_merge((array) $args['post_status'], array('inherit'));
			}
		}
		
		if( empty( $args['post_status'] ) ) {
			//a post_status is required
			return array();
		}
		
		if(isset($args['per_page'])) {
			$args['posts_per_page'] = $args['per_page'];
			unset($args['per_page']);
		}
		$wp_posts = new \WP_Query( $args );
		$found = ( int ) $wp_posts->found_posts;

		if ( $wp_posts->have_posts() ) {
			return $wp_posts->posts;
		}
		return array();
		
	}
	
	public function findById($id) {
		return get_post($id);
	}
	
	public function _filter_posts_where_handleDateRange( $where, $wp_query ) {
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
		remove_filter('posts_search', array($this, __METHOD__));
		return $where;
	}
	
}