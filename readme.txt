=== Simple Gutenberg Google Fonts ===
Contributors: acosmin 
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
Requires at least: 4.9.8
Tested up to: 4.9.8
Stable tag: 1.0.2
Requires PHP: 5.4
Tags: fonts, Gutenberg, Google Fonts, gutenberg fonts, font

== Description ==
Simple Google Fonts adds the posibility to change default fonts to Google fonts from within the new WordPress v5.0 editor, codename Gutenberg. You'll need Gutenberg installed and activated in order to use this plugin.

### What it does?
Simple Google Fonts adds the posibility to change default fonts to Google fonts from within the new WordPress v5.0 editor, codename Gutenberg. You'll need Gutenberg installed and activated in order to use this plugin.

#### Things to know:
* Read the `After activation` section for some info on how to use it.
* Supported post types: **posts** and **pages**
* You can set Google fonts for individual posts and pages or globally.
* Globally means that the styles from a selected post will apply to the entire website. It will also apply the styles in the Editor, for new or old posts (that don't have any styles applied to them).
* The post needs to be published for you to be able to use its styles globally. If for some reason you change that post to draft mode and the global mode is enabled on it, the option will reset and you'll need to enable it again after you publish the post.
* It's safe to say that if you don't have an Internet connection, Google fonts will not display (unless you have them installed on your system).

#### To do list:
* Add support for `word-spacing` for both `body` and `headings`
* Auto add `700` and `700i` weights if the font supports those variants.

=== Installation ===

#### From within WordPress

1. Visit 'Plugins > Add New'.
2. Search for `Simple Google Fonts`.
3. Activate `Simple Google Fonts` from your Plugins page.
4. Go to "after activation" below.

#### Manually

1. Upload the `simple-google-fonts` folder to the `/wp-content/plugins/` directory
2. Activate the `Simple Google Fonts` plugin through the 'Plugins' menu in WordPress
3. Go to "after activation" below.

#### After activation

1. Edit/add a post in Gutenberg mode
2. When you're editing the post, you'll see an icon, `A`,  in the top-right corner of your screen (if you hover over it, it will say `Simple Google Fonts`). From there you will be able to change font families and styles for `body` and `headings`. If the `A` icond doesn't show up, you can click on the 3 dots, top-right corner, and in the `Plugins` sub-section, click on `Simple Google Fonts`. If it doesn't show up in that menu, then you're either not editing a post/page or the plugin isn't activated.
3. You're done.

=== GDPR Notice ===
By using this plugin (which requests data from Google Fonts servers) you consent that Google will retrieve your IP address and that it might send it to third parties. Also, it's your responsability to notify and obtain consent from your website's users (by updating your Privacy Policy and Terms and Conditions). 

Please read [Google's Privacy Policy](https://policies.google.com/privacy/ "Google Privacy Policy") and if you agree with it, you can start using this plugin.

=== Theme developers ===

You can use the `sgf_defaults` filter to change the plugin defaults, example:

    add_filter( 'sgf_defaults', function( $defaults ) {
        // since v1.0.1
        $check = function_exists( 'sgf_get_font_id' );
    
        // Headings font family
        $hff = ! $check ? 0 : sgf_get_font_id( 'Josefin Sans' );
    
        // Body font family
        $bff = ! $check ? 0 : sgf_get_font_id( 'Muli' );
    
        // Headings
        $defaults[ 'headings' ][ 'ff' ] = $hff; // int  | Font Family
        
        // Body
        $defaults[ 'body' ][ 'ff' ] = $bff;     // int | Font Family
        $defaults[ 'body' ][ 'wt' ] = '400';    // string | Font Weight
        $defaults[ 'body' ][ 'lh' ] = 1.8;      // float | Line Height
        $defaults[ 'body' ][ 'ls' ] = 0;        // float | Letter spacing
        $defaults[ 'body' ][ 'ws' ] = 0;        // float | Word spacing
        
        // Returns new defaults
        return $defaults;
    }, 15 );

For headings you can use the `sgf_headings_defaults`, example:

    add_filter( 'sgf_headings_defaults', function( $defaults, $headings ) {
	    // $headings = [ 'h1', ... 'h6' ];

	    $defaults[ 'h1' ][ 'wt' ] = '400';  // string | H1 font weight, italic is added automatically.
	    $defaults[ 'h1' ][ 'tt' ] = 'none'; // string | H1 text transform
	    $defaults[ 'h1' ][ 'lh' ] = 1.8;    // float | Line height
	    $defaults[ 'h1' ][ 'ls' ] = 0;      // float | Letter spacing
        $defaults[ 'h1' ][ 'ws' ] = 0;      // float | Words spacing

	    // if a propriety is left out, it will use the plugin default.
	    // you can use this for headings from h1 to h6

	    return $defaults;
    }, 10, 2 );

For text logos you can use the `sgf_styles_frontend_txt_logo` filter to make sure it always takes on the Headings font family, example:

    add_filter( 'sgf_styles_frontend_txt_logo', function() { return '.logo-wrap'; }, 15 );

You can add these in your `functions.php` file. If you want to support old PHP versions, replace the anonymous functions with normal functions.

== Screenshots ==
1. You'll be able to access the plugin sidebar by clicking on the 3 dots and then on `Simple Google Fonts`
2. If you want to have a shortcut in the toolbar, click on the start to pin it.
3. Now you'll be able to access the plugin by clicking the `A` icon

== Changelog ==

= 1.0.2 =
Release Date: October 13th, 2018

* Changed: Using `wp_remote_get()` instead of `file_get_contents()`;
* Fixed: `Tabs` deprecated argument issue;
* Fixed: `theStyles()` defaults;
* Added: Filter for headings defaults; 
* Added: Word spacing option;

= 1.0.1 =
Release Date: October 10th, 2018

* Updated readme.txt file - tags & a simple GDPR notice;
* Fixed: missing `break;` in `inc/compatibility.php:L67`;
* Fixed: bug where Pages couldn't set globals;
* Added: `sgf_get_font_id()` function to get the font family id easier;
* Added: filter for `sgf_get_all_meta()`;

= 1.0.0 =
Release Date: October 5th, 2018

* Plugin release;