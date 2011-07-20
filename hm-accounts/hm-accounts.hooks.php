<?php
add_action( 'init', 'hma_check_for_pages' );
function hma_check_for_pages() {
	hma_check_for_submit('register');
	hma_check_for_submit('sso_register');
	hma_check_for_submit('login');
	hma_check_for_submit('lost_password');
	hma_check_for_submit('profile');
}

add_action( 'hma_register_form', 'hma_add_register_inputs' );
function hma_add_register_inputs() {
	hma_add_form_fields( 'register', false );
	echo '<input type="hidden" name="referer" value="' . ( !empty( $_REQUEST['referer'] ) ? $_REQUEST['referer'] : wp_get_referer()) . '" />' . "\n";
}

add_action( 'hma_sso_register_form', 'hma_add_sso_register_inputs' );
function hma_add_sso_register_inputs() {
	hma_add_form_fields( 'sso_register', false );
	echo '<input type="hidden" name="referer" value="' . ( !empty( $_REQUEST['referer'] ) ? $_REQUEST['referer'] : wp_get_referer()) . '" />' . "\n";
}

add_action( 'hma_login_form', 'hma_add_login_inputs' );
function hma_add_login_inputs() {
	hma_add_form_fields( 'login', false );
	
	if ( !empty( $_REQUEST['redirect_to'] ) )
		echo '<input type="hidden" name="redirect_to" value="' . ($_REQUEST['redirect_to'] ) . '" />' . "\n"; 
		
	echo '<input type="hidden" name="referer" value="' . ( !empty( $_REQUEST['referer'] ) ? $_REQUEST['referer'] : wp_get_referer()) . '" />' . "\n"; 
}

add_action( 'hma_lost_password_form', 'hma_add_lost_password_inputs' );
function hma_add_lost_password_inputs() {
	hma_add_form_fields( 'lost_password' );
	echo '<input type="hidden" name="referer" value="' . ( !empty( $_REQUEST['referer'] ) ? $_REQUEST['referer'] : wp_get_referer()) . '" />' . "\n"; 
}

add_action( 'hma_profile_form', 'hma_add_profile_inputs' );
function hma_add_profile_inputs() {
	hma_add_form_fields( 'profile' );
}

function hma_add_form_fields( $page, $add_nonce = true ) {
	echo '<input type="hidden" name="hma_' . $page . '_submitted" value="' . $page . '" />' . "\n";
	
	if ( $add_nonce )
		wp_nonce_field( 'hma_' . $page . '_submitted' );
}

/**
 * Checks POST data for a given page name
 * 
 * @param string $page name
 */
function hma_check_for_submit( $page ) {
	if ( empty( $_POST['hma_' . $page . '_submitted'] ) )
		return;
	do_action( 'hma_' . $page . '_submitted' );
}

/**
 * Checks GET data for a password reset
 * 
 */
add_action( 'init', 'hma_check_for_password_reset' );
function hma_check_for_password_reset() {
	if ( isset( $_GET['action'] ) && $_GET['action'] == 'rp' && !empty( $_GET['key'] ) && !empty( $_GET['login'] ) ) {
		$status = hma_reset_password(  $_GET['login'], $_GET['key'] );
		if ( !is_wp_error( $status ) ) {
			do_action( 'hma_lost_password_reset_success' );
			wp_redirect( add_query_arg( 'message', '303', get_bloginfo('lost_password_url', 'display') ) );
		} else {
			do_action( 'hma_lost_password_reset_error', $status );
			wp_redirect( add_query_arg( 'message', $status->get_error_code(), get_bloginfo('lost_password_url', 'display') ) );
		}
		exit;
	}
}

//avatar

function hma_replace_avatar( $avatar, $id_or_email, $size, $default, $alt = null ) {
	
	//If the default is supplied and an email - dont hook in (as it is the avatars options on the admin page)
	if ( is_string( $id_or_email ) && strpos( $id_or_email, '@' ) > 0 && $default )
		return $avatar;
		
	$user = hma_parse_user( $id_or_email );
	
	if ( !$user ) return $avatar;
	$src = hma_get_avatar( $user, $size, $size, true, false );

	if ( !$src ) return $avatar;
	return "<img alt='{$alt}' src='{$src}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
}
add_filter( 'get_avatar', 'hma_replace_avatar', 10, 5 );

/**
 * Adds some classes to body_class for account pages.
 * 
 * @param array $classes
 * @return array
 */
function hma_body_class( $classes ) {
	
	if ( get_query_var( 'is_login' ) == '1' ) {
		$classes[] = 'login';
	}
	
	if ( get_query_var( 'is_lost_password' ) == '1' ) {
		$classes[] = 'login';
		$classes[] = 'lost-password';
	}
	
	return $classes;
	
}
add_filter( 'body_class', 'hma_body_class' );

/**
 * Returns the logout url 
 *
 * @param string $login_url
 * @param string $redirect
 * @return string - new url
 */
function hma_login_url_hook( $login_url, $redirect ) {
	return hma_get_login_url( $redirect );
}
add_filter('login_url', 'hma_login_url_hook', 10, 2 );

/**
 * Returns the logout url
 *
 * @param string $logout_url
 * @param string $redirect
 * @return string - new url
 */
function hma_logout_url_hook( $logout_url, $redirect ) {
	$url = hma_get_logout_url( $redirect );

	return $url;
}
add_filter('logout_url', 'hma_logout_url_hook', 10, 2 );

/**
 * Override the author url with our own user urls.
 * 
 * @param string $link
 * @param int $user_id
 * @return string
 */
function hma_get_author_link( $link, $user_id ) {
	return hma_get_user_url( $user_id );
}
add_filter( 'author_link', 'hma_get_author_link', 10, 2 );
