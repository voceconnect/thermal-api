<?php

namespace WP_JSON_API;

class APIv1 extends API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $slim ) {
		parent::__construct( $slim );
		$this->registerRoute( 'GET', 'users(/:id)', array( __CLASS__, 'get_users' ) );
		$this->registerRoute( 'GET', 'posts(/:id)', array( __CLASS__, 'get_posts' ) );
		$this->registerRoute( 'GET', 'taxonomies(/:name)', array( __CLASS__, 'get_taxonomies' ) );
		$this->registerRoute( 'GET', 'taxonomies/:name/terms(/:term_id)', array( __CLASS__, 'get_terms' ) );
	}

	public static function get_users( $id = null ) {
		$users = array();
		if ( $id ) {
			$users[] = self::format_user( get_user_by( 'id', $id ) );
			return compact( 'users' );
		}

		return WP_API_BASE . '/users/' . $id;
	}

	public static function get_posts( $id = null ) {
		$posts = array();
		if ( $id ) {
			$posts[] = self::format_post( get_post( $id ) );
			return compact( 'posts' );
		}

		// TODO: Get posts and loop through, setting to $posts[]

		return WP_API_BASE . '/posts/' . $id;
	}

	public static function get_taxonomies( $name = null ) {
		return WP_API_BASE . '/taxonomies/' . $name;
	}

	public static function get_terms( $name, $term_id = null ) {
		return WP_API_BASE . '/taxonomies/' . $name . '/terms/' . $term_id;
	}

	/**
	 * Format post data
	 * @param \WP_Post $post
	 * @return Array Formatted post data
	 */
	public static function format_post( \WP_Post $post ) {
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		$data = array(
			'id'               => $post->ID,
			'id_str'           => (string)$post->ID,
			'permalink'        => get_permalink( $post ),
			'parent'           => $post->post_parent,
			'parent_str'       => (string)$post->post_parent,
			'date'             => get_the_time( 'c', $post ),
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
		);
		wp_reset_postdata();

		return $data;
	}

	/**
	 * Format user data
	 * @param \WP_User $user
	 * @return Array Formatted user data
	 */
	public static function format_user( \WP_User $user ) {}

}
