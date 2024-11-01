<?php
/**
 * Sanitization functions
 * 
 * @package SimpleGoogleFonts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add only allowed values for headings
 *
 * @since  1.0.0
 * @param  string $prop  Heading propriety
 * @param  string $value Propriety value
 * @return string        Allowed value for propriety, in `propriety:value` format
 */
function sgf_sanitize_headings_allowed( $prop, $value ) {
    $allowed  = sgf_allowed_values();
    $defaults = sgf_headings_defaults();

    if( array_key_exists( $value, array_flip( $allowed[ $prop ] ) ) ) {
        return $prop . ':' . $value;
    } else {
        return $prop . ':' . $defaults[ prop ];
    }
}

/**
 * Sanitize Headings values
 *
 * @since  1.0.0
 * @param  string $values Current values, stored as a string in the for of 'el:h1|wt:400|...'
 * @return string         Sanitized values
 */
function sgf_sanitize_headings_values( $values ) {
    $parts = explode( '|', $values );
    
    $items = array_map( function( $item ) {
        $item    = explode( ':', $item );
        $prop    = sanitize_key( $item[ 0 ] );
        $value   = $item[ 1 ];
        $newitem = '';

        switch( $prop ) {
            case 'el':
            case 'wt':
            case 'tt':
                $newitem = sgf_sanitize_headings_allowed( $prop, $value );
                break;

            case 'lh':
            case 'ls':
            case 'ws':
                $newitem = $prop . ':' . floatval( $value ) ;
                break;

            default:
                break;
        }
        
        return $newitem;

    }, $parts );

    return implode( '|', $items );
}

/**
 * Sanitize `Font Weight` value
 *
 * @since  1.0.0
 * @param  string $value Unsanitized value
 * @return string        Sanitized value
 */
function sgf_sanitize_weight( $value ) {
    $allowed = sgf_allowed_values();

    return in_array( $value, $allowed[ 'wt' ], TRUE ) ? $value : '400';
}