<?php

namespace WP_JSON_API;

if ( ! defined( 'MAX_POSTS_PER_PAGE' ) )
	define( 'MAX_POSTS_PER_PAGE', 10 );

require_once( __DIR__ . '/../API_Base.php' );

class APIv1 extends API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $app ) {
		parent::__construct( $app );
		$this->registerRoute( 'GET', 'posts(/:id)', array( $this, 'get_posts' ) );
		$this->registerRoute( 'GET', 'users(/:id)', array( $this, 'get_users' ) );
		$this->registerRoute( 'GET', 'taxonomies(/:name)', array( $this, 'get_taxonomies' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms(/:term_id)', array( $this, 'get_terms' ) );
		$this->registerRoute( 'GET', 'rewrite_rules', array( $this, 'get_rewrite_rules' ) );
	}

	public function get_posts( $id = null ) {

		$force_public_post_stati = function( $wp_query ) {
			$qv = &$wp_query->query_vars;

			$invalid_status = false;

			// verify post_status query var exists
			if ( !isset( $qv['post_status'] ) ) {
				$qv['post_status'] = '';
			}

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
				} );
			}
		};

		$found = 0;
		$include_found = false;
		$wp_posts = array();
		$posts = array();

		$request_args = $this->app->request()->get();
		if ( ! empty( $id ) ) {
			$request_args['p'] = (int)$id;
		}
		if ( ! empty( $request_args['include_found'] ) || ! empty( $request_args['paged'] ) ) {
			$include_found = true;
		}
		if ( ! $include_found ) {
			$request_args['no_found_rows'] = true;
		}
		$args = self::get_post_args( $request_args );

		add_action( 'parse_query', $force_public_post_stati );
		$wp_posts = new \WP_Query( $args );
		remove_action( 'parse_query', $force_public_post_stati );

		if ( $wp_posts->have_posts() ) {
			$found = (int)$wp_posts->found_posts;
			foreach ( $wp_posts->posts as $query_post ) {
				$posts[] = self::format_post( $query_post );
			}
		}

		return $include_found ? compact( 'found', 'posts' ) : compact( 'posts' );
	}

	/**
	 * @param array $request_args
	 * @return array
	 */
	public static function get_post_args( $request_args = array() ) {
		// Remove any args that are not allowed by the API
		$request_args_whitelist = array(
			'm',
			'year',
			'monthnum',
			'w',
			'day',
			'hour',
			'minute',
			'second',
			'before',
			'after',
			's',
			'exact',
			'sentence',
			'cat',
			'category_name',
			'tag',
			'taxonomy',
			'paged',
			'per_page',
			'offset',
			'orderby',
			'order',
			'author_name',
			'author',
			'post__in',
			'p',
			'name',
			'pagename',
			'attachment',
			'attachment_id',
			'subpost',
			'subpost_id',
			'post_type',
			'post_parent__in',
			'include_found',
			'no_found_rows',
		);
		foreach ( $request_args as $key => $val ) {
			if ( ! in_array( $key, $request_args_whitelist ) ) {
				unset( $request_args[$key] );
			}
		}

		// Create export array by merging defaults with request args
		$defaults = array(
			'orderby' => 'date',
			'order' => 'DESC',
			'posts_per_page' => MAX_POSTS_PER_PAGE,
		);
		$args = wp_parse_args( $request_args, $defaults );

		// Remove args merged in that we want to run some filters on
		$args_cleanlist = array(
			'after',
			'before',
			'taxonomy',
			'author',
			'cat',
			'orderby',
			'per_page',
		);
		foreach ( $args as $key => $val ) {
			if ( in_array( $key, $args_cleanlist ) ) {
				unset( $args[$key] );
			}
		}


		if ( isset( $request_args['p'] ) ) {
			$args['p'] = (int)$request_args['p'];
		}

		if ( isset( $request_args['taxonomy'] ) && is_array( $request_args['taxonomy'] ) ) {
			$args['tax_query'] = array(
				'relation' => 'OR',
			);

			foreach ( $request_args['taxonomy'] as $key => $value ) {
				$args['tax_query'][] = array(
					'taxonomy' => $key,
					'terms' => is_array( $value ) ? $value : array(),
					'field' => 'term_id',
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
			// allowing any author exculsions to be accepted.
			$r = array_filter( (array)$request_args['author'], function( $author ) {
				return $author > 0;
			} );
			$args['author'] = implode( ',', $r );
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
			$r = array_filter( (array)$request_args['orderby'], function( $orderby ) use ( $valid_orders ) {
				return in_array( $orderby, $valid_orders );
			} );
			$args['orderby'] = implode( ' ', $r );
		}

		if ( ! empty( $request_args['per_page'] ) && $request_args['per_page'] >= 1 ) {
			$args['posts_per_page'] = min( (int)$request_args['per_page'], $args['posts_per_page'] );
		}

		return $args;
	}

	public function get_users( $id = null ) {
		$users = array();
		if ( $id ) {
			$users[] = self::format_user( get_user_by( 'id', $id ) );
			return compact( 'users' );
		}

		return WP_API_BASE . '/users/' . $id;
	}

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

	public function get_terms( $name, $term_id = null ) {
		return WP_API_BASE . '/taxonomies/' . $name . '/terms/' . $term_id;
	}

	/**
	 * @param $taxonomy
	 * @return Array
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

	public function format_term() {

	}

	/**
	 * Format post data
	 * @param \WP_Post $post
	 * @return Array Formatted post data
	 */
	public static function format_post( \WP_Post $post ) {
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

		$data = array(
			'id' => $user->ID,
			'id_str' => (string)$user->ID,
			'nicename' => $user->data->user_nicename,
			'display_name' => $user->data->display_name,
			'user_url' => $user->data->user_url,

			'posts_url' => 'http=>//example.com/author/john-doe/',
			'avatar' => array(
				array(
					'url' => 'http=>//1.gravatar.com/avatar/7a10459e7210f3bbaf2a75351255d9a3?s=64',
					'width' => 64,
					'height' => 64,
				),
			),
			'meta' => array()
		);

		return $data;
	}

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
