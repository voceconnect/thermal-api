<?php

namespace Voce\Thermal\v1\Controllers;

class Users {

	private static $_model;

	private static function get_list_users_cap() {
		return apply_filters('thermal_list_users_cap', false);
	}

	public static function model() {
		if ( !isset( self::$_model ) ) {
			self::$_model = new \Voce\Thermal\v1\Models\Users();
		}
		return self::$_model;
	}

	public static function find( $app ) {
		if ( ( $list_users_cap = self::get_list_users_cap() ) && !current_user_can( $list_users_cap ) ) {
			$app->halt( '403', get_status_header_desc( '403' ) );
		}

		$found = 0;
		$users = array( );
		$request_args = $app->request()->get();

		$args = self::convert_request( $request_args );

		$model = self::model();

		$users = $model->find( $args, $found );
		array_walk( $users, array( __CLASS__, 'format' ), 'read' );

		return ! empty( $request_args['count_total'] ) ? compact( 'users', 'found' ) : compact( 'users' );
	}

	public static function findById( $app, $id ) {
		if ( ( $list_users_cap = self::get_list_users_cap() ) && !current_user_can( $list_users_cap ) && $id !== get_current_user_id() ) {
			$app->halt( '403', get_status_header_desc( '403' ) );
		}

		$model = self::model();
		$user = $model->findById($id);
		if ( !$user ) {
			$user->halt( '404', get_status_header_desc('404') );
		}
		self::format($user, 'read');
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
		if ( !in_array( $state, array( 'read', 'new', 'edit' ) ) ) {
			$state = 'read';
		}

		$data = array(
			'id' => $user->ID,
			'id_str' => ( string ) $user->ID,
			'nicename' => $user->data->user_nicename,
			'display_name' => $user->data->display_name,
			'user_url' => $user->data->user_url,
		);

		if ( $state === 'read' ) {

			$avatar = get_avatar( $user->ID );
			$a_match = preg_match( "/src='([^']*)'/i", $avatar, $matches );

			if ( $avatar and $a_match ) {
				$data = array_merge( $data, array(
					'avatar' => array(
						array(
							'url' => array_pop( $matches ),
							'width' => 96,
							'height' => 96,
						)
					)
				) );
			}

			$data = array_merge( $data, array(
				'posts_url' => get_author_posts_url( $user->ID ),
				'meta' => ( object ) array(
					'description' => get_user_meta($user->ID, 'description', true),
					'first_name' => get_user_meta( $user->ID, 'first_name', true),
					'last_name' => get_user_meta( $user->ID, 'last_name', true),
					'nickname' => get_user_meta( $user->ID, 'nickname', true),
					)
				)
			);
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
			'in' => array( '\\Voce\\Thermal\\v1\\toArray', '\\Voce\\Thermal\\v1\\applyInt' ),
			'include_found' => array( '\\Voce\\Thermal\\v1\\toBool' ),
			'who' => array( )
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

		if ( !empty( $request_args['per_page'] ) && $request_args['per_page'] > \Voce\Thermal\v1\MAX_USERS_PER_PAGE ) {
			$request_args['per_page'] = \Voce\Thermal\v1\MAX_USERS_PER_PAGE;
		}

		$request_args['count_total'] = ! ( empty( $request_args['paged'] ) && empty( $request_args['include_found'] ) );

		if ( !empty( $request_args['who']) && !in_array( $request_args['who'], array( 'authors' ) ) ) {
			unset( $request_args['who'] );
		}
		return $request_args;
	}

}