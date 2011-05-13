<?php
/*
Plugin Name: humanmade Nuclear Core
Plugin URI: http://humanmade.co.uk/
Description: A set of helpful frameworks, functions, classes.
Version: 0.1
Author: humanmade limited, Tom Willmot, Joe Hoyle, Matthew Haines-Young
Author URI: http://humanmade.co.uk/
*/

if ( !defined( 'HELPERPATH' ) ) :

	define( 'HELPERPATH', dirname( __FILE__ ) . '/' );
	define( 'HELPERURL', str_replace( ABSPATH, trailingslashit( get_bloginfo('wpurl') ), dirname( __FILE__ ) ) . '/' );

	// Load the helper functions
	include_once( HELPERPATH . 'hm_core.functions.php' );

	if( !defined( 'HM_ENABLE_MEDIA_UPLOAD_EXTENSIONS' ) || HM_ENABLE_MEDIA_UPLOAD_EXTENSIONS == true ) {
	// Load the Custom Media Buttons
	include_once( HELPERPATH . 'media-uploader.extensions.php' );
	}

	if( !defined( 'HM_ENABLE_PHPTHUMB' ) || HM_ENABLE_PHPTHUMB == true ) {
	// Load phpThumb
	include_once( HELPERPATH . 'phpthumb.php' );
	}

	// Load the Accounts module
	if ( defined( 'HM_ENABLE_ACCOUNTS' ) && HM_ENABLE_ACCOUNTS !== false )
		include_once( HELPERPATH . 'tj-accounts/tj-accounts.php' );

	// Load the custom rewrite rules
	include_once( HELPERPATH . 'template-rewrite.php' );

	// Load the Payapl class
	if ( defined( 'HM_ENABLE_PAYPAL' ) && HM_ENABLE_PAYPAL !== false )
		include_once( HELPERPATH . 'paypal/paypal.functions.php' );

	// Include the js script shortcode
	function helper_add_scripts( $scripts ) {
		$rel = str_replace( ABSPATH, '/', HELPERPATH );
		$scripts->add( 'helper', $rel . 'scripts/helper.functions.js', array( 'jquery' ), '1' );
	}
	
	if( !defined( 'HM_ENABLE_SCRIPTS' ) || HM_ENABLE_SCRIPTS == true ) {
	add_action( 'wp_default_scripts', 'helper_add_scripts', 20 );
	}

endif;
