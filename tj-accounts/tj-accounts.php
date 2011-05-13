<?php

include_once( 'tj-accounts.functions.php' );
include_once( 'tj-accounts.rewrite.php' );
include_once( 'tj-accounts.hooks.php' );
include_once( 'tj-accounts.actions.php' );
include_once( 'tj-accounts.bloginfo.php' );
include_once( 'tj-accounts.sso.php' );

add_action( 'init', 'tja_init' );
function tja_init() {
	//inlucde facebook if the plugin is activated
	if( function_exists( 'fbc_get_fbconnect_user' ) ) {
		include_once( 'tj-accounts.facebook.php' );  
	}
}
