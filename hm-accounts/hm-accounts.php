<?php

include_once( 'hm-accounts.functions.php' );
include_once( 'hm-accounts.message.functions.php' );
include_once( 'hm-accounts.template-tags.php' );
include_once( 'hm-accounts.rewrite.php' );
include_once( 'hm-accounts.hooks.php' );
include_once( 'hm-accounts.actions.php' );
include_once( 'hm-accounts.bloginfo.php' );
include_once( 'hm-accounts.sso.php' );
include_once( 'hm-accounts.admin.edit-profile.php' );

/**
 * Setup HM Accounts
 *
 * @access public
 * @return null
 */
function hma_init() {

	if ( function_exists( 'fbc_get_fbconnect_user' ) )
		include_once( 'hm-accounts.sso.facebook.php' );

}
add_action( 'init', 'hma_init' );

function hma_default_profile_fields() {
	
	hma_register_profile_field( 'user_avatar_path' );
	hma_register_profile_field( 'user_avatar_option' );
	
}
add_action( 'init', 'hma_default_profile_fields', 9 );