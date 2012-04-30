<?php

/**
 * Redirect the user after they login
 *
 * @param mixed $return
 * @return null
 */
function hma_do_login_redirect( $return, $do_redirect_on_error = false ) {

	if ( is_wp_error( $return ) ) {

		do_action( 'hma_login_submitted_error', $return );
		
		if ( $do_redirect_on_error ) {
			wp_redirect( wp_get_referer(), 303 );
			exit;
		}
		
		return;

	} else {

		if ( ! empty( $_REQUEST['redirect_to'] ) )
			$redirect = urldecode( $_REQUEST['redirect_to'] );

		elseif ( ! empty( $_POST['referer'] ) ) //success
			$redirect = $_POST['referer'];

		else
			$redirect = get_bloginfo('url');

		do_action( 'hma_login_submitted_success', $redirect );

		$redirect = apply_filters( 'hma_login_redirect', $redirect, $user );

		// we have to use header: location as wp_redirect messes up arrays in GET params
		header( 'Location: ' . hm_parse_redirect( $redirect ), true, 303 );
		exit;
	}

}

/**
 * Parse the redirect string and replace _user_login_ with
 * the users login.
 *
 * @param string $redirect
 * @return string
 */
function hm_parse_redirect( $redirect ) {

	if ( is_user_logged_in() )
		$redirect = str_replace( '_user_login_', wp_get_current_user()->user_login, $redirect );

	return apply_filters( 'hm_parse_login_redirect',  $redirect );

}

/**
 * Log the user out
 *
 * @return null
 */
function hma_logout() {

	if ( isset( $_GET['action'] ) && $_GET['action'] == 'logout' ) :

		// logout of a sso provider
		if ( hma_is_logged_in_with_sso_provider() )
			$sso_provider = hma_get_logged_in_sso_provider();

		// Fire the WordPress logout
		wp_logout();

		if ( ! empty( $_GET['redirect_to'] ) ) {
		    $redirect = $_GET['redirect_to'];

		} else {
		    $redirect = remove_query_arg( 'action', wp_get_referer() );

		    // Redirect to homepage if logged out from wp-admin
		    if ( strpos( $redirect, '/wp-admin' ) )
		    	$redirect = get_bloginfo( 'url' );

		}

		if ( isset( $sso_provider ) )
			$sso_provider->logout( $redirect );

		wp_redirect( $redirect );
		exit;

	endif;

}
add_action( 'init', 'hma_logout', 9 );

/**
 * Catch the lost password form
 *
 * @return null
 */
function hma_lost_password_submitted() {

	$success = hma_lost_password( $_POST['user_email'] );

	if ( is_wp_error( $success ) ) {

		do_action( 'hma_lost_password_submitted_error', $success );

		return;

	} else {

		do_action( 'hma_lost_password_submitted_success', $success );

		if ( isset( $_REQUEST['login_source'] ) && $_REQUEST['login_source'] == 'popup' )
			wp_redirect( get_bloginfo( 'lost_password_inline_url', 'display' ) . '?message=' . $success['text'] );

		else
			wp_redirect( get_bloginfo( 'lost_password_url', 'display' ) . '?message=' . $success['text'] );

		exit;

	}
}
add_action( 'hma_lost_password_submitted', 'hma_lost_password_submitted' );

/**
 * Process the edit profile form submission
 *
 * @return null
 */
function hma_profile_submitted() {

	check_admin_referer( 'hma_profile_submitted' );

	$current_user = wp_get_current_user();

	// check the user is logged in
	if ( !is_user_logged_in() )
		return;

	// Loop through all data and only user user_* fields or fields which have been registered using hma_register_profile_field
	foreach( $_POST as $key => $value ) {

		if ( ( ! hma_is_profile_field( $key ) && hma_custom_profile_fields() ) || ( ! hma_custom_profile_fields() && strpos( $key, 'user_' ) !== 0 ) )
			continue;

		$user_data[$key] = is_string( $value ) ? esc_attr( $value ) : array_map( 'esc_attr', $value );

	}

	// Check that the passwords match if they were $_POST'd
	if ( ! empty( $_POST['user_pass'] ) && isset( $_POST['user_pass2'] ) && ( $_POST['user_pass'] !== $_POST['user_pass2'] ) ) {
		hm_error_message( 'The passwords you entered do not match', 'update-user' );
		return;
	}
	
	if ( ! empty( $_POST['user_pass'] ) )
		$user_data['user_pass'] = esc_attr( $_POST['user_pass'] );
	
	else
		unset( $user_data['user_pass'] );

	if ( ! empty( $_POST['user_email'] ) )
		$user_data['user_email'] = esc_attr( $_POST['user_email'] );

	$user_data['ID'] = $current_user->ID;

	if ( isset( $_POST['first_name'] ) )
		$user_data['first_name'] = esc_attr( $_POST['first_name'] );

	if ( isset( $_POST['last_name'] ) )
		$user_data['last_name'] = esc_attr( $_POST['last_name'] );

	if ( isset( $_POST['nickname'] ) )
		$user_data['nickname'] = esc_attr( $_POST['nickname'] );

	$user_data['user_login'] = $current_user->user_login;

	if ( isset( $_POST['description'] ) )
		$user_data['description'] = esc_attr( $_POST['description'] );

	if ( isset( $_POST['display_name'] ) ) {

		$name = trim( $_POST['display_name'] );
		$match = preg_match_all( '/([\S^\,]*)/', esc_attr( $_POST['display_name'] ), $matches );

		foreach( array_filter( (array) $matches[0] ) as $match )
			$name = trim( str_replace( $match, $user_data[$match], $name ) );

		$user_data['display_name'] = $name;
		$user_data['display_name_preference'] = esc_attr( $_POST['display_name'] );

	}

	if ( !empty( $_FILES['user_avatar']['name'] ) )
		$user_data['user_avatar'] = $_FILES['user_avatar'];

	$success = hma_update_user_info( $user_data );

	// Unlink any SSO providers
	if ( !is_wp_error( $success ) && !empty( $_POST['unlink_sso_providers'] ) && array_filter( (array) $_POST['unlink_sso_providers'] ) ) {

		if ( empty( $user_data['user_pass'] ) ) {
			hm_error_message( 'The social network(s) could not be unlinked because you did not enter your password', 'update-user' );

		} else {

			foreach( array_filter( (array) $_POST['unlink_sso_providers'] ) as $sso_provider_id ) {

				$sso_provider = hma_get_sso_provider( $sso_provider_id );
				$sso_provider->unlink();

			}
		}
	}

	if ( is_wp_error( $success ) ) {
		
		do_action( 'hma_update_user_profile_error', $success );
		return;

	} else {

	if ( $_POST['redirect_to'] )
	    $redirect = esc_attr( $_POST['redirect_to'] );

	elseif ( $_POST['referer'] )
	    $redirect = esc_attr( $_POST['referer'] );

	elseif ( wp_get_referer() )
	    $redirect = wp_get_referer();

	else
	    $redirect = get_bloginfo( 'edit_profile_url', 'display' );

	do_action( 'hma_update_user_profile_completed', $redirect );

		wp_redirect( $redirect, 303 /* 303 means redirect for form submission - remove this comment */ );
	exit;

	}

}
add_action( 'hma_profile_submitted', 'hma_profile_submitted' );
