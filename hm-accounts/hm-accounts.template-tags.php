<?php

function hma_get_displayed_user_id() {
	return get_query_var( 'author' ) ? get_query_var( 'author' ) : 0;
}

function hma_get_displayed_user() {
	return get_userdata( hma_get_displayed_user_id() );
}

function hma_get_displayed_user_avatar( $width, $height, $crop = true ) {
	return hma_get_avatar( hma_get_displayed_user_id(), $width, $height, $crop );
}

function hma_get_current_user_avatar( $width, $height, $crop = true ) {
	return hma_get_avatar( get_current_user_id(), $width, $height, $crop );
}

function hma_current_user_avatar( $width, $height, $crop = true ) { ?>
	<img src="<?php echo hma_get_current_user_avatar( $width, $height, $crop ); ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" class="avatar" />
<?php }

function hma_displayed_user_avatar( $width, $height, $crop = true ) { ?>
	<img src="<?php echo hma_get_displayed_user_avatar( $width, $height, $crop ); ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" class="avatar" />
<?php }

function hma_user_avatar( $user_id, $width, $height, $crop = true ) { ?>
	<img src="<?php echo hma_get_avatar( $user_id, $width, $height, $crop ); ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" class="avatar" />
<?php }

function hma_get_displayed_user_url() {
	return hma_get_user_url( hma_get_displayed_user_id() );
}

function hma_get_current_user_url() {
	return hma_get_user_url( get_current_user_id() );
}

function hma_current_user_url() {
	echo hma_get_current_user_url();
}

function hma_displayed_user_url() {
	echo hma_get_displayed_user_url();
}

function hma_get_displayed_user_login() {
	return get_the_author_meta( 'user_login', hma_get_displayed_user_id() );
}

function hma_displayed_user_login() {
	echo hma_get_displayed_user_login();
}

function hma_get_displayed_user_profile_field_data( $field ) {
	return hma_get_profile_field_data( hma_get_displayed_user_id(), $field );
}

function hma_get_current_user_profile_field_data( $field ) {
	return hma_get_profile_field_data( get_current_user_id(), $field );
}

function hma_displayed_user_profile_field_data( $field ) {
	echo hma_get_displayed_user_profile_field_data( $field );
}

function hma_current_user_profile_field_data( $field ) {
	echo hma_get_current_user_profile_field_data( $field );
}

function hma_get_user_link( $user_id ) {
	return '<a href="' . hma_get_user_url( $user_id ) . '">' . hma_get_user_name( $user_id ) . '</a>';
}

function hma_user_link( $user_id ) {
	echo hma_get_user_link( $user_id );
}

function hma_current_user_link() {
	echo hma_get_user_link( get_current_user_id() );
}

function hma_displayed_user_link() {
	echo hma_get_user_link( hma_get_displayed_user_id() );
}

function hma_get_user_name( $user_id ) {
	return apply_filters( 'hma_get_user_name', get_the_author_meta( 'display_name', $user_id ), $user_id );
}

function hma_user_name( $user_id ) {
	echo hma_get_user_name( $user_id );
}

function hma_get_displayed_user_name() {
	return hma_get_user_name( hma_get_displayed_user_id() );
}

function hma_displayed_user_name() {
	echo hma_get_displayed_user_name();
}

function hma_current_user_name() {
	echo hma_get_current_user_name();
}

function hma_get_current_user_name() {
	return hma_get_user_name( get_current_user_id() );
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

	$url = get_bloginfo( 'logout_url', 'display' );

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
	return get_bloginfo( 'lost_password_url', 'display' );
}

/**
 * Return the register page url
 *
 * @access public
 * @return null
 */
function hma_get_register_url() {
	return get_bloginfo( 'register_url', 'display' );
}

/**
 * Check whether we are on the login page
 *
 * @return bool
 */
function hma_is_login() {
	return hma_is_( 'is_login' );
}

/**
 * Check whether we are on the register page
 *
 * @return bool
 */
function hma_is_register() {
	return hma_is_( 'is_register' );
}

/**
 * Check whether we are on the lost password page
 *
 * @return bool
 */
function hma_is_lost_password() {
	return hma_is_( 'is_lost_password' );
}

/**
 * Check whether we are on the edit profile page
 *
 * @return bool
 */
function hma_is_edit_profile() {
	return hma_is_( 'is_edit_profile' );
}

function hma_is_( $query_arg ) {

	global $wp_the_query;

	return ! empty( $wp_the_query->$query_arg );

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

	if ( hma_is_( 'is_user_profile' ) && ! empty( $user_id ) && $user_id == get_query_var( 'author' ) )
		return true;

	elseif ( empty( $user_id ) )
		return hma_is_( 'is_user_profile' );
		
	return false;

}

/**
 * Check if we're on the current users profile page
 * 
 * @access public
 * @return bool
 */
function hma_is_current_user_profile() {
	
	if ( hma_is_user_profile( get_current_user_id() ) )
		return true;
		
	return false;
	
}

function hma_is_user_author( $user_id ) {
	
	if ( ! empty( $user_id ) && $user_id == get_query_var( 'author' ) )
		return true;
		
	return false;
	
}

function hma_is_current_user_author() {
	
	return hma_is_user_author( get_current_user_id() );
	
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
 * @todo refactor out $authordata and hm_parse_user();
 * @param object $authordata. (default: null)
 * @return string
 */
function hma_get_user_url( $authordata = null ) {

	if ( ! $authordata )
		global $authordata;

	$authordata = hma_parse_user( $authordata );

	return apply_filters( 'hma_user_url', home_url( hma_get_user_profile_rewrite_slug() . '/' . $authordata->user_nicename . '/' ) );
}