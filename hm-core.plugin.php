<?php

/*
Plugin Name: HM Core
Description: A set of helpful frameworks, functions, classes.
Version: 1.3
Author: Human Made Limited
Author URI: http://hmn.md/
*/

// Don't load if there is another version loaded
// TODO we shouldn't even activate
if ( defined( 'HM_CORE_PATH' ) || defined( 'HELPERPATH' ) )
	return;

define( 'HM_CORE_SLUG', 'hm-core' );
define( 'HM_CORE_PATH', dirname( __FILE__ ) . '/' );
define( 'HM_CORE_URL', str_replace( ABSPATH, site_url( '/' ), HM_CORE_PATH ) );

// Load core files
include_once( HM_CORE_PATH . 'hm-core.deprecated.php' );
include_once( HM_CORE_PATH . 'hm-core.functions.php' );
include_once( HM_CORE_PATH . 'hm-core.termmeta.php' );
include_once( HM_CORE_PATH . 'hm-core.messages.php' );
include_once( HM_CORE_PATH . 'hm-core.classes.php' );
include_once( HM_CORE_PATH . 'hm-core.wp-query-additions.php' );

// Load the custom media button support unless it's specifically disabled
if ( ! defined( 'HM_ENABLE_MEDIA_UPLOAD_EXTENSIONS' ) || HM_ENABLE_MEDIA_UPLOAD_EXTENSIONS )
    include_once( HM_CORE_PATH . 'media-uploader.extensions.php' );

// Load the js functions unless specifically disabled
if ( ! defined( 'HM_ENABLE_SCRIPTS' ) || HM_ENABLE_SCRIPTS ) {

    // Include the js script shortcode
    function hm_core_add_scripts( $scripts ) {
    	$scripts->add( 'hm-core', str_replace( ABSPATH, '/', HM_CORE_PATH ) . 'scripts/hm-core.functions.js', array( 'jquery' ), '1' );
    }
    add_action( 'wp_default_scripts', 'hm_core_add_scripts', 20 );

}

// New style of loading stuff based off theme supports
function hm_theme_supports() {

	// Related posts function
	if ( current_theme_supports( 'hm-related-posts' ) ) {
		include_once( HM_CORE_PATH . 'hm-core.related-posts.php' );

	} else {

		if ( ! function_exists( 'hm_get_related_posts' ) ) {

			// We create a mock function to alert client code that theme supports is needed for this
			function hm_get_related_posts() {
				throw new Exception( 'hm_related_posts is not available, you must add theme supports for "hm-related-posts"' );
			}

		}

	}

}
add_action( 'after_setup_theme', 'hm_theme_supports', 11 );

function hm_core_load_textdomain() {

	/** The 'plugin_locale' filter is also used by default in load_plugin_textdomain() */
	$locale = apply_filters( 'plugin_locale', get_locale(), 'hm-core' );

	/** Set filter for WordPress languages directory */
	$hm_core_wp_lang_dir = apply_filters(
		'hm_core_wp_lang_dir',
		trailingslashit( WP_LANG_DIR ) . 'hm-core/hm-core-' . $locale . '.mo'
	);

	/** Translations: First, look in WordPress' "languages" folder = custom & update-secure! */
	load_textdomain( 'hm-core', $hm_core_wp_lang_dir );

	/** Translations: Secondly, look in plugin's "languages" folder = default */
	load_muplugin_textdomain( 'hm-core', '/hm-core/languages' );
}
add_action( 'init', 'hm_core_load_textdomain' );