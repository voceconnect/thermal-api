<?php

namespace Voce\Thermal\v1\Controllers;

class Comments {

	private static $_model;

	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new \Voce\Thermal\v1\Models\Comments();
		}
		return self::$_model;
	}

	protected static function _find( $app, $args ) {
		$args = self::convert_request( $args );

		$found = 0;
		$comments = array( );

		$model = self::model();

		if($lastModified = apply_filters('thermal_get_lastcommentmodified', get_lastcommentmodified( 'gmt' ) ) ) {
			$app->lastModified( strtotime( $lastModified . ' GMT' ) );
		}

		$comments = $model->find( $args, $found );

		array_walk( $comments, array( __CLASS__, 'format' ), 'read' );

		return empty( $args['include_found'] ) ? compact( 'comments' ) : compact( 'comments', 'found' );
	}

	public static function find( $app ) {
		$args = $app->request()->get();
		return self::_find( $app, $args );
	}

	public static function findByPost( $app, $post_id ) {
		$post = Posts::findById( $app, $post_id );
		$args = $app->request()->get();
		$args['post_id'] = $post->id;
		return self::_find( $app, $args );
	}

	public static function findById( $app, $id ) {
		$comment = self::model()->findById( $id );
		if ( !$comment ) {
			$app->halt( '404', get_status_header_desc( '404' ) );
		}

		if( $lastModified = apply_filters('thermal_comment_last_modified', $comment->comment_date_gmt ) ) {
			$app->lastModified( strtotime( $lastModified . ' GMT' ) );
		}

		self::format( $comment, 'read' );
		return $comment;
	}

	/**
	 * Filter and validate the parameters that will be passed to the model.
	 * @param array $request_args
	 * @return array
	 */
	protected static function convert_request( $request_args ) {
		// Remove any args that are not allowed by the API
		$request_filters = array(
			'before' => array( ),
			'after' => array( ),
			's' => array( ),
			'paged' => array( ),
			'per_page' => array( '\\intval' ),
			'offset' => array( '\\intval' ),
			'orderby' => array( ),
			'order' => array( ),
			'in' => array( '\\Voce\\Thermal\\v1\\toArray', '\\Voce\\Thermal\\v1\\applyInt' ),
			'parent' => array( '\\intval' ),
			'post_id' => array( '\\intval' ),
			'post_name' => array( ),
			'type' => array( ),
			'status' => array( ),
			'user_id' => array( '\\intval' ),
			'include_found' => array( '\\Voce\\Thermal\\v1\\toBool' ),
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

		//make sure per_page is below MAX
		if ( !empty( $request_args['per_page'] ) ) {
			if ( absint( $request_args['per_page'] ) > \Voce\Thermal\v1\MAX_TERMS_PER_PAGE ) {
				$request_args['per_page'] = \Voce\Thermal\v1\MAX_COMMENTS_PER_PAGE;
			} else {
				$request_args['per_page'] = absint( $request_args['per_page'] );
			}
		}

		//filter status by user privelages
		if ( isset( $request_args['status'] ) && $request_args['status'] !== 'approve' ) {
			if ( is_user_logged_in() ) {
				if ( !current_user_can( 'moderate_comments' ) ) {
					$app->halt( '403', get_status_header_desc( '403' ) );
				}
			} else {
				$app->halt( '401', get_status_header_desc( '401' ) );
			}
		}

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] > \Voce\Thermal\v1\MAX_POSTS_PER_PAGE ) {
			$request_args['per_page'] = \Voce\Thermal\v1\MAX_POSTS_PER_PAGE;
		}

		if ( !empty( $request_args['paged'] ) && !isset( $request_args['include_found'] ) ) {
			$request_args['include_found'] = true;
		}

		return $request_args;
	}

	/**
	 * 
	 * @param \WP_Comment $comment
	 * @param string $state  State of CRUD to render for, options 
	 * 	include 'read', new', 'edit'
	 */
	public static function format( &$comment, $state = 'read' ) {
		if ( !$comment ) {
			return $comment = null;
		}

		//allow for use with array_walk
		if ( func_num_args() > 2 ) {
			$state = func_get_arg( func_num_args() - 1 );
		}
		if ( !in_array( $state, array( 'read', 'new', 'edit' ) ) ) {
			$state = 'read';
		}

		//edit provides a slimmed down response containing only editable fields
		$GLOBALS['comment'] = $comment;


		if ( $comment->comment_approved === '1' ) {
			$status = 'approve';
		} elseif ( $comment->comment_approved === '0' ) {
			$status = 'pending';
		} else {
			$status = ( string ) $comment->comment_approved;
		}

		$data = array(
			'id' => intval( $comment->comment_ID ),
			'id_str' => ( string ) $comment->comment_ID,
			'type' => empty( $comment->comment_type ) ? 'comment' : $comment->comment_type,
			'author' => $comment->comment_author,
			'author_url' => $comment->comment_author_url,
			'parent' => intval( $comment->comment_parent ),
			'parent_str' => ( string ) $comment->comment_parent,
			'date' => ( string ) get_comment_time( 'c', true, $comment ),
			'content' => $comment->comment_content,
			'status' => $status,
			'user' => intval( $comment->user_id ),
			'user_id_str' => ( string ) $comment->user_id
		);

		//add extended data for 'read'
		if ( $state == 'read' ) {
			$avatar = get_avatar( $comment );
			preg_match( "/src='([^']*)'/i", $avatar, $matches );

			$data = array_merge( $data, array(
				'content_display' => apply_filters( 'comment_text', get_comment_text( $comment->comment_ID ), $comment ),
				'avatar' => array(
					array(
						'url' => array_pop( $matches ),
						'width' => 96,
						'height' => 96,
					)
				)
				) );
		}

		$comment = apply_filters_ref_array( 'thermal_comment_entity', array( ( object ) $data, &$comment, $state ) );
	}

}

?>
