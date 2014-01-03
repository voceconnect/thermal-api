<?php

namespace Voce\Thermal\v1;

require_once(__DIR__ . '/../models/Users.php');

class UsersController {

	private static $_model;

	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new UsersModel();
		}
		return self::$_model;
	}

	public static function find( $app ) {
		if ( !is_user_logged_in() ) {
			$state = 'restricted';
		} else {
			$state = 'read';
		}		

		$found = 0;
		$users = array( );
		$request_args = $app->request()->get();

		$args = self::convert_request( $request_args );

		$model = self::model();

		$users = $model->find( $args, $found );
		array_walk( $users, array( __CLASS__, 'format' ), $state );

		return empty( $request_args['no_found_rows'] ) ? compact( 'users', 'found' ) : compact( 'users' );
	}

	public static function findById( $app, $id ) {
		$model = self::model();
		$user = $model->findById($id);
		if ( !$user ) {
			$user->halt( '404', get_status_header_desc('404') );
		}
		
		if ( !is_user_logged_in() ) {
			$state = 'restricted';
		} else {
			$state = 'read';
		}
		
		self::format( $user, $state );
		return $user;
	}

	/**
	 * 
	 * @param \WP_User $user
	 * @param string $state  State of CRUD to render for, options 
	 * 	include 'read', new', 'edit'
	 */
	public static function format( &$user, $state = 'read' ) {
		if ( !$user ) {
			return $user = null;
		}

		//allow for use with array_walk
		if ( func_num_args() > 2 ) {
			$state = func_get_arg( func_num_args() - 1 );
		}
		if ( !in_array( $state, array( 'read', 'new', 'edit', 'restricted' ) ) ) {
			$state = 'read';
		}

		$data = array(
			'id' => $user->ID,
			'id_str' => ( string ) $user->ID,
			'nicename' => $user->data->user_nicename,
			'display_name' => $user->data->display_name,
			'user_url' => $user->data->user_url,
		);
		
		// If we are not on the restricted view then we will add some more admin fields 
		if ( $state !== 'restricted' ) {
			$data = array_merge( $data, array(
					'roles' => 	$user->roles,
					'user_login' => $user->user_login,
					'user_pass' => $user->user_pass,
					'user_email' => $user->user_email,
					'user_status' => $user->user_status,
					'user_registered' => $user->user_registered,
					'user_activation_key' => $user->user_activation_key,
					'caps' => $user->caps,
					'allcaps' => $yuser->allcaps,
					'filter' => $user->filter
			) );
		}		

		if ( $state === 'read' || $state === 'restricted' ) {

			$avatar = get_avatar( $user->ID );
			preg_match( "/src='([^']*)'/i", $avatar, $matches );

			$data = array_merge( $data, array(
				'posts_url' => get_author_posts_url( $user->ID ),
				'avatar' => array(
					array(
						'url' => array_pop( $matches ),
						'width' => 96,
						'height' => 96,
					)
				),
				'meta' => ( object ) get_user_meta( $user->ID )
				) );
		}
		
		if ( $state === 'restricted' ) {
			unset($data['meta']->wp_user_level);
			unset($data['meta']->wp_capabilities);
			unset($data['meta']->{'wp_usersettings'});
			unset($data['meta']->{'wp_usersettingstime'});			
			unset($data['meta']->{'wp_user-settings'});
			unset($data['meta']->{'wp_user-settings-time'});
		}
		
		$user = apply_filters_ref_array( 'thermal_user_entity', array( ( object ) $data, &$user, $state ) );
	}

	/**
	 * Filter and validate the parameters that will be passed to the model.
	 * @param array $request_args
	 * @return array
	 */
	public static function convert_request( $request_args ) {
		$request_filters = array(
			'paged' => array( ),
			'per_page' => array( '\\intval' ),
			'offset' => array( '\\intval' ),
			'orderby' => array( ),
			'order' => array( ),
			'in' => array( __NAMESPACE__ . '\\toArray', __NAMESPACE__ . '\\applyInt' ),
			'include_found' => array( __NAMESPACE__ . '\\toBool' )
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
		
		if(!empty($request_args['in'])) {
			$request_args['include'] = $request_args['in'];
			unset($request_args['in']);
		}

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] > MAX_USERS_PER_PAGE ) {
			$request_args['per_page'] = MAX_USERS_PER_PAGE;
		}

		if ( empty( $request_args['paged'] ) && empty( $request_args['include_found'] ) ) {
			$request_args['count_total'] = false;
		}

		return $request_args;
	}

}