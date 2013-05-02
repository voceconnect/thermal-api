<?php

namespace WP_JSON_API;

if ( ! defined( 'MAX_POSTS_PER_PAGE' ) ) {
	define( 'MAX_POSTS_PER_PAGE', 10 );
}

if ( ! defined( 'MAX_TERMS_PER_PAGE' ) ) {
	define( 'MAX_TERMS_PER_PAGE', 10 );
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

		if ( ! empty( $args['paged'] ) ) {
			$args['include_found'] = true;
		}

		if ( $wp_query_posts->have_posts() ) {
			$found = (int)$wp_query_posts->found_posts;
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

		$get_posts = new \WP_Query( array_merge( $defaults, $args ) );
		remove_action('parse_query', $force_public_post_stati );

		return $get_posts;
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
		return WP_API_BASE . '/taxonomies/' . $name;
	}

	public function get_terms( $name, $term_id = null ) {
		$found = 0;
		$wp_terms = array();
		$terms = array();

		$request_args = $this->app->request()->get();
		$args = self::get_terms_args( $request_args );

		if ( ! empty( $term_id ) ) {
			$args['include'] = array( $term_id );
		}

		$wp_terms = get_terms( $name, $args );

		if ( count( $wp_terms ) ) {
			$found = (int)get_terms( $name, array_merge( $args, array( 'fields' => 'count' ) ) );
		}

		if ( ! empty( $request_args['paged'] ) ) {
			$request_args['include_found'] = true;
		}

		if ( $found ) {
			foreach ( $wp_terms as $term ) {
				$terms[] = $this->format_term( $term );
			}
		}

		return ! empty( $request_args['include_found'] ) ? compact( 'found', 'terms' ) : compact( 'terms' );
	}

	/**
	 * @param array $request_args
	 * @return array
	 */
	public static function get_terms_args( $request_args = array() ) {
		$args = array();

		$args['number'] = MAX_TERMS_PER_PAGE;
		if ( ! empty( $request_args['per_page'] ) && $request_args['per_page'] >= 1 ) {
			$args['number'] = min( (int)$request_args['per_page'], $args['number'] );
		}

		if ( ! empty( $request_args['offset'] ) && $request_args['offset'] >= 1 ) {
			$args['offset'] = (int)$request_args['offset'];
		}

		if ( ! empty( $request_args['paged'] ) && $request_args['paged'] >= 1 ) {
			$args['offset'] = ( (int)$request_args['paged'] - 1 ) * $args['number'];
		}


		$valid_orderby = array(
			'name',
			'slug',
			'count',
		);
		if ( ! empty( $request_args['orderby'] ) && in_array( strtolower( $request_args['orderby'] ), $valid_orderby ) ) {
			$args['orderby'] = strtolower( $request_args['orderby'] );
		}

		$valid_order = array(
			'asc',
			'desc',
		);
		if ( ! empty( $request_args['order'] ) && in_array( strtolower( $request_args['order'] ), $valid_order ) ) {
			$args['order'] = strtolower( $request_args['order'] );
		}


		if ( ! empty( $request_args['include'] ) ) {
			$include = array_merge(
				array_filter(
					array_map( function( $id ) {
						return (int)$id;
					}, (array)$request_args['include'] )
				)
			);
			if ( count( $include ) ) {
				$args['include'] = $include;
			}
		}

		if ( ! empty( $request_args['slug'] ) ) {
			$args['slug'] = $request_args['slug'];
		}

		if ( isset( $request_args['parent'] ) && is_numeric( $request_args['parent'] ) ) {
			$args['parent'] = (int)$request_args['parent'];
		}

		if ( isset( $request_args['exclude_empty'] ) ) {
			if ( ! $request_args['exclude_empty'] || 'false' === strtolower( $request_args['exclude_empty'] ) ) {
				$args['hide_empty'] = false;
			}
		}

		if ( ! empty( $request_args['pad_count'] ) && 'false' !== strtolower( $request_args['pad_count'] ) ) {
			$args['pad_count'] = true;
		}


		return $args;
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
	public function format_user( \WP_User $user ) {

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

	/**
	 * @param $term
	 * @return Array
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
			'post_count'           => $term->count,
			'meta'                 => array(),
		);
	}

}
