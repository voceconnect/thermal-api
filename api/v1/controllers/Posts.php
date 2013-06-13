<?php

namespace Voce\Thermal\v1;

require_once(__DIR__ . '/../models/Posts.php');

class PostsController {

	private static $_model;

	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new PostsModel();
		}
		return self::$_model;
	}

	public static function find( $app ) {
		$found = 0;
		$posts = array( );
		$request_args = $app->request()->get();

		$args = self::convert_request( $request_args );

		$model = self::model();

		$posts = $model->find( $args, $found );

		array_walk( $posts, array( __CLASS__, 'format' ), 'read' );

		return empty( $args['no_found_rows'] ) ? compact( 'posts', 'found' ) : compact( 'posts' );
	}

	public static function findById( $app, $id ) {
		$post = self::model()->findById( $id );
		if ( !$post ) {
			$app->halt( '404', get_status_header_desc( '404' ) );
		}
		$post_type_obj = get_post_type_object( get_post_type( $post ) );
		$post_status_obj = get_post_status_object( get_post_status( $post ) );

		if ( is_user_logged_in() ) {
			if ( !current_user_can( $post_type_obj->cap->read, $post->ID ) ) {
				$app->halt( '403', get_status_header_desc( '403' ) );
			}
		} elseif ( !($post_type_obj->public && $post_status_obj->public) ) {
			$app->halt( '401', get_status_header_desc( '401' ) );
		}

		self::format( $post, 'read' );
		return $post;
	}

	/**
	 * Filter and validate the parameters that will be passed to the model.
	 * @param array $request_args
	 * @return array
	 */
	protected static function convert_request( $request_args ) {
		// Remove any args that are not allowed by the API
		$request_filters = array(
			'm' => array( ),
			'year' => array( ),
			'monthnum' => array( ),
			'w' => array( ),
			'day' => array( ),
			'hour' => array( ),
			'minute' => array( ),
			'second' => array( ),
			'before' => array( ),
			'after' => array( ),
			's' => array( ),
			'exact' => array( __NAMESPACE__ . '\\toBool' ),
			'sentence' => array( __NAMESPACE__ . '\\toBool' ),
			'cat' => array( __NAMESPACE__ . '\\toArray', __NAMESPACE__ . '\\applyInt', __NAMESPACE__ . '\\toCommaSeparated' ),
			'category_name' => array( ),
			'tag' => array( ),
			'taxonomy' => array( __NAMESPACE__ . '\\toArray' ),
			'paged' => array( ),
			'per_page' => array( '\\intval' ),
			'offset' => array( '\\intval' ),
			'orderby' => array( ),
			'order' => array( ),
			'author_name' => array( ),
			'author' => array( ),
			'post__in' => array( __NAMESPACE__ . '\\toArray', __NAMESPACE__ . '\\applyInt', __NAMESPACE__ . '\\toCommaSeparated' ),
			'p' => array( ),
			'name' => array( ),
			'pagename' => array( ),
			'attachment' => array( ),
			'attachment_id' => array( ),
			'subpost' => array( ),
			'subpost_id' => array( ),
			'post_type' => array( __NAMESPACE__ . '\\toArray' ),
			'post_parent__in' => array( __NAMESPACE__ . '\\toArray', __NAMESPACE__ . '\\applyInt' ),
			'include_found' => array( __NAMESPACE__ . '\\toBool' ),
		);
		//strip any nonsafe args
		$request_args = array_intersect_key( $request_args, $request_filters );

		//run through basic sanitation
		foreach ( $request_args as $key => $value ) {
			foreach ( $request_filters[$key] as $callback ) {
				$value = call_user_func( $callback, $value );
			}
			$request_args[$key] = $value;
		}

		//taxonomy
		if ( isset( $request_args['taxonomy'] ) && is_array( $request_args['taxonomy'] ) ) {
			$tax_query = array( );
			$public_taxonomies = get_taxonomies( array( 'public' => true ) );
			foreach ( $request_args['taxonomy'] as $key => $value ) {
				if ( in_array( $key, $public_taxonomies ) ) {
					$tax_query[] = array(
						'taxonomy' => $key,
						'terms' => is_array( $value ) ? $value : array( ),
						'field' => 'term_id',
					);
				}
			}
			unset( $request_args['taxonomy'] );
			if ( count( $tax_query ) ) {
				$request_args['tax_query'] = $tax_query;
			}
		}

		//post_type filtering
		if ( isset( $request_args['post_type'] ) ) {
			//filter to only ones with read capability
			$post_types = array( );
			foreach ( $request_args['post_type'] as $post_type ) {
				if ( $post_type_obj = get_post_type_object( $post_type ) ) {
					if ( $post_type_obj->public || current_user_can( $post_type_obj->cap->read ) ) {
						$post_types[] = $post_type;
					}
				}
			}
		} else {
			if ( empty( $request_args['s'] ) ) {
				$request_args['post_type'] = get_post_types( array( 'publicly_queryable' => true ) );
			} else {
				$request_args['post_type'] = get_post_types( array( 'exclude_from_search' => false ) );
			}
		}



		if ( isset( $request_args['author'] ) ) {
			// WordPress only allows a single author to be excluded. We are not
			// allowing any author exclusions to be accepted.
			$request_args['author'] = array_filter( ( array ) $request_args['author'], function( $author ) {
					return $author > 0;
				} );
			$request_args['author'] = implode( ',', $request_args['author'] );
		}

		if ( isset( $request_args['orderby'] ) && is_array( $request_args['orderby'] ) ) {
			$request_args['orderby'] = implode( ' ', $request_args['orderby'] );
		}

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] > MAX_POSTS_PER_PAGE ) {
			$request_args['per_page'] = MAX_POSTS_PER_PAGE;
		}

		if ( empty( $request_args['paged'] ) && empty( $request_args['include_found'] ) ) {
			$request_args['no_found_rows'] = true;
		}

		return $request_args;
	}

	/**
	 * 
	 * @param \WP_Post $post
	 * @param string $state  State of CRUD to render for, options 
	 * 	include 'read', new', 'edit'
	 */
	public static function format( &$post, $state = 'read' ) {
		if ( !$post ) {
			return $post = null;
		}

		//allow for use with array_walk
		if ( func_num_args() > 2 ) {
			$state = func_get_arg( func_num_args() - 1 );
		}
		if ( !in_array( $state, array( 'read', 'new', 'edit' ) ) ) {
			$state = 'read';
		}

		//edit provides a slimmed down response containing only editable fields
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		$data = array(
			'type' => $post->post_type,
			'parent' => $post->post_parent,
			'parent_str' => ( string ) $post->post_parent,
			'date' => ( string ) get_post_time( 'c', true, $post ),
			'status' => $post->post_status,
			'comment_status' => $post->comment_status,
			'menu_order' => $post->menu_order,
			'title' => $post->post_title,
			'name' => $post->post_name,
			'excerpt' => $post->post_excerpt,
			'content' => $post->post_content,
			'author' => $post->post_author,
		);

		//add extended data for 'read'
		if ( $state = 'read' ) {
			$media = array( );
			$meta = array( );

			// get direct post attachments
			$attachments = get_posts( array(
				'post_parent' => $post->ID,
				'post_mime_type' => 'image',
				'post_type' => 'attachment',
				) );

			foreach ( $attachments as $attachment ) {
				$media[$attachment->ID] = self::_format_image_media_item( $attachment );
			}

			// get gallery meta
			$gallery_meta = self::_get_gallery_meta( $post, $media );
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
					array_walk( $terms, array( __NAMESPACE__ . '\TermsController', 'format' ), $state );
					$post_taxonomies[$taxonomy] = $terms;
				}
			}

			remove_filter( 'the_content', 'do_shortcode', 11 );
			remove_filter( 'the_content', 'convert_smilies' );
			remove_filter( 'the_content', 'shortcode_unautop' );

			// remove "<!--more-->" teaser text for display content
			$post_more = get_extended( $post->post_content );
			$content_display = $post_more['extended'] ? $post_more['extended'] : $post_more['main'];

			$userModel = UsersController::model();
			$author = $userModel->findById( $post->post_author );

			UsersController::format( $author, 'read' );

			$data = array_merge( $data, array(
				'id' => $post->ID,
				'id_str' => ( string ) $post->ID,
				'permalink' => get_permalink( $post ),
				'modified' => get_post_modified_time( 'c', true, $post ),
				'comment_status' => $post->comment_status,
				'comment_count' => ( int ) $post->comment_count,
				'excerpt_display' => apply_filters( 'the_excerpt', get_the_excerpt() ),
				'content_display' => apply_filters( 'the_content', $content_display ),
				'mime_type' => $post->post_mime_type,
				'meta' => ( object ) $meta,
				'taxonomies' => ( object ) $post_taxonomies,
				'media' => array_values($media),
				'author' => $author
				) );
		}

		wp_reset_postdata();

		$post = ( object ) $data;
	}

	protected static function _get_post_galleries( \WP_Post $post ) {
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
			$gallery_data[] = self::_parse_gallery_attrs( $attrs );
		}

		return $gallery_data;
	}

	protected static function _parse_gallery_attrs( $gallery_attrs ) {

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
	 * @param $post
	 * @param $media
	 * @return array
	 */
	protected static function _get_gallery_meta( $post, $media ) {
		$gallery_meta = array( );

		// check post content for gallery shortcode
		if ( $gallery_data = self::_get_post_galleries( $post ) ) {

			foreach ( $gallery_data as $gallery ) {

				$gallery_id = empty( $gallery['id'] ) ? $post->ID : intval( $gallery['id'] );
				$order = strtoupper( $gallery['order'] );
				$orderby = implode( ' ', $gallery['orderby'] );
				$include = empty( $gallery['include'] ) ? array( ) : $gallery['include'];
				$exclude = empty( $gallery['exclude'] ) ? array( ) : $gallery['exclude'];

				if ( !empty( $order ) && ( 'RAND' == $order ) ) {
					$orderby = 'none';
				}

				$attachments = self::_get_gallery_attachments( $gallery_id, $order, $orderby, $include, $exclude );

				$ids = array( );
				foreach ( $attachments as $attachment ) {
					$media[$attachment->ID] = self::_format_image_media_item( $attachment );
					$ids[] = $attachment->ID;
				}

				$gallery_meta[] = array(
					'ids' => $ids,
					'orderby' => $gallery['orderby'],
					'order' => $order,
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
	protected static function _get_gallery_attachments( $gallery_id, $order, $orderby, $include, $exclude ) {

		$args = array(
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $order,
			'orderby' => $orderby,
		);

		$attachments = array( );

		if ( !empty( $include ) ) {

			$args['include'] = $include;
			$_attachments = get_posts( $args );

			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} else if ( !empty( $exclude ) ) {

			$args = array_merge( $args, array(
				'post_parent' => $gallery_id,
				'exclude' => $exclude,
				) );
			$attachments = get_children( $args );
		} else {

			$args['post_parent'] = $gallery_id;
			$attachments = get_children( $args );
		}

		return $attachments;
	}

	/**
	 * Format the output of a media item.
	 * @param \WP_Post $post
	 * @return array
	 */
	protected static function _format_image_media_item( \WP_Post $post ) {
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

}

?>
