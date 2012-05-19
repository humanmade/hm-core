<?php

include_once( 'hm-accounts.classes.php' );
include_once( 'hm-accounts.functions.php' );
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

	foreach( hma_default_profile_fields() as $field )
		hma_register_profile_field( $field );

}
add_action( 'init', 'hma_init', 9 );

function hma_default_profile_fields() {
	return array( 
		'user_avatar_path', 
		'user_avatar_option',
		'first_name',
		'last_name',
		'description',
		'display_name_preference',
		'url',
		'location',
		'gender'
	);
}