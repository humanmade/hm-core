<?php

require_once( 'hm-accounts.sso.classes.php' );
require_once( 'hm-accounts.sso.facebook.php' );
require_once( 'hm-accounts.sso.twitter.php' );

/**
 * Returns an array of all avatar options.
 * 
 * @return array
 */
function hma_get_avatar_options() {

	global $hma_sso_avatar_options;
	return $hma_sso_avatar_options->avatar_options;

}

/**
 * Returns an array of Avatar Option objects that a is authentication with or can use.
 * 
 * @return array
 */
function hma_get_user_avatar_options() {
	global $hma_sso_avatar_options;
	
	$array = $hma_sso_avatar_options->avatar_options;
	
	foreach( $array as $key => $value ) {
		if ( isset( $value->sso_provider ) && method_exists( $value->sso_provider, 'is_authenticated' ) && !$value->sso_provider->is_authenticated() )
			unset( $array[$key] );
	}
	
	return $array;
}

/**
 * Returns an Avatar Option object for a given avatar option id.
 * 
 * @param string $service_id
 * @return object | null on not found
 */
function hma_get_avatar_option( $service_id ) {
	
	foreach( hma_get_avatar_options() as $option )
		if ( $option->service_id == $service_id )
			return $option;
	
}

/**
 * Returns an array of SSO provider objects for all SSO provers.
 * 
 * @return array
 */
function hma_get_sso_providers() {
	
	global $hma_sso_providers;
	return (array) $hma_sso_providers;
	
}

/**
 * Gets an SSO provider object for a given SSO provider ID.
 * 
 * @param int $sso_provider_id
 * @return HMA_SSO_Provider|null on not found
 */
function hma_get_sso_provider( $sso_provider_id ) {
	
	foreach( hma_get_sso_providers() as $sso_provider )
		if ( $sso_provider->id == $sso_provider_id )
			return $sso_provider;
	
}

/**
 * Checks if a user logged in with a given SSO provider.
 * 
 * @param object $sso_provider. (default: hma_get_logged_in_sso_provider())
 * @return bool
 */
function hma_is_logged_in_with_sso_provider( $sso_provider = null ) {
	
	if ( $sso_provider ) {
		
		return $sso_provider == hma_get_logged_in_sso_provider();
		
	} else {
		
		return (bool) hma_get_logged_in_sso_provider();
		
	}
}

/**
 * Gets the SSO provider a user used to log in with (may be null).
 * 
 * @return SSO object | null
 */
function hma_get_logged_in_sso_provider() {

	foreach( hma_get_sso_providers() as $sso_provider ) {

		if ( $sso_provider->check_for_provider_logged_in() )
			return $sso_provider;
		}
}

/**
 * Returns an array of sso providers the suer has authenticated with.
 * 
 * @return array
 */
function hma_get_sso_providers_for_current_user() {
	
	return hma_get_sso_providers_for_user( get_current_user_id() );
}

function hma_get_sso_providers_for_user( $user_id ) {

	$user_providers = array();
	$user = get_userdata( $user_id );
	
	foreach( hma_get_sso_providers() as $sso_provider ) {

		$sso_provider->set_user( $user );
		if ( $sso_provider->is_authenticated() )
			$user_providers[] = $sso_provider;
	}
	
	return $user_providers;
}

/**
 * Returns an array of all the SSO providers a user has not authenticated with.
 * 
 * @return array
 */
function hma_get_sso_providers_not_authenticated_for_current_user() {

	$authenticated_providers = hma_get_sso_providers_for_current_user();
	$unauthenticated_providers = array();
	
	foreach( hma_get_sso_providers() as $p ) {
		if ( !in_array( $p, $authenticated_providers ) )
			$unauthenticated_providers[] = $p;
	}
	
	return $unauthenticated_providers;
}

/**
 * Loads the avatar options and sso classes.
 * 
 */
function hma_init_avatar_options() {
	
	//only show "Uploaded" if they have one
	new hma_Uploaded_Avatar_Option();
		
	new hma_Gravatar_Avatar_Option();
	
	try {
		new HMA_SSO_Facebook();
	} catch( Exception $e ) {
	
	}
	
	try {
		new hma_SSO_Twitter();
	} catch( Exception $e ) {
	
	}
}
add_action( 'init', 'hma_init_avatar_options', 1 );


global $hma_sso_avatar_options;
$hma_sso_avatar_options = new hma_SSO_Avatar_Options();

