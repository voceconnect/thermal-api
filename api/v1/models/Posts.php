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
		
		$wp_posts = new \WP_Query( $args );

		if ( $wp_posts->have_posts() ) {
			$found = ( int ) $wp_posts->found_posts;
			return $wp_posts->posts;
		}
		return array();
		
	}
	
	public function findById($id) {
		return get_post($id);
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