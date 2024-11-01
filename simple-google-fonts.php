<?php
/**
 * Plugin Name: Simple Google Fonts
 * Plugin URI: 
 * Description: Simple Google Fonts adds the posibility to change default fonts to Google fonts from within the new WordPress v5.0 editor, codename Gutenberg. 
 * Author: acosmin
 * Author URI: 
 * Version: 1.0.2
 * License: GPL3+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package SimpleGoogleFonts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Some constants
define( 'SGF_PLUGIN',         __FILE__ );
define( 'SGF_PLUGIN_VERSION', '1.0.2'  );
define( 'SGF_PLUGIN_MIN_WP',  '4.9.8'  );
define( 'SGF_PLUGIN_MIN_PHP', '5.4.0'  );
define( 'SGF_PLUGIN_MIN_GB',  '3.7.0'  );

define( 'SGF_PLUGIN_BASNAME',  plugin_basename( SGF_PLUGIN ) );
define( 'SGF_PLUGIN_DIR_PATH', plugin_dir_path( SGF_PLUGIN ) );

// Compatibility with WP, PHP, Gutenberg versions
require SGF_PLUGIN_DIR_PATH . 'inc/compatibility.php';

$sgf_compatible = sgf_compatible_versions();

if( ! $sgf_compatible[ 'issue' ] ) {
	return;
}

// Initialize
require_once SGF_PLUGIN_DIR_PATH . 'inc/helpers.php';
require_once SGF_PLUGIN_DIR_PATH . 'inc/sanitize.php';
require_once SGF_PLUGIN_DIR_PATH . 'inc/enqueue.php';
require_once SGF_PLUGIN_DIR_PATH . 'inc/meta.php';