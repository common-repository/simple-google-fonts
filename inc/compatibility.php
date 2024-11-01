<?php
/**
 * Checks compatibility with different pieces of software.
 * 
 * @package SimpleGoogleFonts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check compatibility with different versions of software
 *
 * @since  1.0.0
 * @todo   Gutenberg version check, `SGF_PLUGIN_MIN_GB`
 * @return array `el` => software type, `issue` => if this version passes, `ver` => required version
 */
function sgf_compatible_versions() {
    // PHP version check
    if ( version_compare( PHP_VERSION, SGF_PLUGIN_MIN_PHP, '<' ) ) {
        return array( 
            'el'    => 'php', 
            'issue' => false,
            'ver'   => SGF_PLUGIN_MIN_PHP 
        );
    }

    // WP version check
    if ( version_compare( $GLOBALS[ 'wp_version' ], SGF_PLUGIN_MIN_WP, '<' ) ) {
        return array( 
            'el'    => 'wp', 
            'issue' => false,
            'ver'   => SGF_PLUGIN_MIN_WP
        );
    }
    
    return array( 
        'el'    => 'none', 
        'issue' => true,
        'ver'   => null
    );
}

/**
 * Different message for different issues
 *
 * @since  1.0.0
 * @param  string $el Type of software
 * @return string     Notice message
 */
function sgf_wrong_version_message( $el ) {
    $message = '';

    switch( $el ) {
        case 'wp':
            $message = esc_html__( 
                'Simple Google Fonts requires WordPress version %s. Please upgrade and try again.', 'simple-google-fonts' 
            );
            break;

        case 'php':
            $message = esc_html__( 
                'Simple Google Fonts requires PHP version %s. Please contact your web-hosting provider to upgrade it for you.', 'simple-google-fonts' 
            );
            break;
        
        default:
            break;
    }

    return $message;
}

/**
 * Notice message wrapper used when the plugin gets disabled for different reasons
 *
 * @since  1.0.0
 * @param  array   $compatibility `sgf_compatible_versions()` values
 * @param  boolean $return        Echo or return the message
 * @return string                 The message
 */
function sgf_disabled_notice( $compatibility, $return = false ) {
    if( ! $compatibility ) {
        $compatibility = sgf_compatible_versions();
    }

    $message = sprintf( 
        sgf_wrong_version_message( $compatibility[ 'el' ] ), 
        $compatibility[ 'ver' ]
    );

    $html = '<div class="error"><p>%s</p></div>';

    if( $return ) {
        return sprintf( $html, $message );
    } else {
        printf( $html, $message );
    }
    
}

/**
 * Checks if the versions are ok and if they are not it disables the plugin
 * while it's activated.
 *
 * @since  1.0.0
 * @return void
 */
function sgf_check_version() {
    $compatibility = sgf_compatible_versions();

    if ( ! $compatibility[ 'issue' ] ) {
        if ( is_plugin_active( SGF_PLUGIN_BASNAME ) ) {
            deactivate_plugins( SGF_PLUGIN_BASNAME );

            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }

            add_action( 'admin_notices', 'sgf_disabled_notice' );
        }
    }
}
add_action( 'admin_init', 'sgf_check_version' );

/**
 * Makes sure the plugin can't be activated if some versions aren't
 * corresponding
 *
 * @since  1.0.0
 * @return void
 */
function sgf_activation_check() {
    $compatibility = sgf_compatible_versions();

    if ( ! $compatibility[ 'issue' ] ) {
        deactivate_plugins( SGF_PLUGIN_BASNAME );

        wp_die( 
            strip_tags( sgf_disabled_notice( $compatibility, true ) ), 
            '', array( 'back_link' => true )
        );
    }
}
register_activation_hook( SGF_PLUGIN, 'sgf_activation_check' );