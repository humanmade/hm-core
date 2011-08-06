<?php

include_once( 'hm-accounts.functions.php' );
include_once( 'hm-accounts.rewrite.php' );
include_once( 'hm-accounts.hooks.php' );
include_once( 'hm-accounts.actions.php' );
include_once( 'hm-accounts.bloginfo.php' );
include_once( 'hm-accounts.sso.php' );
include_once( 'hm-accounts.admin.edit-profile.php' );


add_action( 'init', 'hma_init' );
function hma_init() {
	//inlucde facebook if the plugin is activated
	if ( function_exists( 'fbc_get_fbconnect_user' ) ) {
		include_once( 'hm-accounts.sso.facebook.php' );  
	}
}
