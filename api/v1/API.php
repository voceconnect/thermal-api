<?php

namespace Voce\Thermal;

if ( ! defined( 'MAX_POSTS_PER_PAGE' ) ) {
	define( 'MAX_POSTS_PER_PAGE', 10 );
}

if ( ! defined( 'MAX_TERMS_PER_PAGE' ) ) {
	define( 'MAX_TERMS_PER_PAGE', 10 );
}

if ( ! defined( 'MAX_USERS_PER_PAGE' ) ) {
	define( 'MAX_USERS_PER_PAGE', 10 );
}

require_once( __DIR__ . '/../API_Base.php' );

/**
 *
 */
class APIv1 extends API_Base {

	protected $version = '1';

	/**
	 * Register the allowed routes.
	 * @param \Slim\Slim $app
	 */
	public function __construct( \Slim\Slim $app ) {
		parent::__construct( $app );
		$this->registerRoute( 'GET', 'posts/?(:id)/?', array( $this, 'get_posts' ) );
		$this->registerRoute( 'GET', 'users/?(:id)/?', array( $this, 'get_users' ) );
		$this->registerRoute( 'GET', 'taxonomies/?(:name)/?', array( $this, 'get_taxonomies' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms/?(:term_id)/?', array( $this, 'get_terms' ) );
		$this->registerRoute( 'GET', 'rewrite_rules/?', array( $this, 'get_rewrite_rules' ) );
	}

	/**
	 * posts/:id endpoint.
	 * @param int $id [optional]
	 * @return array
	 */
	public function get_posts( $id = null ) {

		$found         = 0;
		$posts         = array();
		$request_args  = $this->app->request()->get();

		$args = self::get_post_args( $request_args, $id );

		add_action( 'parse_query', array( __CLASS__, '_force_public_post_status' ) );
		$wp_posts = new \WP_Query( $args );
		remove_action( 'parse_query', array( __CLASS__, '_force_public_post_status' ) );

		if ( $wp_posts->have_posts() ) {
			$found = (int)$wp_posts->found_posts;
			foreach ( $wp_posts->posts as $query_post ) {
				$posts[] = self::format_post( $query_post );
			}
		}

		return $args['no_found_rows'] ? compact( 'posts' ) :  compact( 'found', 'posts' );

	}

	/**
	 * 'post_requests' action, force invalid post status to return empty request
	 * @return string
	 */
	public static function _force_blank_request() {
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
		if ( ! isset( $qv['post_status'] ) )
			$qv['post_status'] = array();

		// if a non-public post_status was requested, no posts should be returned
		// remove the stati for now, and set a flag so we can filter the results
		if ( ! empty( $qv['post_status'] ) ) {

			$non_public_stati = array_values( get_post_stati( array( 'public' => false ) ) );

			$qv['post_status'] = (array)$qv['post_status'];

			$before_count = count( $qv['post_status'] );
			$qv['post_status'] = array_diff( $qv['post_status'], $non_public_stati );

			$invalid_status = ( $before_count !== count( $qv['post_status'] ) );
		}

		// ensure post_status contains valid registered stati
		$post_stati = get_post_stati();
		$qv['post_status'] = array_intersect( $post_stati, (array)$qv['post_status'] );

		// if a user tried to specify a non-public post_status, and no valid post_status values
		// remain after filtering, force an empty result set
		if ( empty( $qv['post_status'] ) && $invalid_status ) {
			add_filter( 'posts_request', array( __CLASS__, '_force_blank_request' ) );
		}
	}

	/**
	 * Filter and validate the parameters that will be passed to get_posts.
	 * @param array $request_args
	 * @param int $id [optional]
	 * @return array
	 */
	public static function get_post_args( $request_args, $id = null ) {
		// Remove any args that are not allowed by the API
		$request_args_whitelist = array(
			'm'               => '',
			'year'            => '',
			'monthnum'        => '',
			'w'               => '',
			'day'             => '',
			'hour'            => '',
			'minute'          => '',
			'second'          => '',
			'before'          => '',
			'after'           => '',
			's'               => '',
			'exact'           => '',
			'sentence'        => '',
			'cat'             => '',
			'category_name'   => '',
			'tag'             => '',
			'taxonomy'        => '',
			'paged'           => '',
			'per_page'        => '',
			'offset'          => '',
			'orderby'         => '',
			'order'           => '',
			'author_name'     => '',
			'author'          => '',
			'post__in'        => '',
			'p'               => '',
			'name'            => '',
			'pagename'        => '',
			'attachment'      => '',
			'attachment_id'   => '',
			'subpost'         => '',
			'subpost_id'      => '',
			'post_type'       => '',
			'post_parent__in' => '',
			'include_found'   => '',
			'no_found_rows'   => '',
		);
		$request_args = array_intersect_key( $request_args, $request_args_whitelist );

		// Create export array by merging defaults with request args
		$defaults = array(
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => MAX_POSTS_PER_PAGE,
			'no_found_rows'  => true,
		);
		$args = wp_parse_args( $request_args, $defaults );

		// Strip non-WP_Query args that got populated by defaults parsing
		$api_non_wp_args = array(
			'after'    => '',
			'before'   => '',
			'taxonomy' => '',
			'author'   => '',
			'cat'      => '',
			'orderby'  => '',
			'per_page' => '',
		);
		$args = array_diff_key( $args, $api_non_wp_args );

		if ( ! is_null( $id ) ) {

			$args['p'] = (int)$id;

		} else if ( isset( $request_args['p'] ) ) {

			$args['p'] = (int)$request_args['p'];

		}

		if ( isset( $request_args['taxonomy'] ) && is_array( $request_args['taxonomy'] ) ) {
			$args['tax_query'] = array(
				'relation' => 'OR',
			);

			foreach ( $request_args['taxonomy'] as $key => $value ) {
				$args['tax_query'][] = array(
					'taxonomy' => $key,
					'terms'    => is_array( $value ) ? $value : array(),
					'field'    => 'term_id',
				);
			}
		}

		if ( isset( $request_args['after'] ) ) {
			$date = date('Y-m-d', strtotime( $request_args['after'] ) );
			add_filter( 'posts_where', function( $where ) use ( $date ) {
				$where .= " AND post_date > '$date'";
				return $where;
			} );
		}

		if ( isset( $request_args['before'] ) ) {
			$date = date('Y-m-d', strtotime( $request_args['before'] ) );
			add_filter( 'posts_where', function( $where ) use ( $date ) {
				$where .= " AND post_date < '$date'";
				return $where;
			} );
		}

		if ( isset( $request_args['author'] ) ) {
			// WordPress only allows a single author to be excluded. We are not
			// allowing any author exclusions to be accepted.
			$args['author'] = array_filter( (array)$request_args['author'], function( $author ) {
				return $author > 0;
			} );
			$args['author'] = implode( ',', $args['author'] );
		}

		if ( isset( $request_args['cat'] ) ) {
			$args['cat'] = implode( ',', (array)$request_args['cat'] );
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

		if ( isset( $request_args['orderby'] ) ) {
			$args['orderby'] = array_intersect( $valid_orders, (array)$request_args['orderby'] );
			$args['orderby'] = implode( ' ', $args['orderby'] );
		}

		if ( ! empty( $request_args['per_page'] ) && $request_args['per_page'] >= 1 ) {
			$args['posts_per_page'] = min( (int)$request_args['per_page'], $args['posts_per_page'] );
		}

		if ( ! empty( $request_args['paged'] ) ) {

			$args['no_found_rows'] = false;

		} else if ( isset( $request_args['include_found'] ) ) {

			if ( ( 'true' === $request_args['include_found'] ) || ( 1 == $request_args['include_found'] ) ) {

				$args['no_found_rows'] = false;

			}

		}

		return $args;
	}

	/**
	 * users/:id route
	 * @param int $id [optional]
	 * @return array
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
	 * Filter and validate the parameters that will be passed to get_users.
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

	/**
	 * taxonomies/:id endpoint.
	 * @param string $name [optional]
	 * @return array
	 */
	public function get_taxonomies( $name = null ) {
		$args = array(
			'public' => true,
		);

		if ( ! is_null( $name ) ) {
			$args['name'] = $name;
		}

		$t = get_taxonomies( $args, 'object' );
		$args = $this->app->request()->get();
		$taxonomies = array();
		foreach ( $t as $taxonomy ) {
			if ( isset( $args['in'] ) ) {
				if ( ! in_array( $taxonomy->name, (array)$args['in'] ) ) {
					continue;
				}
			}

			if ( isset( $args['post_type'] ) ) {
				if ( 0 === count( array_intersect( $taxonomy->object_type, (array)$args['post_type'] ) ) ) {
					continue;
				}
			}

			$taxonomies[] = $this->format_taxonomy( $taxonomy );
		}

		return compact( 'taxonomies' );
	}

	/**
	 * taxonomies/:taxonomy/terms/:term endpoint.
	 * @param string $name
	 * @param int $term_id [optional]
	 * @return array
	 */
	public function get_terms( $name, $term_id = null ) {
		$found = 0;

		$request       = $this->app->request();
		$request_args  = $request->get();
		$args          = self::get_terms_args( $request_args, $term_id );

		$include_found = filter_var( $request->get('include_found'), FILTER_VALIDATE_BOOLEAN );
		$include_found = ( $include_found || $request->get('paged') );

		$terms = array_map( array( __CLASS__, 'format_term' ), get_terms( $name, $args ) );

		if ( $include_found && count( $terms ) ) {
			$found = (int)get_terms( $name, array_merge( $args, array( 'fields' => 'count' ) ) );
		}

		return $include_found ? compact( 'found', 'terms' ) : compact( 'terms' );
	}

	/**
	 * Filter and validate the parameters that will be passed to get_terms.
	 * @param array $request_args
	 * @param int $term_id [optional]
	 * @return array
	 */
	public static function get_terms_args( $request_args, $term_id = null ) {
		$args = array();

		$args['number'] = MAX_TERMS_PER_PAGE;

		foreach ( array( 'parent', 'offset' ) as $int_var ) {
			if ( isset( $request_args[$int_var] ) &&
				is_int( $value = filter_var( $request_args[$int_var], FILTER_VALIDATE_INT ) ) ) {
				$args[$int_var] = max( 0, $value );
			}
		}

		foreach ( array( 'hide_empty', 'pad_counts' ) as $bool_var ) {
			if ( isset( $request_args[$bool_var] ) ) {
				$args[$bool_var] = filter_var( $request_args[$bool_var], FILTER_VALIDATE_BOOLEAN );
			}
		}

		if ( ! empty( $request_args['per_page'] ) && $request_args['per_page'] >= 1 ) {
			$args['number'] = min( (int)$request_args['per_page'], $args['number'] );
		}

		if ( ! empty( $request_args['paged'] ) && $request_args['paged'] >= 1 ) {
			$args['offset'] = ( (int)$request_args['paged'] - 1 ) * $args['number'];
		}

		$valid_orderby = array( 'name', 'slug', 'count' );
		if ( ! empty( $request_args['orderby'] ) && in_array( strtolower( $request_args['orderby'] ), $valid_orderby ) ) {
			$args['orderby'] = strtolower( $request_args['orderby'] );
		}

		$valid_order = array( 'asc', 'desc' );
		if ( ! empty( $request_args['order'] ) && in_array( strtolower( $request_args['order'] ), $valid_order ) ) {
			$args['order'] = strtolower( $request_args['order'] );
		}

		if ( ! is_null( $term_id ) ) {

			$args['include'] = array( (int)$term_id );

		} else if ( ! empty( $request_args['include'] ) ) {

			$args['include'] = array_values( array_filter( array_map( 'intval', (array)$request_args['include'] ) ) );

		}

		if ( ! empty( $request_args['slug'] ) ) {
			$args['slug'] = $request_args['slug'];
		}

		return $args;
	}

	/**
	 * Format the output of a taxonomy.
	 * @param $taxonomy
	 * @return array
	 */
	public function format_taxonomy( $taxonomy ) {
		return array(
			'name'         => $taxonomy->name,
			'post_types'   => $taxonomy->object_type,
			'hierarchical' => $taxonomy->hierarchical,
			'query_var'    => $taxonomy->query_var,
			'labels'       => array(
				'name'          => $taxonomy->labels->name,
				'singular_name' => $taxonomy->labels->singular_name,
			),
			'meta'         => (object)array(),
		);
	}

	/**
	 * @param $post
	 * @param $media
	 * @return array
	 */
	public static function get_gallery_meta( $post, $media ) {
		$gallery_meta = array();

		// check post content for gallery shortcode
		if ( $gallery_data = self::get_post_galleries( $post ) ) {

			foreach ( $gallery_data as $gallery ) {

				$gallery_id = empty( $gallery['id'] ) ? $post->ID : intval( $gallery['id'] );
				$order      = strtoupper( $gallery['order'] );
				$orderby    = implode( ' ', $gallery['orderby'] );
				$include    = empty( $gallery['include'] ) ? array() : $gallery['include'];
				$exclude    = empty( $gallery['exclude'] ) ? array() : $gallery['exclude'];

				if ( ! empty( $order ) && ( 'RAND' == $order ) ) {
					$orderby = 'none';
				}

				$attachments = self::get_gallery_attachments( $gallery_id, $order, $orderby, $include, $exclude );

				$ids = array();
				foreach ( $attachments as $attachment ) {
					$media[$attachment->ID] = self::format_image_media_item( $attachment );
					$ids[] = $attachment->ID;
				}

				$gallery_meta[] = array(
					'ids'     => $ids,
					'orderby' => $gallery['orderby'],
					'order'   => $order,
				);
			}
		}

		return array( 'gallery_meta' => $gallery_meta, 'media' => $media );
	}

	/**
	 * @param $gallery_id
	 * @param $order
	 * @param $orderby
	 * @param $include
	 * @param $exclude
	 * @return array|bool
	 */
	public static function get_gallery_attachments( $gallery_id, $order, $orderby, $include, $exclude ) {

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'order'          => $order,
			'orderby'        => $orderby,
		);

		$attachments = array();

		if ( ! empty( $include ) ) {

			$args['include'] = $include;
			$_attachments    = get_posts( $args );

			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}

		} else if ( ! empty( $exclude ) ) {

			$args = array_merge( $args, array(
				'post_parent' => $gallery_id,
				'exclude'     => $exclude,
			) );
			$attachments = get_children( $args );

		} else {

			$args['post_parent'] = $gallery_id;
			$attachments         = get_children( $args );

		}

		return $attachments;

	}

	/**
	 * Format post data
	 * @param \WP_Post $post
	 * @return Array Formatted post data
	 */
	public function format_post( \WP_Post $post ) {
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		$media = array();
		$meta = array();

		// get direct post attachments
		$attachments = get_posts( array(
			'post_parent'    => $post->ID,
			'post_mime_type' => 'image',
			'post_type'      => 'attachment',
		) );
		foreach ( $attachments as $attachment ) {
			$media[$attachment->ID] = self::format_image_media_item( $attachment );
		}

		// get gallery meta
		$gallery_meta = self::get_gallery_meta( $post, $media );
		if ( !empty( $gallery_meta['gallery_meta'] ) ) {
			$meta['gallery'] = $gallery_meta['gallery_meta'];
			$media = $gallery_meta['media'];
		}

		if ( $thumbnail_id = get_post_thumbnail_id( $post->ID ) ) {
			$meta['featured_image'] = (int)$thumbnail_id;
		}


		// get taxonomy data
		$post_taxonomies = array();
		$taxonomies      = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			// get the terms related to post
			$terms = get_the_terms( $post->ID, $taxonomy );
			if ( !empty( $terms ) ) {
				$post_taxonomies[$taxonomy] = array_values( array_map( array( __CLASS__, 'format_term' ), $terms ) );
			}
		}

		remove_filter( 'the_content', 'do_shortcode', 11 );
		remove_filter( 'the_content', 'convert_smilies' );
		remove_filter( 'the_content', 'shortcode_unautop' );

		// remove "<!--more-->" teaser text for display content
		$post_more       = get_extended( $post->post_content );
		$content_display = $post_more['extended'] ? $post_more['extended'] : $post_more['main'];

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
			'excerpt'          => $post->post_excerpt,
			'excerpt_display'  => apply_filters( 'the_excerpt', get_the_excerpt() ),
			'content'          => $post->post_content,
			'content_display'  => apply_filters( 'the_content', $content_display ),
			'mime_type'        => $post->post_mime_type,
			'meta'             => (object)$meta,
			'taxonomies'       => (object)$post_taxonomies,
			'media'            => $media,
			'author'           => self::format_user( get_user_by( 'id', $post->post_author ) ),
		);

		wp_reset_postdata();

		return $data;
	}

	public static function get_post_galleries( \WP_Post $post ) {
		global $shortcode_tags;

		if ( !isset( $shortcode_tags['gallery'] ) )
			return array();

		// setting shortcode tags to 'gallery' only
		$backup_shortcode_tags = $shortcode_tags;
		$shortcode_tags        = array( 'gallery' => $shortcode_tags['gallery'] );
		$pattern               = get_shortcode_regex();
		$shortcode_tags        = $backup_shortcode_tags;

		$matches = array();
		preg_match_all( "/$pattern/s", $post->post_content, $matches );

		$gallery_data = array();
		foreach ( $matches[3] as $gallery_args ) {
			$attrs          = shortcode_parse_atts( $gallery_args );
			$gallery_data[] = self::parse_gallery_attrs( $attrs );
		}

		return $gallery_data;
	}

	public static function parse_gallery_attrs( $gallery_attrs ) {

		$clean_val = function( $val ) {
			$trimmed = trim( $val );
			return ( is_numeric( $trimmed ) ? (int)$trimmed : $trimmed );
		};

		$params = array(
			'id',
			'ids',
			'orderby',
			'order',
			'include',
			'exclude',
		);
		$array_params = array(
			'ids',
			'orderby',
			'include',
			'exclude',
		);

		if ( empty( $gallery_attrs['order'] ) ) {
			$gallery_attrs['order'] = 'ASC';
		}
		if ( ! empty( $gallery_attrs['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $gallery_attrs['orderby'] ) ) {
				$gallery_attrs['orderby'] = 'post__in';
			}
			$gallery_attrs['include'] = $gallery_attrs['ids'];
		}
		if ( empty( $gallery_attrs['orderby'] ) ) {
			$gallery_attrs['orderby'] = 'menu_order, ID';
		}

		$gallery = array();
		foreach ( $params as $param ) {
			if ( !empty( $gallery_attrs[$param] ) ) {
				if ( in_array( $param, $array_params ) ) {
					$gallery_param_array = explode( ',', $gallery_attrs[$param] );
					$gallery_param_array = array_map( $clean_val, $gallery_param_array );
					$gallery[$param] = $gallery_param_array;
				}
				else {
					$gallery[$param] = $clean_val( $gallery_attrs[$param] );
				}
			}
		}

		return $gallery;
	}

	/**
	 * Format the output of a user.
	 * @param \WP_User $user
	 * @return array Formatted user data
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
	 * @return array
	 */
	public function get_rewrite_rules() {
		$base_url = home_url( '/' );
		$rewrite_rules = array();

		$rules = get_option( 'rewrite_rules', array() );
		foreach ( $rules as $regex => $query ) {
			$patterns = array( '|index\.php\?&?|', '|\$matches\[(\d+)\]|' );
			$replacements = array( '', '\$$1');

			$rewrite_rules[] = array(
				'regex' => $regex,
				'query_expression' => preg_replace( $patterns, $replacements, $query ),
			);
		}

		return compact( 'base_url', 'rewrite_rules' );
	}

	/**
	 * Format the output of a media item.
	 * @param \WP_Post $post
	 * @return array
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

	/**
	 * Format the output of a term.
	 * @param $term
	 * @return array
	 */
	public static function format_term( $term ) {
		return array(
			'id'                   => (int)$term->term_id,
			'id_str'               => $term->term_id,
			'term_taxonomy_id'     => (int)$term->term_taxonomy_id,
			'term_taxonomy_id_str' => $term->term_taxonomy_id,
			'parent'               => (int)$term->parent,
			'parent_str'           => $term->parent,
			'name'                 => $term->name,
			'slug'                 => $term->slug,
			'taxonomy'             => $term->taxonomy,
			'description'          => $term->description,
			'post_count'           => (int)$term->count,
			'meta'                 => (object)array(),
		);
	}

}
