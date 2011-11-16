<?php

/**
 * Catch the submitted login form and log the user in.
 *
 * @return null
 */
function hma_login_submitted() {

	$return = hma_log_user_in( array( 'username' => $_POST['user_login'], 'password' => $_POST['user_pass'], 'remember' => ( !empty( $_POST['remember'] ) ? true : false ) ) );

	hma_do_login_redirect( $return );

}
add_action( 'hma_login_submitted', 'hma_login_submitted' );

/**
 * Redirect the user after they login
 *
 * @param mixed $return
 * @return null
 */
function hma_do_login_redirect( $return ) {

	if ( is_wp_error( $return ) ) {

		do_action( 'hma_login_submitted_error', $return );

		if ( isset( $_REQUEST['login_source'] ) && $_REQUEST['login_source'] == 'popup' )
			$redirect = add_query_arg( 'message', $return->get_error_code(), get_bloginfo( 'login_inline_url', 'display' ) );

		else
			$redirect = add_query_arg( 'message', $return->get_error_code(), get_bloginfo( 'login_url', 'display' ) );

		if ( !empty( $_REQUEST['redirect_to'] ) )
			add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $redirect );

		if ( $_REQUEST['referer'] )
			$redirect = add_query_arg( 'referer', $_REQUEST['referer'], $redirect );

		elseif ( wp_get_referer() )
			$redirect = add_query_arg( 'referer', wp_get_referer(), $redirect );

		wp_redirect( hm_parse_redirect( $redirect ), 303 );
		exit;

	} else {

		if ( $_REQUEST['redirect_to'] )
			$redirect = urldecode( $_REQUEST['redirect_to'] );

		elseif ( $_POST['referer'] ) //success
			$redirect = $_POST['referer'];

		else
			$redirect = get_bloginfo('url');

		do_action( 'hma_login_submitted_success', $redirect );

		wp_redirect( hm_parse_redirect( $redirect ), 303 );
		exit;
	}

}

/**
 * Check that the user who logged in with SSO
 *
 * @todo same as hma_login_in_user_from_sso_providers()?
 * @return bool
 */
function hma_check_for_sso_providers_logged_in() {

	foreach ( hma_get_sso_providers() as $sso_provider )
		if ( $sso_provider->check_for_provider_logged_in() )
			if ( $sso_provider->perform_wordpress_login_from_provider() )
				return true;

}

/**
 * hma_check_for_sso_providers_registered function.
 *
 * @access public
 * @return null
 */
function hma_check_for_sso_providers_registered() {

	foreach( hma_get_sso_providers() as $sso_provider )
		if ( $sso_provider->check_for_provider_logged_in() )
			return $sso_provider;

}

/**
 * hma_login_in_user_from_sso_providers function.
 *
 * @access public
 * @return null | bool
 */
function hma_login_in_user_from_sso_providers() {

	if ( is_user_logged_in() )
		return null;

	foreach ( hma_get_sso_providers() as $sso_provider )
		if ( $sso_provider->check_for_provider_logged_in() )
			if ( $sso_provider->perform_wordpress_login_from_provider() )
				return true;

}
//add_action( 'init', 'hma_login_in_user_from_sso_providers' );

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

		if ( $_GET['redirect_to'] ) {
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

		if ( isset( $_REQUEST['login_source'] ) && $_REQUEST['login_source'] == 'popup' )
			wp_redirect( get_bloginfo( 'lost_password_inline_url', 'display' ) . '?message=' . $success->get_error_code() );

		else
			wp_redirect( get_bloginfo( 'lost_password_url', 'display' ) . '?message=' . $success->get_error_code() );

		exit;

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
 * Process the SSO form submission
 *
 * @return null
 */
function hma_sso_register_submitted() {

	$registered_with_sso_provider = hma_check_for_sso_providers_registered();

	if ( !$registered_with_sso_provider )
		return;

	$result = $registered_with_sso_provider->register_sso_submitted();

	if ( ( !$result ) || is_wp_error( $result ) ) {

		add_action( 'hma_sso_register_form', array( &$registered_with_sso_provider, 'register_form_fields' ) );
	    do_action( 'hma_sso_provider_register_submitted_with_erroneous_details', &$registered_with_sso_provider, $result );

	    if ( isset( $_REQUEST['register_source'] ) && $_REQUEST['register_source'] == 'popup' )
		    wp_redirect( get_bloginfo( 'register_inline_url', 'display' ) . '?message=' );

	    else
		    wp_redirect( get_bloginfo( 'register_url', 'display' ) . '?message=' );

	    exit;

	} else {

		do_action( 'hma_sso_register_completed', &$registered_with_sso_provider, $result );

	    if ( $_POST['redirect_to'] )
	    	$redirect = $_POST['redirect_to'];

	    elseif ( $_POST['referer'] )
	    	$redirect = $_POST['referer'];

	    elseif ( wp_get_referer() )
	    	$redirect = wp_get_referer();

	    else
	    	$redirect = get_bloginfo('edit_profile_url', 'display');

	    wp_redirect( $redirect );
	    exit;

	}

}
add_action( 'hma_sso_register_submitted', 'hma_sso_register_submitted' );

/**
 * Process the registration form submission
 *
 * @return null
 */
function hma_register_submitted() {

	$hm_return = hma_new_user( array(
	    'user_login' 	=> $_POST['user_login'],
	    'user_email'	=> $_POST['user_email'],
	    'use_password' 	=> true,
	    'user_pass'		=> $_POST['user_pass'],
	    'user_pass2'	=> $_POST['user_pass_1'],
	    'use_tos'		=> false,
	    'unique_email'	=> true,
	    'do_redirect'	=> false,
	    'send_email'	=> true,
	    'override_nonce'=> true
	) );

	if ( is_wp_error( $hm_return ) ) {

		if ( isset( $_REQUEST['register_source'] ) && $_REQUEST['register_source'] == 'popup' )
		    wp_redirect( get_bloginfo( 'register_inline_url', 'display' ) . '?message=' . $hm_return->get_error_code() );

	    else
		    wp_redirect( get_bloginfo( 'register_url', 'display' ) . '?message=' . $hm_return->get_error_code() );

	    exit;

	} else {

		do_action( 'hma_register_completed', $hm_return );

		if ( $_POST['redirect_to'] )
			$redirect = $_POST['redirect_to'];

		elseif ( $_POST['referer'] )
			$redirect = $_POST['referer'];

		elseif ( wp_get_referer() )
			$redirect = wp_get_referer();

		else
			$redirect = get_bloginfo( 'edit_profile_url', 'display' );

		wp_redirect( $redirect );
		exit;
	}

}
add_action( 'hma_register_submitted', 'hma_register_submitted' );

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

		if ( strpos( $key, 'user_' ) !== 0 && ! hma_is_profile_field( $key ) )
			continue;

		$user_data[$key] = is_string( $value ) ? esc_attr( $value ) : array_map( 'esc_attr', $value );

	}

	// Check that the passwords match is they were $_POST'd
	if ( !empty( $user_data['user_pass'] ) && !empty( $user_data['user_pass2'] ) && ( $user_data['user_pass'] != $user_data['user_pass2'] ) ) {
		hm_error_message( 'The passwords you entered do not match', 'update-user' );
		return;
	}
	
	// Unset user_pass2
	if ( $user_data['user_pass'] && $user_data['user_pass2'] && ( $user_data['user_pass'] === $user_data['user_pass2'] ) )
		unset( $user_data['user_pass2'] );

	$user_data['ID'] = $current_user->ID;

	if ( !empty( $_POST['first_name'] ) )
		$user_data['first_name'] = esc_attr( $_POST['first_name'] );

	if ( !empty( $_POST['last_name'] ) )
		$user_data['last_name'] = esc_attr( $_POST['last_name'] );

	if ( !empty( $_POST['nickname'] ) )
		$user_data['nickname'] = esc_attr( $_POST['nickname'] );

	if ( $current_user->user_login )
		$user_data['user_login'] = $current_user->user_login;

	if ( !empty( $_POST['description'] ) )
		$user_data['description'] = esc_attr( $_POST['description'] );

	if ( !empty( $_POST['display_name'] ) ) {

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

	if ( $_POST['redirect_to'] )
	    $redirect = esc_attr( $_POST['redirect_to'] );

	elseif ( $_POST['referer'] )
	    $redirect = esc_attr( $_POST['referer'] );

	elseif ( wp_get_referer() )
	    $redirect = wp_get_referer();

	else
	    $redirect = get_bloginfo( 'edit_profile_url', 'display' );

	do_action( 'hma_update_user_profile_completed', $redirect );

	wp_redirect( add_query_arg( 'message', is_wp_error( $success ) ? $success->get_error_code() : '1', $redirect ) );
	exit;
}
add_action( 'hma_profile_submitted', 'hma_profile_submitted' );