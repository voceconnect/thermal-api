<?php

namespace Voce\Thermal\v1\Controllers;

class Posts {

	private static $_model;

	/**
	 *
	 * @return \Voce\Thermal\v1\Models\Posts
	 */
	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new \Voce\Thermal\v1\Models\Posts();
		}
		return self::$_model;
	}

	public static function find( $app ) {
		$found = 0;
		$posts = array( );
		$request_args = $app->request()->get();

		$args = self::convert_request( $request_args );

		if ( $lastModified = apply_filters( 'thermal_get_lastpostmodified', get_lastpostmodified( 'gmt' ) ) ) {
			$app->lastModified( strtotime( $lastModified . ' GMT' ) );
		}

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

		if ( $lastModified = apply_filters( 'thermal_post_last_modified', $post->post_modified_gmt ) ) {
			$app->lastModified( strtotime( $lastModified . ' GMT' ) );
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
			'exact' => array( '\\Voce\\Thermal\\v1\\toBool' ),
			'sentence' => array( '\\Voce\\Thermal\\v1\\toBool' ),
			'cat' => array( '\\Voce\\Thermal\\v1\\toArray', '\\Voce\\Thermal\\v1\\applyInt', '\\Voce\\Thermal\\v1\\toCommaSeparated' ),
			'category_name' => array( ),
			'tag' => array( ),
			'taxonomy' => array( '\\Voce\\Thermal\\v1\\toArray' ),
			'paged' => array( ),
			'per_page' => array( '\\intval' ),
			'offset' => array( '\\intval' ),
			'orderby' => array( ),
			'order' => array( ),
			'author_name' => array( ),
			'author' => array( ),
			'post__in' => array( '\\Voce\\Thermal\\v1\\toArray', '\\Voce\\Thermal\\v1\\applyInt' ),
			'p' => array( ),
			'name' => array( ),
			'pagename' => array( ),
			'attachment' => array( ),
			'attachment_id' => array( ),
			'subpost' => array( ),
			'subpost_id' => array( ),
			'post_type' => array( '\\Voce\\Thermal\\v1\\toArray' ),
			'post_status' => array( '\\Voce\\Thermal\\v1\\toArray' ),
			'post_parent__in' => array( '\\Voce\\Thermal\\v1\\toArray', '\\Voce\\Thermal\\v1\\applyInt' ),
			'include_found' => array( '\\Voce\\Thermal\\v1\\toBool' ),
		);
		//strip any nonsafe args
		$request_args = array_intersect_key( $request_args, $request_filters );

		//run through basic sanitation
		foreach ( $request_args as $key => $value ) {
			if ( isset( $request_filters[$key] ) ) {
				foreach ( $request_filters[$key] as $callback ) {
					$value = call_user_func( $callback, $value );
				}
				$request_args[$key] = $value;
			}
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
			$request_args['post_type'] = $post_types;
		} else {
			if ( empty( $request_args['s'] ) ) {
				$request_args['post_type'] = get_post_types( array( 'public' => true ) );
			} else {
				$request_args['post_type'] = get_post_types( array( 'exclude_from_search' => false ) );
			}
		}

		if ( empty( $request_args['post_status'] ) ) {
			//default to publish status
			$request_args['post_status'] = 'publish';
		} else {
			$request_args['post_status'] = array_filter( $request_args['post_status'], function( $status ) use ( $request_args ) {
					if ( $status == 'inherit' ) {
						return true;
					}

					$status_obj = get_post_status_object( $status );
					if ( !$status_obj ) {
						return false;
					};

					if ( $status_obj->public ) {
						return true;
					}

					//below makes an assumption that a post status is one of public, protected, or private
					//because WP Query doesn't currently handle proper mapping of status to type, if a the
					//current user doesn't have the capability to view a for that status, the status gets kicked out

					if ( $status_obj->protected ) {
						foreach ( $request_args['post_type'] as $post_type ) {
							$post_type_obj = get_post_type_object( $post_type );
							if ( $post_type_obj ) {
								$edit_protected_cap = $post_type_obj->cap->edit_others_posts;
							} else {
								$edit_protected_cap = 'edit_others_' . $post_type;
							}
							if ( !current_user_can( $edit_protected_cap ) ) {
								return false;
							}
						}
					} else if ( $status_obj->private ) {
						$post_type_obj = get_post_type_object( $post_type );
						if ( $post_type_obj ) {
							$read_private_cap = $post_type_obj->cap->read_rivate_posts;
						} else {
							$read_private_cap = 'read_private_' . $post_type;
						}
						if ( !current_user_can( $read_private_cap ) ) {
							return false;
						}
					} else {
						return false;
					}
					return true;
				} );
			if ( empty( $request_args['post_status'] ) ) {
				unset( $request_args['post_status'] );
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

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] > \Voce\Thermal\v1\MAX_POSTS_PER_PAGE ) {
			$request_args['per_page'] = \Voce\Thermal\v1\MAX_POSTS_PER_PAGE;
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
		if ( $state == 'read' ) {
			$media = array( );
			$meta = array( );

			// get direct post attachments
			$media_image_ids = get_posts( array(
				'post_parent' => $post->ID,
				'post_mime_type' => 'image',
				'post_type' => 'attachment',
				'fields' => 'ids',
				'posts_per_page' => \Voce\Thermal\v1\MAX_POSTS_PER_PAGE
				) );
			//get media in content
			if ( preg_match_all( '|<img.*?class=[\'"](.*?)wp-image-([0-9]{1,6})(.*?)[\'"].*?>|i', $post->post_content, $matches ) ) {
				$media_image_ids = array_merge( $media_image_ids, $matches[2] );
			}

			//get media from gallery
			$gallery_meta = self::_get_gallery_meta( $post, $media_image_ids );
			if ( !empty( $gallery_meta ) ) {
				$meta['gallery'] = $gallery_meta;
			}

			if ( $thumbnail_id = get_post_thumbnail_id( $post->ID ) ) {
				$media_image_ids[] = $meta['featured_image'] = ( int ) $thumbnail_id;
			}

			$media_image_ids = apply_filters('thermal_media_image_ids', $media_image_ids, $post);

			$media_image_ids = array_unique( $media_image_ids );
			foreach ( $media_image_ids as $media_image_id ) {
				if ( $image_item = self::_format_image_media_item( $media_image_id ) ) {
					$media[$media_image_id] = $image_item;
				}
			}

			// get taxonomy data
			$post_taxonomies = array( );
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $taxonomies as $taxonomy ) {
				// get the terms related to post
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( !empty( $terms ) ) {
					array_walk( $terms, array( __NAMESPACE__ . '\Terms', 'format' ), $state );
					$post_taxonomies[$taxonomy] = $terms;
				}
			}

			remove_filter( 'the_content', 'do_shortcode', 11 );
			remove_filter( 'the_content', 'convert_smilies' );
			remove_filter( 'the_content', 'shortcode_unautop' );

			// remove "<!--more-->" teaser text for display content
			$post_more = get_extended( $post->post_content );
			$content_display = $post_more['extended'] ? $post_more['extended'] : $post_more['main'];

			$userModel = Users::model();
			$author = $userModel->findById( $post->post_author );

			Users::format( $author, 'read' );

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
				'media' => array_values( $media ),
				'author' => $author
				) );
		}

		$data = apply_filters_ref_array( 'thermal_post_entity', array( ( object ) $data, &$post, $state ) );

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
	protected static function _get_gallery_meta( $post, &$attachment_ids = array( ) ) {
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

				$ids = self::_get_gallery_attachments( $gallery_id, $order, $orderby, $include, $exclude );

				$attachment_ids = array_unique( array_merge( $attachment_ids, $ids ) );

				$gallery_meta[] = array(
					'ids' => $ids,
					'orderby' => $gallery['orderby'],
					'order' => $order,
				);
			}
		}

		return $gallery_meta;
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
			'fields' => 'ids',
			'orderby' => $orderby,
		);

		$attachments = array( );

		if ( !empty( $include ) ) {

			$args['include'] = $include;
			$attachments = get_posts( $args );
		} else if ( !empty( $exclude ) ) {

			$args = array_merge( $args, array(
				'post_parent' => $gallery_id,
				'exclude' => $exclude,
				) );
			$attachments = get_posts( $args );
		} else {

			$args['post_parent'] = $gallery_id;
			$attachments = get_posts( $args );
		}

		return $attachments;
	}

	/**
	 * Format the output of a media item.
	 * @param \WP_Post $post
	 * @return array
	 */
	protected static function _format_image_media_item( $post ) {
		if ( !is_a( $post, "\WP_Post" ) ) {
			$post = get_post( $post );
			if ( !$post ) {
				return false;
			}
		}
		$meta = wp_get_attachment_metadata( $post->ID );
		$src = wp_get_attachment_image_src( $post->ID, 'full' );

		if ( isset( $meta['sizes'] ) and is_array( $meta['sizes'] ) ) {
			$upload_dir = wp_upload_dir();

			$sizes = array(
				array(
					'height' => $meta['height'],
					'name' => 'full',
					'url' => $src[0],
					'width' => $meta['width'],
				),
			);

			foreach ( $meta['sizes'] as $size => $data ) {
				$src = wp_get_attachment_image_src( $post->ID, $size );

				$sizes[] = array(
					'height' => $data['height'],
					'name' => $size,
					'url' => $src[0],
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
