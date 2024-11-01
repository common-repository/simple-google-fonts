<?php
/**
 * Register and handle post meta functionality
 * 
 * @package SimpleGoogleFonts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta fields as an array to be used as arguemnts for `register_meta`
 *
 * @since  1.0.0
 * @return array
 */
function sgf_meta_fields() {
	$defaults   = sgf_defaults();
	$global_pid = sgf_get_global_post_id();
	$global_def = [];
	$prefix     = 'sgf_';

	if( $global_pid ) {
		$global_def = sgf_get_all_meta( $global_pid );
	}

	$originals = [
		'headings' => [
			'ff' => [
				'type'     => 'integer',
				'default'  => $defaults[ 'headings' ][ 'ff' ],
				'sanitize' => 'absint'
			],
			'els' => [
				'type'     => 'string',
				'default'  => [],
				'single'   => false,
				'sanitize' => 'sgf_sanitize_headings_values'
			]
		],
		'body' => [
			'ff' => [
				'type'     => 'integer',
				'default'  => $defaults[ 'body' ][ 'ff' ],
				'sanitize' => 'absint'
			],
			'wt' => [
				'type'     => 'string',
				'default'  => $defaults[ 'body' ][ 'wt' ],
				'sanitize' => 'sgf_sanitize_weight'
			],
			'lh' => [
				'type'     => 'number',
				'default'  => $defaults[ 'body' ][ 'lh' ]
			],
			'ls' => [
				'type'     => 'number',
				'default'  => $defaults[ 'body' ][ 'ls' ]
			],
			'ws' => [
				'type'     => 'number',
				'default'  => $defaults[ 'body' ][ 'ws' ]
			]
		],
		'global' => [
			'is' => [
				'type'    => 'boolean',
				'default' => false
			],
			'date' => [
				'type'    => 'string',
				'default' => ''
			]
		]
	];

	$merged = $originals;

	if( $global_def !== false || empty( $global_def ) ) {
		foreach( $merged as $panel => $options ) {
			if( array_key_exists( $panel, $global_def ) ) {
				foreach( $options as $option => $args ) {
					if( array_key_exists( $option, $global_def[ $panel ] ) ) {

						switch( $option ) {
							case 'is':
								$merged[ $panel ][ $option ][ 'default' ] = false;
								break;

							case 'date':
								$merged[ $panel ][ $option ][ 'default' ] = '';
								break;

							default:
								$merged[ $panel ][ $option ][ 'default' ] = $global_def[ $panel ][ $option ];
								break;
						}

					}
				}
			}
		}
	}

	foreach( $merged as $panel => $options ) {
		foreach( $options as $option => $value ) {
			$merged[ $panel ][ $prefix . $option ] = $value;
			unset( $merged[ $panel ][ $option ] );
		}
	}

	return apply_filters( 'sgf_meta_fields', $merged, 
		compact( 'defaults', 'global_pid', 'global_def', 'originals', 'merged' ) 
	);
}

/**
 * Register meta to be used in REST API, for Gutenberg
 * 
 * @since  1.0.0
 * @param  array $meta Array of meta fields to be registered
 * @return void
 */
function sgf_register_meta( $meta ) {
	if( empty( $meta ) ) return;

	$subtypes = sgf_allowed_post_types();

	foreach( $subtypes as $subtype ) {
		foreach( $meta as $panel => $keys ) {
			$count = 0;
			foreach( $keys as $key => $args ) {
				$meta_key = $key . '_' . $panel;
	
				$meta_args = [
					'single'         => true,
					'object_subtype' => $subtype,
					'show_in_rest'   => [
						'schema' => array(
							'type'    => $args[ 'type' ],
							'default' => $args[ 'default' ]
						)
					],
					
				];
	
				if( array_key_exists( 'sanitize', $args ) ) {
					$meta_args[ 'sanitize' ] = $args[ 'sanitize' ];
				}
	
				if( array_key_exists( 'single', $args ) ) {
					$meta_args[ 'single' ] = $args[ 'single' ];
				}
	
				register_meta( 'post', $meta_key, $meta_args );
			}
		}
	}
}

/**
 * Register meta action
 *
 * @since  1.0.0
 * @return void
 */
function sgf_register_meta_action() {
    $meta = sgf_meta_fields();

    // Register them
    sgf_register_meta( $meta );
}
add_action( 'init', 'sgf_register_meta_action' );

/**
 * The post's id where the Global style was last enabled
 *
 * @since  1.0.0
 * @todo   Maybe try something with post date.
 * @return integer Post id
 */
function sgf_get_global_post_id() {
	$meta_key   = 'sgf_is_global';
	$transient  = 'sgf_global_for_single_id';

	if( false === ( $post_ids = get_transient( $transient ) ) ) {
		$post_ids = get_posts( [
			'meta_key'   => $meta_key,
			'meta_value' => true,
			'fields'     => 'ids',
			'post_type'  => sgf_allowed_post_types()
		] );

		set_transient( $transient, $post_ids, MONTH_IN_SECONDS );
	}

	if( empty( $post_ids ) ) return false;
	
	$posts_meta = [];

	foreach( $post_ids as $post_id ) {
		$posts_meta[ 'p' . $post_id ] = sgf_get_all_meta( $post_id );
	}

	$global_dates = [];

	foreach( $posts_meta as $pid => $post_meta ) {
		$global_dates[ $pid ] = $post_meta[ 'global' ][ 'date' ];
	}

	$global_dates = apply_filters( 'sgf_get_global_post_id', $global_dates, compact( 'post_ids', 'post_meta' ) );

	$most_recent = max( $global_dates );
	
	return (int) str_replace( 'p', '', array_search( $most_recent, $global_dates, TRUE ) );
}

/**
 * Makes sure the global style is enabled just from one post by refreshing
 * post meta in the rest.
 * 
 * @since  1.0.0
 * @return void
 */
function sgf_set_the_global_styles() {
	$meta_key   = 'sgf_is_global';
	$date       = 'sgf_date_global';
	$transient  = 'sgf_global_posts_ids';

	if( false === ( $post_ids = get_transient( $transient ) ) ) {
		$post_ids = get_posts( [
			'meta_key'   => $meta_key,
			'meta_value' => true,
			'fields'     => 'ids',
			'post_type'  => sgf_allowed_post_types()
		] );

		set_transient( $transient, $post_ids, MONTH_IN_SECONDS );
	}
	
	if( empty( $post_ids ) ) return;

	if( count( $post_ids ) === 1 ) return;

	$pid = sgf_get_global_post_id();

	if( ( $key = array_search( $pid, $post_ids, TRUE ) ) !== false ) {
		unset( $post_ids[ $key ] );
	} else {
		return;
	}

	set_transient( $transient, $post_ids, MONTH_IN_SECONDS );

	$post_ids = apply_filters( 'sgf_set_the_global_styles', $post_ids, compact( 'post_ids', 'pid' ) );

	foreach( $post_ids as $post_id ) {
		update_post_meta( $post_id, $meta_key, false );
		update_post_meta( $post_id, $date, '' );
	}
}
add_action( 'load-edit.php', 'sgf_set_the_global_styles' );
add_action( 'load-post.php', 'sgf_set_the_global_styles' );
add_action( 'load-post-new.php', 'sgf_set_the_global_styles' );

/**
 * Refresh our transients when the post is published/saved
 *
 * @since  1.0.0
 * @return void
 */
function sgf_refresh_global_ids_transient() {
	delete_transient( 'sgf_global_for_single_id' );
	delete_transient( 'sgf_global_posts_ids' );
}
add_action( 'publish_post', 'sgf_refresh_global_ids_transient' );
add_action( 'save_post', 'sgf_refresh_global_ids_transient' );