<?php

namespace Voce\Thermal\v1;

if ( !defined( 'MAX_POSTS_PER_PAGE' ) ) {
	define( 'MAX_POSTS_PER_PAGE', 100 );
}

if ( !defined( 'MAX_TERMS_PER_PAGE' ) ) {
	define( 'MAX_TERMS_PER_PAGE', 100 );
}

if ( !defined( 'MAX_USERS_PER_PAGE' ) ) {
	define( 'MAX_USERS_PER_PAGE', 100 );
}

require_once( __DIR__ . '/../API_Base.php' );
require_once( __DIR__ . '/controllers/Posts.php');
require_once( __DIR__ . '/controllers/Users.php');

/**
 *
 */
class API extends \Voce\Thermal\API_Base {

	protected $version = '1';

	/**
	 * Register the allowed routes.
	 * @param \Slim\Slim $app
	 */
	public function __construct( \Slim\Slim $app ) {
		parent::__construct( $app );
		$this->registerRoute( 'GET', 'posts/?', array( __NAMESPACE__ . '\\PostsController', 'find' ) );
		$this->registerRoute( 'GET', 'posts/(:id)/?', array( __NAMESPACE__ . '\\PostsController', 'findById' ) );
		$this->registerRoute( 'GET', 'users/?', array( __NAMESPACE__ . '\\UsersController', 'find' ) );
		$this->registerRoute( 'GET', 'users/(:id)/?', array( __NAMESPACE__ . '\\UsersController', 'findById' ) );
		$this->registerRoute( 'GET', 'taxonomies/?(:name)/?', array( $this, 'get_taxonomies' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms/?(:term_id)/?', array( $this, 'get_terms' ) );
		$this->registerRoute( 'GET', 'rewrite_rules/?', array( $this, 'get_rewrite_rules' ) );
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

		if ( !is_null( $name ) ) {
			$args['name'] = $name;
		}

		$t = get_taxonomies( $args, 'object' );
		$args = $this->app->request()->get();
		$taxonomies = array( );
		foreach ( $t as $taxonomy ) {
			if ( isset( $args['in'] ) ) {
				if ( !in_array( $taxonomy->name, ( array ) $args['in'] ) ) {
					continue;
				}
			}

			if ( isset( $args['post_type'] ) ) {
				if ( 0 === count( array_intersect( $taxonomy->object_type, ( array ) $args['post_type'] ) ) ) {
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

		$request = $this->app->request();
		$request_args = $request->get();
		$args = self::get_terms_args( $request_args, $term_id );

		$include_found = filter_var( $request->get( 'include_found' ), FILTER_VALIDATE_BOOLEAN );
		$include_found = ( $include_found || $request->get( 'paged' ) );

		$terms = array_map( array( __CLASS__, 'format_term' ), get_terms( $name, $args ) );

		if ( $include_found && count( $terms ) ) {
			$found = ( int ) get_terms( $name, array_merge( $args, array( 'fields' => 'count' ) ) );
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
		$args = array( );

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

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] >= 1 ) {
			$args['number'] = min( ( int ) $request_args['per_page'], $args['number'] );
		}

		if ( !empty( $request_args['paged'] ) && $request_args['paged'] >= 1 ) {
			$args['offset'] = ( ( int ) $request_args['paged'] - 1 ) * $args['number'];
		}

		$valid_orderby = array( 'name', 'slug', 'count' );
		if ( !empty( $request_args['orderby'] ) && in_array( strtolower( $request_args['orderby'] ), $valid_orderby ) ) {
			$args['orderby'] = strtolower( $request_args['orderby'] );
		}

		$valid_order = array( 'asc', 'desc' );
		if ( !empty( $request_args['order'] ) && in_array( strtolower( $request_args['order'] ), $valid_order ) ) {
			$args['order'] = strtolower( $request_args['order'] );
		}

		if ( !is_null( $term_id ) ) {

			$args['include'] = array( ( int ) $term_id );
		} else if ( !empty( $request_args['include'] ) ) {

			$args['include'] = array_values( array_filter( array_map( 'intval', ( array ) $request_args['include'] ) ) );
		}

		if ( !empty( $request_args['slug'] ) ) {
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
			'name' => $taxonomy->name,
			'post_types' => $taxonomy->object_type,
			'hierarchical' => $taxonomy->hierarchical,
			'query_var' => $taxonomy->query_var,
			'labels' => array(
				'name' => $taxonomy->labels->name,
				'singular_name' => $taxonomy->labels->singular_name,
			),
			'meta' => ( object ) array( ),
		);
	}

	/**
	 * Format post data
	 * @param \WP_Post $post
	 * @return Array Formatted post data
	 */
	public function format_post( \WP_Post $post ) {
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		$media = array( );
		$meta = array( );

		// get direct post attachments
		$attachments = get_posts( array(
			'post_parent' => $post->ID,
			'post_mime_type' => 'image',
			'post_type' => 'attachment',
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
			$meta['featured_image'] = ( int ) $thumbnail_id;
		}


		// get taxonomy data
		$post_taxonomies = array( );
		$taxonomies = get_object_taxonomies( $post->post_type );
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
		$post_more = get_extended( $post->post_content );
		$content_display = $post_more['extended'] ? $post_more['extended'] : $post_more['main'];

		$data = array(
			'id' => $post->ID,
			'id_str' => ( string ) $post->ID,
			'type' => $post->post_type,
			'permalink' => get_permalink( $post ),
			'parent' => $post->post_parent,
			'parent_str' => ( string ) $post->post_parent,
			'date' => get_post_time( 'c', true, $post ),
			'modified' => get_post_modified_time( 'c', true, $post ),
			'status' => $post->post_status,
			'comment_status' => $post->comment_status,
			'comment_count' => ( int ) $post->comment_count,
			'menu_order' => $post->menu_order,
			'title' => $post->post_title,
			'name' => $post->post_name,
			'excerpt' => $post->post_excerpt,
			'excerpt_display' => apply_filters( 'the_excerpt', get_the_excerpt() ),
			'content' => $post->post_content,
			'content_display' => apply_filters( 'the_content', $content_display ),
			'mime_type' => $post->post_mime_type,
			'meta' => ( object ) $meta,
			'taxonomies' => ( object ) $post_taxonomies,
			'media' => $media,
			'author' => self::format_user( get_user_by( 'id', $post->post_author ) ),
		);

		wp_reset_postdata();

		return $data;
	}

	public static function get_post_galleries( \WP_Post $post ) {
		global $shortcode_tags;

		if ( !isset( $shortcode_tags['gallery'] ) )
			return array( );

		// setting shortcode tags to 'gallery' only
		$backup_shortcode_tags = $shortcode_tags;
		$shortcode_tags = array( 'gallery' => $shortcode_tags['gallery'] );
		$pattern = get_shortcode_regex();
		$shortcode_tags = $backup_shortcode_tags;

		$matches = array( );
		preg_match_all( "/$pattern/s", $post->post_content, $matches );

		$gallery_data = array( );
		foreach ( $matches[3] as $gallery_args ) {
			$attrs = shortcode_parse_atts( $gallery_args );
			$gallery_data[] = self::parse_gallery_attrs( $attrs );
		}

		return $gallery_data;
	}

	public static function parse_gallery_attrs( $gallery_attrs ) {

		$clean_val = function( $val ) {
				$trimmed = trim( $val );
				return ( is_numeric( $trimmed ) ? ( int ) $trimmed : $trimmed );
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
		if ( !empty( $gallery_attrs['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $gallery_attrs['orderby'] ) ) {
				$gallery_attrs['orderby'] = 'post__in';
			}
			$gallery_attrs['include'] = $gallery_attrs['ids'];
		}
		if ( empty( $gallery_attrs['orderby'] ) ) {
			$gallery_attrs['orderby'] = 'menu_order, ID';
		}

		$gallery = array( );
		foreach ( $params as $param ) {
			if ( !empty( $gallery_attrs[$param] ) ) {
				if ( in_array( $param, $array_params ) ) {
					$gallery_param_array = explode( ',', $gallery_attrs[$param] );
					$gallery_param_array = array_map( $clean_val, $gallery_param_array );
					$gallery[$param] = $gallery_param_array;
				} else {
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
			'id' => $user->ID,
			'id_str' => ( string ) $user->ID,
			'nicename' => $user->data->user_nicename,
			'display_name' => $user->data->display_name,
			'user_url' => $user->data->user_url,
			'posts_url' => get_author_posts_url( $user->ID ),
			'avatar' => array(
				array(
					'url' => array_pop( $matches ),
					'width' => 96,
					'height' => 96,
				)
			),
			'meta' => ( object ) array( )
		);
	}

	/**
	 * @return array
	 */
	public function get_rewrite_rules() {
		$base_url = home_url( '/' );
		$rewrite_rules = array( );

		$rules = get_option( 'rewrite_rules', array( ) );
		foreach ( $rules as $regex => $query ) {
			$patterns = array( '|index\.php\?&?|', '|\$matches\[(\d+)\]|' );
			$replacements = array( '', '\$$1' );

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
					'name' => 'full',
					'url' => trailingslashit( $upload_dir['baseurl'] ) . $meta['file'],
					'width' => $meta['width'],
				),
			);

			$attachment_upload_date = dirname( $meta['file'] );

			foreach ( $meta['sizes'] as $size => $data ) {
				$sizes[] = array(
					'height' => $data['height'],
					'name' => $size,
					'url' => trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( $attachment_upload_date ) . $data['file'],
					'width' => $data['width'],
				);
			}
		}

		return array(
			'id' => $post->ID,
			'id_str' => ( string ) $post->ID,
			'mime_type' => $post->post_mime_type,
			'alt_text' => get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
			'sizes' => $sizes,
		);
	}

	/**
	 * Format the output of a term.
	 * @param $term
	 * @return array
	 */
	public static function format_term( $term ) {
		return array(
			'id' => ( int ) $term->term_id,
			'id_str' => $term->term_id,
			'term_taxonomy_id' => ( int ) $term->term_taxonomy_id,
			'term_taxonomy_id_str' => $term->term_taxonomy_id,
			'parent' => ( int ) $term->parent,
			'parent_str' => $term->parent,
			'name' => $term->name,
			'slug' => $term->slug,
			'taxonomy' => $term->taxonomy,
			'description' => $term->description,
			'post_count' => ( int ) $term->count,
			'meta' => ( object ) array( ),
		);
	}

}
