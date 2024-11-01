<?php
/**
 * Enqueue Assets
 * 
 * @package SimpleGoogleFonts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue editor assets for backend
 *
 * @since  1.0.0
 * @return void
 */
function sgf_enqueue_editor_assets() {
    $allowed   = apply_filters( 'sgf_allowed_post_types', [ 'post', 'page' ] );
    $post_type = get_post_type();

    if( $post_type === false || ! array_key_exists( $post_type, array_flip( $allowed ) ) ) return;

    // JavaScript
    wp_enqueue_script(
        'sgf-editor-js',
        plugins_url( '/js/dist/plugin.bundle.js', dirname( __FILE__ ) ), [ 
            'wp-blocks', 
            'wp-i18n', 
            'wp-element',
            'wp-components',
            'wp-edit-post',
            'wp-plugins',
            'wp-data'
        ],
        SGF_PLUGIN_VERSION
    );
    
    // CSS
    wp_enqueue_style(
        'sgf-editor',
        plugins_url( 'css/dist/plugin.bundle.css', dirname( __FILE__ ) ),
        [ 'wp-edit-blocks' ],
        SGF_PLUGIN_VERSION
    );

    $global_values = null;

    // Use this as a different filter for consistency
    // Also used in `sgf_enqueue_fonts_setup()`
    $weighs_defaults = apply_filters( 'sgf_enqueue_fonts_auto_add_defaults', true );

    $info = [
        'body_id'     => 'sgf-enqueue-body-css',
        'headings_id' => 'sgf-enqueue-headings-css',
        'style_id'    => 'sgf-inline-styles-css',
        'default_url' => 'https://fonts.googleapis.com/css?family=Open+Sans%3A400%2C400i&ver=1.0.0',
        'fonts_url'   => 'https://fonts.googleapis.com/css',
        'defaults'    => sgf_defaults(),
        'headings'    => sgf_headings_defaults(),
        'weights_def' => $weighs_defaults,
        'timeout'     => 1000
    ];

    if( $global_pid = sgf_get_global_post_id() ) {
        $global_values = sgf_get_all_meta( $global_pid, true );

        if( $global_values ) {
            $info[ 'global_vals' ] = $global_values;
            $info[ 'global_vals' ][ 'pid' ] = (int) $global_pid;
        }
    }

    wp_localize_script( 'sgf-editor-js', 'simpleGFonts',
        apply_filters( 'sgf_enqueue_editor_assets_info', $info )
    );
}
add_action( 'enqueue_block_editor_assets', 'sgf_enqueue_editor_assets' );

/**
 * Enqueue Google fonts and inline styles for global view
 *
 * @since  1.0.0
 * @todo   Recheck conditions and priority. `is_singular()`
 * @return void
 */
function sgf_enqueue_fonts_front_end() {
    if( is_singular() ) return;
    
    if( ! ( $style = sgf_enqueue_fonts_setup( false ) ) ) return;

    $handle = apply_filters( 'sgf_enqueue_fonts_front_end_handle', 'sgf-front-end' );

    wp_enqueue_style( $handle, $style, [], '1.0.0', 'all' );

    wp_add_inline_style( $handle, sgf_styles_frontend( false ) );
}
add_action( 'wp_enqueue_scripts', 'sgf_enqueue_fonts_front_end', 998 );

/**
 * Enqueue Google fonts and inline styles for single view
 *
 * @since  1.0.0
 * @todo   Recheck conditions and priority. `is_singular()`
 * @return void
 */
function sgf_enqueue_fonts_front_end_single() {
    global $post;

    if( ! is_object( $post ) ) return;

    if( ! is_singular() ) return;

    if( ! ( $style = sgf_enqueue_fonts_setup( $post->ID ) ) ) return;

    $handle = apply_filters( 'sgf_enqueue_fonts_front_end_single_handle', 'sgf-front-end-single' );

    wp_enqueue_style( $handle, $style, [], '1.0.0', 'all' );

    wp_add_inline_style( $handle, sgf_styles_frontend( $post->ID ) );
}
add_action( 'wp_enqueue_scripts', 'sgf_enqueue_fonts_front_end_single', 997 );

/**
 * Adds query arguments to the Google Fonts URL making sure the correct fonts
 * and weights are loaded
 *
 * @todo   subsets
 * @since  1.0.0
 * @param  string  $part   The panel from which to get the meta values
 * @param  integer $postID Current post id
 * @return string          Compiled URL with arguments
 */
function sgf_enqueue_fonts_setup( $postID ) {
    $meta    = sgf_get_meta_with_fallback( $postID );
    $fonts   = sgf_get_fonts();
    $allowed = sgf_allowed_values();
    
    $families = [];

    if( ! $meta ) {
        $meta = sgf_use_filtered_defaults();
    };

    if( ! $meta ) return false; 
    
    if( isset( $meta[ 'headings' ][ 'ff' ] ) && $meta[ 'headings' ][ 'ff' ] !== 0 ) {
        $ff       = $fonts[ $meta[ 'headings' ][ 'ff' ] ];
        $name     = esc_attr( $ff[ 'f' ] );
        $variants = $ff[ 'v' ];
        $weights  = [];

        if( ! empty( $meta[ 'headings' ][ 'els' ] ) ) {
            $headings = $meta[ 'headings' ][ 'els' ];

            foreach( $headings as $heading ) {
                $heading = sgf_parse_heading_values( $heading );

                if( ! array_key_exists( $heading[ 'wt' ], array_flip( $allowed[ 'wt' ] ) ) ) {
                    continue;
                }

                $weights[] = $heading[ 'wt' ];
            }
        }

        $families[] = sgf_convert_weights_for_url( $weights, $name, $variants );
    }

    if( isset( $meta[ 'body' ][ 'ff' ] ) && $meta[ 'body' ][ 'ff' ] !== 0 ) {
        $ff       = $fonts[ $meta[ 'body' ][ 'ff' ] ];
        $name     = esc_attr( $ff[ 'f' ] );
        $variants = $ff[ 'v' ];

        $weights  = [];
        $weight  = $meta[ 'body' ][ 'wt' ];

        if( array_key_exists( $weight, array_flip( $allowed[ 'wt' ] ) ) ) {
            $weights[] = $weight;
        }

        $families[] = sgf_convert_weights_for_url( $weights, $name, $variants, $weight );
    }

    if( empty( $families ) ) return NULL;

    return add_query_arg(
        array(
            'family' => urlencode( implode( '|', $families ) )
        ), 'https://fonts.googleapis.com/css'
    );
}

/**
 * Compiles styles for the front-end
 *
 * @since  1.0.0
 * @todo   Simplify this, maybe use some `sprintf()` and recheck conditions.
 * @param  int|boolean $postID Integer ($post->id) for a certain post or false for global
 * @return string              Compiled CSS or empty string if no fonts are selected.
 */
function sgf_styles_frontend( $postID ) {
    $fonts = sgf_get_fonts();
    $meta  = sgf_get_meta_with_fallback( $postID );
    $style = '';
    $headings = sgf_allowed_values()[ 'headings' ];

    $text_logo = apply_filters( 'sgf_styles_frontend_txt_logo', '.site-title' );

    if( ! $meta ) {
        $meta = sgf_use_filtered_defaults();
    };

    if( ! $meta ) return;

    if( sgf_check_if_ffs_selected( $meta ) ) {
        $style .= '
        body {
            font-weight: 400 !important;
            font-style: normal;
        }
        h1, h2, h3, h4, h5, h6 {
            font-weight: 400 !important;
            font-style: normal;
        }';
    }
    
    if( array_key_exists( 'body', $meta ) ) {

        if( isset( $meta[ 'body' ][ 'ff' ] ) && $meta[ 'body' ][ 'ff' ] !== 0 ) {
            $ff = $fonts[ $meta[ 'body' ][ 'ff' ] ][ 'f' ];

            $style .= '
            html, body, input, select, textarea {
                font-family: "' . esc_html( $ff ) . '", sans-serif !important;
            }';
        }

    }

    if( array_key_exists( 'headings', $meta ) ) {

        if( isset( $meta[ 'headings' ][ 'ff' ] ) && $meta[ 'headings' ][ 'ff' ] !== 0 ) {
            $ff = $fonts[ $meta[ 'headings' ][ 'ff' ] ][ 'f' ];

            $style .= '
            h1, h2, h3, h4, h5, h6, ' . esc_html( $text_logo ) . ',
            h1 *, h2 *, h3 *, h4 *, h5 *, h6 *, ' . esc_html( $text_logo ) . ' * {
                font-family: "' . esc_html( $ff ) . '", sans-serif !important;
                font-weight: 400;
            }';
        }

    }

    if( array_key_exists( 'body', $meta ) ) {

        if( isset( $meta[ 'body' ][ 'wt' ] ) && $meta[ 'body' ][ 'wt' ] !== '400' ) {
            $style .= 'body { font-weight: ' . esc_html( $meta[ 'body' ][ 'wt' ] ) . ' !important; }';
        }
        $style .= 'body { ';

            if( isset( $meta[ 'body' ][ 'lh' ] ) ) {
                $style .= 'line-height: ' . esc_html( $meta[ 'body' ][ 'lh' ] ) . ' !important; ';
            }

            if( isset( $meta[ 'body' ][ 'ls' ] ) ) {
                $style .= 'letter-spacing: ' . esc_html( $meta[ 'body' ][ 'ls' ] ) . 'em !important; ';
            }

            if( isset( $meta[ 'body' ][ 'ws' ] ) ) {
                $style .= 'word-spacing: ' . esc_html( $meta[ 'body' ][ 'ws' ] ) . 'em !important; ';
            }

        $style .= '}';
    }

    if( ! empty( $meta[ 'headings' ][ 'els' ] ) ) {
        foreach( $meta[ 'headings' ][ 'els' ] as $headingmeta ) {
            $headingmeta = sgf_parse_heading_values( $headingmeta );

            foreach( $headings as $heading ) {
                if( $headingmeta[ 'el' ] === $heading ) {
                    $add_logo = '';

                    if( $heading === 'h1' ) {
                        $add_logo = sprintf( ', %1$s, %1$s * ', esc_html( $text_logo ) );
                    }

                    $style .= $heading . ', ' . $heading . ' *' . $add_logo . ' { ';

                        if( $headingmeta[ 'wt' ] !== '400' ) {
                            $style .= 'font-weight: ' . esc_html( $headingmeta[ 'wt' ] ) . ' !important; ';
                        }
                        
                        if( isset( $headingmeta[ 'tt' ] ) ) {
                            $style .= 'text-transform: ' . esc_html( $headingmeta[ 'tt' ] ) . ' !important; ';
                        }

                        if( isset( $headingmeta[ 'lh' ] ) ) {
                            $style .= 'line-height: ' . esc_html( $headingmeta[ 'lh' ] ) . ' !important; ';
                        }

                        if( isset( $headingmeta[ 'ls' ] ) ) {
                            $style .= 'letter-spacing: ' . esc_html( $headingmeta[ 'ls' ] ) . 'em !important; ';
                        }

                        if( isset( $headingmeta[ 'ws' ] ) ) {
                            $style .= 'word-spacing: ' . esc_html( $headingmeta[ 'ws' ] ) . 'em !important; ';
                        }

                    $style .= '}';
                }
            }
        }
    }

    $style = apply_filters( 
        'sgf_styles_frontend', $style, 
        $postID, compact( 'fonts', 'meta', 'headings' )
    );
    
    return sgf_minify_css( $style );
}

/**
 * Make sure DNS prefetch is added
 *
 * @since  1.0.0
 * @param  array  $hints         URLs to print for resource hints.
 * @param  string $relation_type The relation type the URLs are printed for, e.g. 'preconnect' or 'prerender'.
 * @return array                 New hints.
 */
function sgf_fonts_dns_prefetch( $hints, $relation_type ) {
    $fonts = 'fonts.googleapis.com';
    $check = sgf_is_editor_page();

    if ( 'dns-prefetch' === $relation_type && ! in_array( $fonts, $hints, TRUE ) && $check ) {
        $hints[] = $fonts;
    }

    return $hints;
}
add_filter( 'wp_resource_hints', 'sgf_fonts_dns_prefetch', 10, 2 );

/**
 * Dequeue other Google Fonts based on URL
 *
 * @since  1.0.0
 * @todo   Maybe do it just on front-end, use `is_admin()`
 * @return void
 */
function sgf_dequeueu_other_fonts() {
    global $wp_styles;

    if ( ! ( $wp_styles instanceof WP_Styles ) ) {
		return;
    }
    
    $allowed = apply_filters( 'sgf_dequeueu_other_fonts', [ 
        'open-sans', 'wp-editor-font', 'sgf-front-end', 'sgf-front-end-single' 
    ] );
    
    foreach( $wp_styles->registered as $style ) {
        $handle = $style->handle;
        $src    = $style->src;
        $gfonts = strpos( $src, 'fonts.googleapis' );
        
        if( $gfonts !== false ) {
            if( ! array_key_exists( $handle, array_flip( $allowed ) ) ) {
                wp_dequeue_style( $handle );
            }
        }
    }
}
add_action( 'wp_enqueue_scripts', 'sgf_dequeueu_other_fonts', 999 );