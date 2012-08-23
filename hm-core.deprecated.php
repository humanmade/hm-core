<?php

/**
 * hm_parse_user function.
 *
 * @access public
 * @param mixed $user. (default: null)
 * @return void
 */
function hm_parse_user( $user = null ) {

	_deprecated_function( __FUNCTION__, '1.1', 'get_user_by()' );

	// We're we passed an object with ID
	if ( is_object( $user ) && is_numeric( $user->ID ) )
		return get_userdata( $user->ID );

	// We're we passed an object with user_id
	if ( is_object( $user ) && is_numeric( $user->user_id ) )
		return get_userdata( $user->user_id );

	// We're we passed an array
	if ( is_array( $user ) && is_numeric( $user['ID'] ) )
		return get_userdata( $user['ID'] );

	// ID
	if ( is_numeric( $user ) )
		return get_userdata( $user );

	// username
	if ( is_string( $user ) )
		return get_userdatabylogin( $user );

	// null
	global $current_user;

	return get_userdata( $current_user->ID );

}
