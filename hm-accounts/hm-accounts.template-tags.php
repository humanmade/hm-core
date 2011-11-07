<?php

function hma_get_displayed_user_id() {
	return get_query_var( 'author' ) ? get_query_var( 'author' ) : 0;
}

function hma_get_displayed_user_avatar( $width, $height, $crop = true ) {
	return hma_get_avatar( hma_get_displayed_user_id(), $width, $height, $crop );
}

function hma_displayed_user_avatar( $width, $height, $crop = true ) { ?>
	
	<img src="<?php echo hma_get_displayed_user_avatar( $width, $height, $crop ); ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" class="avatar" />

<?php }

function hma_get_displayed_user_url() {
	return hma_get_user_url( hma_get_displayed_user_id() );
}

function hma_displayed_user_url() {
	echo hma_get_displayed_user_url();
}

function hma_get_displayed_user_display_name() {
	return get_the_author_meta( 'display_name', hma_get_displayed_user_id() );
}

function hma_displayed_user_display_name() {
	echo hma_get_displayed_user_display_name();
}

function hma_get_displayed_user_login() {
	return get_the_author_meta( 'user_login', hma_get_displayed_user_id() );
}

function hma_displayed_user_login() {
	echo hma_get_displayed_user_login();
}

/**
 * Get the current profile user
 *
 * @access public
 * @return null
 */
function hma_get_profile_user() {

	if ( !hma_is_user_profile() )
		return null;

	return get_user_by( 'slug', get_query_var( 'author_name' ) );
}

/**
 * Returns the login page url.
 *
 * @param string $redirect. (default: null) - where to redirect to after login is successful
 * @param string $message - message to show on the login page
 * @return string
 */
function hma_get_login_url( $redirect = null, $message = null ) {

	$url = get_bloginfo( 'login_url', 'display' );

	if ( $redirect )
		$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );

	if ( $message )
		$url = add_query_arg( 'login_message', urlencode( $message ), $url );

	return esc_url( $url );
}

/**
 * Returns the login page url.
 *
 * @param string $redirect. (default: null) - where to redirect to after login is successful
 * @param string $message - message to show on the login page
 * @return string
 */
function hma_get_logout_url( $redirect = null ) {

	$url = add_query_arg( 'action', 'logout', hma_get_login_url() );

	if ( $redirect )
		$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );

	return $url;
}

/**
 * Return the lost password page url
 *
 * @return string
 */
function hma_get_lost_password_url() {
	return hma_get_login_url() . 'lost-password/';
}

/**
 * Return the register page url
 *
 * @access public
 * @return null
 */
function hma_get_register_url() {
	return trailingslashit( get_bloginfo( 'url' ) ) . 'register/';
}

/**
 * Check whether we are on the login page
 *
 * @return bool
 */
function hma_is_login() {
	global $wp_the_query;
	return !empty( $wp_the_query->is_login );
}

/**
 * Check whether we are on the register page
 *
 * @return bool
 */
function hma_is_register() {
	global $wp_the_query;
	return !empty( $wp_the_query->is_register );
}

/**
 * Check whether we are on the lost password page
 *
 * @return bool
 */
function hma_is_lost_password() {
	global $wp_the_query;
	return !empty( $wp_the_query->is_lost_password );
}

/**
 * Check whether we are on the edit profile page
 *
 * @return bool
 */
function hma_is_edit_profile() {
	global $wp_the_query;
	return !empty( $wp_the_query->is_edit_profile );
}

/**
 * Check whether we are on the profile page
 *
 * If you pass $user_id then check if we are on that users profile page
 *
 * @param int $user_id [optional]
 * @return bool
 */
function hma_is_user_profile( $user_id = null ) {

	global $wp_the_query;

	if ( !empty( $wp_the_query->is_user_profile ) && !empty( $user_id ) && $user_id == get_query_var( 'author' ) )
		return true;

	elseif ( empty( $user_id ) )
		return !empty( $wp_the_query->is_user_profile );
		
	return false;

}

function hma_is_current_user_profile() {
	
	if ( hma_is_user_profile( get_current_user_id() ) )
		return true;
		
	return false;
	
}

/**
 * Checks if a given user is a facebook user
 *
 * @param object $user
 * @return bool
 */
function hma_is_facebook_user( $user ) {
	return (bool) $user->fbuid;
}

/**
 * Return the users profile url
 *
 * @todo the user profile permalink structure should be filterable
 * @param object $authordata. (default: null)
 * @return string
 */
function hma_get_user_url( $authordata = null ) {

	if ( !$authordata )
		global $authordata;

	$authordata = hma_parse_user( $authordata );

	return get_bloginfo( 'url' ) . '/users/' . $authordata->user_nicename . '/';
}