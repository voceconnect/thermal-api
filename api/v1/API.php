<?php

namespace WP_JSON_API;

if ( ! defined( 'MAX_POSTS_PER_PAGE' ) )
	define( 'MAX_POSTS_PER_PAGE', 10 );

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
		return WP_API_BASE . '/users/' . $id;
	}

	public static function get_posts( $id = null ) {
		$defaults = array(
			'found_posts' => false,
		);

		$args = $_GET;

		if ( ! is_null( $id ) ) {
			$args['p'] = (int)$id;
		}

		if ( isset( $_GET['taxonomy'] ) && is_array( $_GET['taxonomy'] ) ) {
			$args['tax_query'] = array(
				'relation' => 'OR',
			);

			foreach ( $_GET['taxonomy'] as $key => $value ) {
				$args['tax_query'] = array(
					'taxonomy' => $key,
					'terms' => is_array( $value ) ? $value : array(),
					'field' => 'id',
				);
			}
		}

		if ( isset( $_GET['after'] ) ) {
			$date = date('Y-m-d', strtotime( $_GET['after'] ) );
			add_filter( 'posts_where', function( $where ) use ( $date ) {
				$where .= " AND post_date > '$date'";
				return $where;
			} );
		}

		if ( isset( $_GET['before'] ) ) {
			$date = date('Y-m-d', strtotime( $_GET['before'] ) );
			add_filter( 'posts_where', function( $where ) use ( $date ) {
				$where .= " AND post_date < '$date'";
				return $where;
			} );
		}

		if ( isset( $_GET['author'] ) ) {
			if ( is_array( $_GET['author'] ) ) {
				$args['author'] = implode( ',', $_GET['author'] );
			} else {
				$args['author'] = (int)$_GET['author'];
			}
		}

		if ( isset( $_GET['cat'] ) ) {
			if ( is_array( $_GET['cat'] ) ) {
				$args['cat'] = implode( ',', $_GET['cat'] );
			} else {
				$args['cat'] = (int)$_GET['cat'];
			}
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

		if ( isset( $_GET['orderby'] ) and in_array( $_GET['orderby'], $valid_orders ) ) {
			$args['orderby'] = $_GET['orderby'];
		}

		if ( isset( $_GET['per_page'] ) ) {
			if ( $_GET['per_page'] >= 1 and $_GET['per_page'] <= MAX_POSTS_PER_PAGE ) {
				$args['posts_per_page'] = (int)$_GET['per_page'];
			}
		}

		if ( isset ( $_GET['paged'] ) ) {
			$args['found_posts'] = true;
		}

		return new \WP_Query( array_merge( $defaults, $args ) );
	}

	public static function get_taxonomies( $name = null ) {
		return WP_API_BASE . '/taxonomies/' . $name;
	}

	public static function get_terms( $name, $term_id = null ) {
		return WP_API_BASE . '/taxonomies/' . $name . '/terms/' . $term_id;
	}
}
