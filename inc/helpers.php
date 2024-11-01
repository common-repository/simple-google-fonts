<?php
/**
 * Utility functions
 * 
 * @package SimpleGoogleFonts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if current page is using Gutenberg
 *
 * @since  1.0.0
 * @return boolean
 */
function sgf_is_editor_page() {
    return function_exists( 'is_gutenberg_page' ) && is_gutenberg_page();
}

/**
 * Headings defaults
 *
 * @since  1.0.0
 * @return array
 */
function sgf_headings_defaults() {
	$headings = sgf_allowed_values()[ 'headings' ];
	$defaults = [];

	foreach( $headings as $heading ) {
		$defaults[ $heading ] = [ 
			'el' => $heading,
			'wt' => '400',
			'tt' => 'none',
			'lh' => 1.8,
			'ls' => 0,
			'ws' => 0
		];
	}

	return apply_filters( 'sgf_headings_defaults', $defaults, $headings );
}

/**
 * Options defaults
 *
 * @since  1.0.0
 * @return array
 */
function sgf_defaults() {
    return apply_filters( 'sgf_defaults', [
        'headings' => [
            'ff' => 0
        ],
        'body' => [
            'ff' => 0,
            'wt' => '400',
            'lh' => 1.8,
			'ls' => 0,
			'ws' => 0
        ]
    ] );
}

/**
 * Allowed values for some options
 *
 * @since  1.0.0
 * @return array
 */
function sgf_allowed_values() {
	return apply_filters( 'sgf_allowed_values', [
		'tt' => [
			'none', 
			'capitalize', 
			'uppercase', 
			'lowercase'
		],
		'wt' => [ 
			'100', '100i', 
			'200', '200i', 
			'300', '300i',
			'400', '400i',
			'500', '500i',
			'600', '600i',
			'700', '700i',
			'800', '800i',
			'900', '900i'
		],
		'headings' => [
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
		]
	] );
}

/**
 * Returns an associative array with all the Google fonts.
 * 
 * @since  1.0.0
 * @return array
 */
function sgf_get_fonts() {
	$response = wp_remote_get( plugins_url( '/js/src/json/google-fonts.json', dirname( __FILE__ ) ) );

	return json_decode( wp_remote_retrieve_body( $response ), TRUE );
}

/**
 * Gets the font index from the fonts array
 *
 * @since  1.0.1
 * @param  string $name Font family name
 * @return int          Font family index, 0 if not found.
 */
function sgf_get_font_id( $name ) {
	$fonts   = sgf_get_fonts();
	$font_id = 0;

	$font = array_filter( $fonts, function( $font ) use( $name ) {
		return $font[ 'f' ] === $name;
	} );

	if( ! empty( $font ) ) {
		$font_id = array_keys( $font )[ 0 ];

		return $font_id;
	}

	return $font_id;
}

/**
 * Gets all the meta registered by us and arranges it by panels and values
 *
 * @since  1.0.0
 * @todo   Simplify this, less conditions, maybe some wrapper functions.
 * @param  integer       $postID
 * @param  booealn       $raw    Raw data
 * @return array|boolean         Array on success, false otherwise.
 */
function sgf_get_all_meta( $postID, $raw = false ) {
	$all      = get_post_meta( $postID );
	$meta     = [];
	$prefix   = 'sgf_';
	$raw_meta = [];

	if( empty( $all ) ) return false;
	
	if( $raw ) {
		foreach( $all as $meta_key => $meta_value ) {
			if( strpos( $meta_key, $prefix ) !== false ) {
				$parts = explode( '_', str_replace( $prefix, '', $meta_key ) );

				$prop  = $parts[ 0 ];
				$panel = $parts[ 1 ];
			
				if( $meta_key === 'sgf_els_headings' ) {
					$raw_meta[ $meta_key ] = $meta_value;
				} else {
					switch( $prop ) {
						case 'ff':
							$raw_meta[ $meta_key ] = (integer) $meta_value[ 0 ];
							break;
						case 'lh':
						case 'ls':
						case 'ws':
							$raw_meta[ $meta_key ] = floatval( $meta_value[ 0 ] );
							break;
						case 'is':
							$raw_meta[ $meta_key ] = (boolean) $meta_value[ 0 ];
							break;
						default:
							$raw_meta[ $meta_key ] = $meta_value[ 0 ];
							break;
					}
				}

				
			}
		}

		return apply_filters( 'sgf_get_all_meta', $raw_meta, compact( 'postID', 'raw', 'all' ) );
	}

    foreach( $all as $meta_key => $meta_value ) {
        if( strpos( $meta_key, $prefix ) !== false ) {
            $parts = explode( '_', str_replace( $prefix, '', $meta_key ) );

            $prop  = $parts[ 0 ];
			$panel = $parts[ 1 ];

			switch( $prop ) {
				case 'ff':
					$meta[ $panel ][ $prop ] = (int) $meta_value[ 0 ];
					break;

				case 'els':
					$meta[ $panel ][ $prop ] = $meta_value;
					break;

				case 'lh':
				case 'ls':
				case 'ws':
					$meta[ $panel ][ $prop ] = floatval( $meta_value[ 0 ] );
					break;

				case 'is':
					$meta[ $panel ][ $prop ] = (boolean) $meta_value[ 0 ];
					break;

				default:
					$meta[ $panel ][ $prop ] = $meta_value[ 0 ];
					break;
			}
        }
	}
	
	return apply_filters( 'sgf_get_all_meta', $meta, compact( 'postID', 'raw', 'all' ) );
}

/**
 * Parse meta value for a selected heading
 *
 * @since  1.0.0
 * @param  string $heading Heading meta value
 * @return array           Array containing all the heading's proprieties and values.
 */
function sgf_parse_heading_values( $heading ) {
	$values = [];

	$proprieties = explode( '|', $heading );

	foreach( $proprieties as $propriety ) {
		$propriety = explode( ':', $propriety );
		$values[ $propriety[ 0 ] ] = $propriety[ 1 ];
	}

	return $values;
}

/**
 * Stringify headin values from an array.
 *
 * @since  1.0.2
 * @param  array  $heading Proprieties and values in an array.
 * @return string          Empty string if no $heading, values in a string otherwise.
 */
function sgf_stringify_heading_values( $heading = [] ) {
	if( empty( $heading ) ) return '';

	$proprieties = [];
	
	foreach( $heading as $propriety => $value ) {
		$proprieties[] = $propriety . ':' . $value;
	}

	return implode( '|', $proprieties );
}

/**
 * Converts font weights into a string to be used in a Google Fonts URL
 *
 * @since  1.0.0
 * @param  array   $weights  Current weights
 * @param  string  $font     Current font family
 * @param  array   $variants Available font weights for this family
 * @param  boolean $weight   Check if we should convert for headings
 * @return string            Converted string
 */
function sgf_convert_weights_for_url( $weights = [], $font, $variants, $weight = false ) {
	$default = '400';
	$italic  = $default . 'i';
	$pattern = '%1$s:%2$s';
	
	if( apply_filters( 'sgf_enqueue_fonts_auto_add_defaults', true ) ) {
		if( $weight !== false ) {
			if( ! in_array( $default, $weights, TRUE ) ) {
				array_push( $weights, $default );
			}
		} else {
			if( $weight !== $default ) {
				array_push( $weights, $default );
			}
		}

		if( ! in_array( $italic, $weights, TRUE ) && in_array( $italic, $variants, TRUE ) ) {
			array_push( $weights, $italic );
		}
	}

	foreach( $weights as $weight ) {
		if( strpos( $weight, 'i' ) ) {
			continue;
		}

		$newitalic = $weight . 'i';

		if( $newitalic !== $weight && in_array( $newitalic, $variants, TRUE )  ) {
			array_push( $weights, $newitalic );
		}
	}

	$weights = array_map( 'esc_attr', array_unique( $weights ) );
	
	sort( $weights );

	$raw = $weights;

	$weights = sprintf( $pattern, $font, implode( ',', $weights ) );

	return apply_filters( 
		'sgf_convert_weights_for_url', $weights, 
		compact( 'raw', 'font', 'variants', 'weight' ) 
	);
}

/**
 * Gets meta for a post id and if that post doesn't have the necessary meta,
 * it will fallback to the global option.
 *
 * @since  1.0.0
 * @param  int|boolean   $postID Integer for a certain post, `false` if you want to return global meta.
 * @return array|boolean         Returns false if no meta or an array of selected meta.
 */
function sgf_get_meta_with_fallback( $postID ) {
	$meta = false;

	if( $postID ) {
		$meta = sgf_get_all_meta( $postID ); // will return false if no meta
	}

	if( ! $meta  ) {
        $global_id = sgf_get_global_post_id();

        if( $global_id ) {
            $meta = sgf_get_all_meta( $global_id );
        }
	};
	
	return $meta;
}

/**
 * Checks if the font families are set.
 *
 * @since  1.0.0
 * @param  array   $meta Post meta to check from
 * @return boolean
 */
function sgf_check_if_ffs_selected( $meta ) {
	return (
		( array_key_exists( 'body', $meta ) && isset( $meta[ 'body' ][ 'ff' ] ) ) ||
		( array_key_exists( 'headings', $meta ) && isset( $meta[ 'headings' ][ 'ff' ] ) )
	);
}

/**
 * Minify CSS
 *
 * @since  1.0.0
 * @todo   might need some rework :)
 * @param  string $css Unminified CSS string.
 * @return string
 */
function sgf_minify_css( $css ) {
	preg_match_all( '/[^\s"]+|"[^"]*"/', wp_strip_all_tags( $css ), $matches );
	
	return str_replace( '*', ' *', implode( '', $matches[ 0 ] ) );
}

/**
 * Allowed post types where this plugin can be used
 *
 * @since  1.0.1
 * @return array Allowed post types
 */
function sgf_allowed_post_types() {
	return apply_filters( 'sgf_allowed_post_types', [ 'post', 'page' ] );
}

/**
 * Checks if a filter is applied on our defaults.
 *
 * @since  1.0.2
 * @return boolean True if filters are applied, false if not.
 */
function sgf_filtered_defaults() {
	global $wp_filter;
	
    if( isset( $wp_filter[ 'sgf_defaults' ] ) || isset( $wp_filter[ 'sgf_headings_defaults' ] ) ) {
		return true;
	}

    return false;
}

/**
 * If filters are applied, use them as defaults and as initial globals.
 *
 * @since  1.0.2
 * @return array All the defaults with compiled Headings defautlts.
 */
function sgf_use_filtered_defaults() {
	$check    = sgf_filtered_defaults();
	$defaults = false;

	if( $check ) {
		$defaults = sgf_defaults();
		$headings = sgf_headings_defaults();
		$count    = 0;

		foreach( $headings as $heading => $options ) {
			$defaults[ 'headings' ][ 'els' ][ $count ] = sgf_stringify_heading_values( $options );
			$count++;
		}
	}

	return apply_filters( 'sgf_use_filtered_defaults', $defaults, $check );
}