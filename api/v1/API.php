<?php

namespace WP_JSON_API;

if ( ! defined( 'MAX_POSTS_PER_PAGE' ) )
	define( 'MAX_POSTS_PER_PAGE', 10 );

if ( ! defined( 'MAX_USERS_PER_PAGE' ) ) {
	define( 'MAX_USERS_PER_PAGE', 10 );
}

require_once( __DIR__ . '/../API_Base.php' );

class APIv1 extends API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $app ) {
		parent::__construct( $app );
		$this->registerRoute( 'GET', 'posts(/:id)', array( $this, 'get_posts' ) );
		$this->registerRoute( 'GET', 'users(/:id)', array( $this, 'get_users' ) );
		$this->registerRoute( 'GET', 'taxonomies(/:name)', array( $this, 'get_taxonomies' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms(/:term_id)', array( $this, 'get_terms' ) );
	}

	public function get_posts( $id = null ) {

		$found = 0;
		$posts = array();

		$request = $this->app->request();
		$args = $request->get();
		$wp_query_posts = $this->get_post_query( $request, $id );

		if ( $wp_query_posts->have_posts() ) {
			$found = $wp_query_posts->found_posts;
			foreach ( $wp_query_posts->posts as $query_post ) {
				$posts[] = $this->format_post( $query_post );
			}
		}

		return ! empty( $args['include_found'] ) ? compact( 'found', 'posts' ) : compact( 'posts' );
	}

	/**
	 * @param \Slim\Http\Request $request
	 * @param int $id
	 * @return WP_Query
	 */
	public function get_post_query( \Slim\Http\Request $request, $id = null ) {

		$force_public_post_stati = function( $wp_query ){
			$qv = &$wp_query->query_vars;

			$invalid_status = false;

			// verify post_status query var exists
			if ( !isset( $qv['post_status'] ) )
				$qv['post_status'] = '';

			// gets rid of non public stati
			if ( !empty( $qv['post_status'] ) ) {
				$non_public_stati = array_values( get_post_stati( array( 'public' => false ) ) );

				$qv['post_status'] = (array)$qv['post_status'];

				// noting count before and after to check if a non valid status was specified
				$before_count = count( $qv['post_status'] );
				$qv['post_status'] = array_diff( (array)$qv['post_status'], $non_public_stati );
				$after_count = count( $qv['post_status'] );

				$invalid_status = ( $before_count !== $after_count );
			}

			// validates status is an actual status
			$post_stati = get_post_stati();
			$qv['post_status'] = array_intersect( $post_stati, (array)$qv['post_status'] );

			// if no post status is set and and invalid status was specified
			// we want to return no results
			if ( empty( $qv['post_status'] ) && $invalid_status ) {
				add_filter( 'posts_request', function() {
					return '';
				});
			}
		};

		add_action('parse_query', $force_public_post_stati );

		$args = $request->get();

		$defaults = array(
			'found_posts' => false,
		);

		if ( ! is_null( $id ) ) {
			$args['p'] = (int)$id;
			$single_post_query = new \WP_Query( array_merge( $defaults, $args ) );
			remove_action('parse_query', $force_public_post_stati );
			return $single_post_query;
		}

		if ( isset( $args['taxonomy'] ) && is_array( $args['taxonomy'] ) ) {
			$args['tax_query'] = array(
				'relation' => 'OR',
			);

			foreach ( $args['taxonomy'] as $key => $value ) {
				$args['tax_query'][] = array(
					'taxonomy' => $key,
					'terms' => is_array( $value ) ? $value : array(),
					'field' => 'term_id',
				);
			}
		}

		if ( isset( $args['after'] ) ) {
			$date = date('Y-m-d', strtotime( $args['after'] ) );
			add_filter( 'posts_where', function( $where ) use ( $date ) {
				$where .= " AND post_date > '$date'";
				return $where;
			} );
		}

		if ( isset( $args['before'] ) ) {
			$date = date('Y-m-d', strtotime( $args['before'] ) );
			add_filter( 'posts_where', function( $where ) use ( $date ) {
				$where .= " AND post_date < '$date'";
				return $where;
			} );
		}

		if ( isset( $args['author'] ) ) {
			// WordPress only allows a single author to be excluded. We are not
			// allowing any author exculsions to be accepted.
			$r = array_filter( (array)$args['author'], function( $author ) {
				return $author > 0;
			} );
			$args['author'] = implode( ',', $r );
		}

		if ( isset( $args['cat'] ) ) {
			$args['cat'] = implode( ',', (array)$args['cat'] );
		}

		$valid_orders = array(
			'none',
			'ID',
			'author',
			'title',
			'name',
			'date',
			'modified',
			'parent',
			'rand',
			'comment_count',
			'menu_order',
			'post__in',
		);

		if ( isset( $args['orderby'] ) ) {
			$r = array_filter( (array)$args['orderby'], function( $orderby ) use ($valid_orders) {
				return in_array( $orderby, $valid_orders );
			} );
			$args['orderby'] = implode( ' ', $r );
		}

		if ( isset( $args['per_page'] ) ) {
			if ( $args['per_page'] >= 1 and $args['per_page'] <= MAX_POSTS_PER_PAGE ) {
				$args['posts_per_page'] = (int)$args['per_page'];
			}
		}

		if ( isset ( $args['paged'] ) ) {
			$args['found_posts'] = true;
		}

		$get_posts = new \WP_Query( array_merge( $defaults, $args ) );
		remove_action('parse_query', $force_public_post_stati );
		return $get_posts;
	}

	/**
	 * users/:id route
	 * @param $id
	 * @return Array
	 */
	public function get_users( $id = null ) {
		$users = array();
		if ( $id ) {
			$users[] = self::format_user( get_user_by( 'id', $id ) );
			return compact( 'users' );
		}

		$args = $this->get_user_args( $this->app->request()->get() );

		if ( $args['include_found'] ) {
			$count = count_users();
			$found = $count['total_users'];
		}

		$users = array_map( array( __CLASS__, 'format_user' ), get_users( $args ) );

		return isset( $found ) ? compact( 'found', 'users' ) : compact( 'users' );
	}
	/**
	 * Filter and validate the parameters that will be passed to get_users
	 * @param $args [optional]
	 * @return array
	 */
	public static function get_user_args( $args = array() ) {
		$_args = array(
			'number'        => MAX_USERS_PER_PAGE,
			'offset'        => 0,
			'orderby'       => 'display_name',
			'order'         => 'desc',
			'include'       => array(),
			'include_found' => false,
		);

		// The maximum number of posts to return. The value must range from
		// 1 to MAX_USERS_PER_PAGE.
		if ( isset( $args['per_page'] ) && (int)$args['per_page'] > 0 ) {
			$_args['number'] = min( (int)$args['per_page'], $_args['number'] );
		}

		// The number of posts to skip over before returning the result set.
		if ( isset( $args['offset'] ) && (int)$args['offset'] > 0 ) {
			$_args['offset'] = (int)$args['offset'];
		}

		// A positive integer specifying the page (or subset of results) to
		// return. This filter will automatically determine the offset to use
		// based on the per_page and paged. Using this filter will cause
		// include_found to be true.
		if ( isset( $args['paged'] ) && (int)$args['paged'] > 0 ) {
			$_args['include_found'] = true;
			$_args['offset'] = ( (int)$args['paged'] - 1 ) * (int)$_args['number'];
		}

		// Sort the results by the given identifier. Defaults to 'display_name'.
		// Supported values are:
		//   'display_name' - Ordered by the display name of the user.
		//   'nicename'     - The slug/nicename of the user.
		//   'post_count'   - The number of posts the user has.
		$valid = array(
			'display_name',
			'nicename',
			'post_count',
		);

		if ( isset( $args['orderby'] ) ) {
			$orderby          = array_map( 'strtolower', (array)$args['orderby'] );
			$_args['orderby'] = array_values( array_intersect( $valid, $orderby ) );
		}

		//The order direction. Options are 'ASC' and 'DESC'. Default is 'DESC'
		$valid = array(
			'asc',
			'desc',
		);

		if ( isset( $args['order'] ) && in_array( strtolower( $args['order'] ), $valid ) ) {
			$_args['order'] = strtolower( $args['order'] );
		}

		// An array of user ID's to include.
		if ( isset( $args['include'] ) ) {
			$_args['include'] = (array)$args['include'];
		}

		// Default to false. When true, the response will include a found rows
		// count. There is some overhead in generating the total count so this
		// should only be turned on when needed. This is automatically turned on
		//  if the 'paged' filter is used.
		if ( isset( $args['include_found'] ) ) {
			$_args['include_found'] = filter_var( $args['include_found'], FILTER_VALIDATE_BOOLEAN );
		}

		return $_args;
	}

	public function get_taxonomies( $name = null ) {
		return WP_API_BASE . '/taxonomies/' . $name;
	}

	public function get_terms( $name, $term_id = null ) {
		return WP_API_BASE . '/taxonomies/' . $name . '/terms/' . $term_id;
	}

	/**
	 * Format post data
	 * @param \WP_Post $post
	 * @return Array Formatted post data
	 */
	public function format_post( \WP_Post $post ) {
		$GLOBALS['post'] = $post;

		$attachments = get_posts( array(
			'post_parent' => $post->ID,
			'post_mime_type' => 'image',
			'post_type' => 'attachment',
		) );
		$media = array();
		foreach ( $attachments as $attachment ) {
			$media[] = self::format_image_media_item( $attachment );
		}

		setup_postdata( $post );
		$data = array(
			'id'               => $post->ID,
			'id_str'           => (string)$post->ID,
			'type'             => $post->post_type,
			'permalink'        => get_permalink( $post ),
			'parent'           => $post->post_parent,
			'parent_str'       => (string)$post->post_parent,
			'date'             => get_post_time( 'c', true, $post ),
			'modified'         => get_post_modified_time( 'c', true, $post ),
			'status'           => $post->post_status,
			'comment_status'   => $post->comment_status,
			'comment_count'    => (int)$post->comment_count,
			'menu_order'       => $post->menu_order,
			'title'            => $post->post_title,
			'name'             => $post->post_name,
			'excerpt_raw'      => $post->post_excerpt,
			'excerpt'          => apply_filters( 'the_excerpt', get_the_excerpt() ),
			'content_raw'      => $post->post_content,
			'content'          => apply_filters( 'the_content', get_the_content() ),
			'content_filtered' => $post->post_content_filtered,
			'mime_type'        => $post->post_mime_type,
			'meta'             => (object)array(),
			'media'            => $media,
		);

		if ( $thumbnail_id = get_post_thumbnail_id( $post->ID ) ) {
			$data['meta']->featured_image = (int)$thumbnail_id;
		}

		wp_reset_postdata();

		return $data;
	}

	/**
	 * Format user data
	 * @param \WP_User $user
	 * @return Array Formatted user data
	 */
	public static function format_user( \WP_User $user ) {
		$avatar = get_avatar( $user->ID );
		preg_match( "/src='([^']*)'/i", $avatar, $matches );

		return array(
			'id'           => $user->ID,
			'id_str'       => (string)$user->ID,
			'nicename'     => $user->data->user_nicename,
			'display_name' => $user->data->display_name,
			'user_url'     => $user->data->user_url,
			'posts_url'    => get_author_posts_url( $user->ID ),
			'avatar'       => array(
				array(
					'url'    => array_pop( $matches ),
					'width'  => 96,
					'height' => 96,
				)
			),
			'meta'         => (object)array()
		);
	}

	/**
	 * @param \WP_Post $post
	 * @return Array
	 */
	public static function format_image_media_item( \WP_Post $post ) {
		$meta = wp_get_attachment_metadata( $post->ID );

		if ( isset( $meta['sizes'] ) and is_array( $meta['sizes'] ) ) {
			$upload_dir = wp_upload_dir();

			$sizes = array(
				array(
					'height' => $meta['height'],
					'name'   => 'full',
					'url'    => trailingslashit( $upload_dir['baseurl'] ) . $meta['file'],
					'width'  => $meta['width'],
				),
			);

			$attachment_upload_date = dirname($meta['file']);

			foreach ( $meta['sizes'] as $size => $data ) {
				$sizes[] = array(
					'height' => $data['height'],
					'name'   => $size,
					'url'    => trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( $attachment_upload_date ) . $data['file'],
					'width'  => $data['width'],
				);
			}
		}

		return array(
			'id'        => $post->ID,
			'id_str'    => (string)$post->ID,
			'mime_type' => $post->post_mime_type, 
			'alt_text'  => get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
			'sizes'     => $sizes,
		);
	}

}
