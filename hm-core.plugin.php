<?php

/*
Plugin Name: HM Core
Description: A set of helpful frameworks, functions, classes.
Version: 1.0
Author: Human Made Limited
Author URI: http://hmn.md/
*/

// TODO We should use theme_supports for the loading

// Don't load if there is another version loaded
// TODO we shouldn't even activate
if ( defined( 'HM_CORE_PATH' ) || defined( 'HELPERPATH' ) )
	return;

define( 'HM_CORE_SLUG', 'hm-core' );
define( 'HM_CORE_PATH', dirname( __FILE__ ) . '/' );
define( 'HM_CORE_URL', str_replace( ABSPATH, site_url( '/' ), HM_CORE_PATH ) );

// Load core files
include_once( HM_CORE_PATH . 'hm-core.debug.php' );
include_once( HM_CORE_PATH . 'hm-core.functions.php' );
include_once( HM_CORE_PATH . 'hm-core.rewrite.php' );
include_once( HM_CORE_PATH . 'hm-core.messages.php' );
include_once( HM_CORE_PATH . 'hm-core.classes.php' );
include_once( HM_CORE_PATH . 'hm-core.hm-cron.php' );

// Load Custom Metaboxes and Fields for WordPress
function hm_initialize_cmb_meta_boxes() {
	include_once( HM_CORE_PATH . '/Custom-Metaboxes-and-Fields-for-WordPress/init.php' );
}
add_action( 'init', 'hm_initialize_cmb_meta_boxes', 9999 );

// Load the custom media button support unless it's specifically disabled
if ( ! defined( 'HM_ENABLE_MEDIA_UPLOAD_EXTENSIONS' ) || HM_ENABLE_MEDIA_UPLOAD_EXTENSIONS )
    include_once( HM_CORE_PATH . 'media-uploader.extensions.php' );

// Load WPThumb unless it's specifically disabled or already included elsewhere
if ( ( ! defined( 'HM_ENABLE_PHPTHUMB' ) || HM_ENABLE_PHPTHUMB ) && ! function_exists( 'wpthumb' ) )
    include_once( HM_CORE_PATH . 'WPThumb/wpthumb.php' );

// Only load HM Accounts if it's enabled
if ( defined( 'HM_ENABLE_ACCOUNTS' ) && HM_ENABLE_ACCOUNTS !== false )
    include_once( HM_CORE_PATH . 'hm-accounts/hm-accounts.php' );

// Load the Paypal class if enabled
if ( defined( 'HM_ENABLE_PAYPAL' ) && HM_ENABLE_PAYPAL !== false )
    include_once( HM_CORE_PATH . 'paypal/paypal.functions.php' );

// Load the js functions unless specifically disabled
if ( ! defined( 'HM_ENABLE_SCRIPTS' ) || HM_ENABLE_SCRIPTS ) :

    // Include the js script shortcode
    function hm_core_add_scripts( $scripts ) {
    	$scripts->add( 'hm-core', str_replace( ABSPATH, '/', HM_CORE_PATH ) . 'scripts/hm-core.functions.js', array( 'jquery' ), '1' );
    }
    add_action( 'wp_default_scripts', 'hm_core_add_scripts', 20 );

endif;

/**
 * Deactivate conflicting plugins
 * 
 * @return null
 */
function hm_deactivate_conflicts() {

	$plugins = get_option( 'active_plugins' );
	$plugin_deactivate = array_keys( $plugins, 'WPThumb/wpthumb.php' );

	if ( isset( $plugin_deactivate[0] ) )
		unset( $plugins[$plugin_deactivate[0]] );

	update_option( 'active_plugins', $plugins );
}

if ( ( ! defined( 'HM_ENABLE_PHPTHUMB' ) || HM_ENABLE_PHPTHUMB ) && ! function_exists( 'wpthumb' ) )
	add_action( 'init', 'hm_deactivate_conflicts' );

// New style of loading stuff based off theme supports
add_action( 'plugins_loaded', function() {
	
	// Related posts function
	if ( current_theme_supports( 'hm-related-posts' ) ) {
		include_once( HM_CORE_PATH . 'hm-core.related-posts.php' );
	
	} else {
		
		// We create a mock function to alert client code that theme supports is needed for this
		function hm_get_related_posts() {
			throw new Exception( 'hm_related_posts is not available, you must add theme supports for "hm-related-posts"' );
		}
	
	}
}, 9 );
