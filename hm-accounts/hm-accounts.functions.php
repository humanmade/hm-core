<?php

/**
 * Send the new user notification email on register success
 *
 * The email can easily be overriden by including an email.register.php file in your theme
 *
 * @param object $user
 * @param string $user_pass
 * @todo the email template file should be filterable
 * @return bool
 */
function hma_email_registration_success( $user, $user_pass ) {

	if ( file_exists( $file = apply_filters( 'hma_email_registration_success_email_template', get_stylesheet_directory() . '/email.register.php' ) ) ) {

		ob_start();
		include( $file );
		$message = ob_get_contents();
		ob_end_clean();
		
		add_filter( 'wp_mail_content_type', 'wp_mail_content_type_html' );
	
	} else {

		wp_new_user_notification( $user->ID, $user_pass );
		return;

	}
	
	add_filter( 'wp_mail_from', 'hm_wp_mail_from' );
	add_filter( 'wp_mail_from_name', 'hm_wp_mail_from_name'  );
	
	return wp_mail( $user->user_email, apply_filters( 'hma_register_email_subject', 'New account registered for ' . get_bloginfo() ), $message, 'content-type=text/html' );

}

/**
 * Send the lost password email
 *
 * @param string $email
 * @return string on success, WP_Error on failure
 */
function hma_lost_password( $email ) {

	if ( !get_user_by_email( $email ) && !get_userdatabylogin( $email ) ) {
		hm_error_message( apply_filters( 'hma_login_unrocognized_email_error_message', 'The email address you entered was not recognised'), 'lost-password' );
		return new WP_Error('unrecognized-email');
	}

	$_POST['user_email'] = $email;
	$_POST['user_login'] = $email;

	// Grab the retrieve password function from wp-login.php
	ob_start();
	include_once( trailingslashit(ABSPATH) . 'wp-login.php' );
	ob_end_clean();

	add_filter( 'retrieve_password_message', 'hma_lost_password_email', 10, 2 );
	add_filter( 'wp_mail_from', 'hm_wp_mail_from' );
	add_filter( 'wp_mail_from_name', 'hm_wp_mail_from_name'  );

	$errors = retrieve_password();

	if ( !is_wp_error( $errors ) ) {
		hm_success_message( 'You have been sent an email with a link to reset your password', 'lost-password' );
		return array( 'status' => 'success', 'text' => 'success' );
	}

	hm_error_message( 'There was an unknown error', 'lost-password' );

	return new WP_Error('unknown');
}

/**
 * Load the lost password email template
 *
 * @param string $message
 * @param string $key
 * @return string, the email contents
 */
function hma_lost_password_email( $message, $key ) {

	$user = get_user_by_email(trim($_POST['user_login']));
	$reset_url = get_bloginfo( 'lost_password_url', 'display' ) . '?action=rp&key=' . $key . '&login=' . $user->user_login;

	// TODO this template path should be filterable
	if ( file_exists( $file = get_stylesheet_directory() . '/email.lost-password.php' ) ) {
		ob_start();
		include( $file );
		$message = ob_get_contents();
		ob_end_clean();
		
		add_filter( 'wp_mail_content_type', 'wp_mail_content_type_html' );
	}

	return $message;

}

/**
 * Send the user their new password
 *
 * @param string $user_login
 * @param string $key
 * @return array on success, WP_Error on failure
 */
function hma_reset_password( $user_login, $key ) {

	add_filter( 'password_reset_message', 'hma_reset_password_email', 10, 2 );
	add_filter( 'wp_mail_from', 'hm_wp_mail_from' );
	add_filter( 'wp_mail_from_name', 'hm_wp_mail_from_name'  );

	$status = hma_override_reset_password( $key, $user_login );

	if ( !is_wp_error( $status ) ) {
		hm_success_message( 'You have been sent an email with your new randomly generated password', 'lost-password' );
		return array( 'status' => 'success', 'code' => 303 );
	}

	hm_error_message( 'The request to reset your password was not successful.', 'lost-password' );

	return $status;

}

/**
 * Load the reset password email template
 *
 * @param mixed $message
 * @param mixed $new_pass
 * @return null
 */
function hma_reset_password_email( $message, $new_pass ) {

	$user = get_userdatabylogin( $_GET['login'] );

	// TODO template path should be filterable
	if ( file_exists( $file = get_stylesheet_directory() . '/email.reset-password.php' ) ) {
		ob_start();
		include( $file );
		$message = ob_get_contents();
		ob_end_clean();
		
		add_filter( 'wp_mail_content_type', 'wp_mail_content_type_html' );
	}

	return $message;

}

/**
 * Update a users information
 *
 * Can take a variety of arguments all in the form of a userinfo array.
 *
 * You can pass any of the default WordPress user fields, you can also pass
 * an avatar to upload or an image url to use as an avatar.
 * You can also pass any amount of additonal fields which will be added to the
 * 'profile_info' user meta.
 *
 * This function does not do any stripping or sanitizing, all that should be done before the data gets here.
 *
 * @param: (array) of user information
 * @return: (mixed) user_id on success, WP_Error on failure
 **/
function hma_update_user_info( $info ) {

	// If an email was passed, check that it is valid
	if ( !empty( $info['user_email'] ) && !is_email( $info['user_email'] ) ) {
		hm_error_message( 'Please enter a valid email address', 'update-user' );
		return new WP_Error( 'invalid-email', 'Please enter a valid email address' );
	}

	// If an ID wasn't passed then use the current user
	if ( empty( $info['ID'] ) )
		$info['ID'] = get_current_user_id();

	if ( empty( $info['ID'] ) ) {
		hm_error_message( 'Invalid user.', 'update-user' );
		return new WP_Error( 'invalid-user', 'Empty user ID' );
	}

	// Prepare the array for wp_update_user
	$userdata['ID'] = $info['ID'];

	if ( isset( $info['user_email'] ) )
		$userdata['user_email'] = $info['user_email'];

	if ( isset( $info['display_name'] ) )
		$userdata['display_name'] = $info['display_name'];

	if ( isset( $info['first_name'] ) )
		$userdata['first_name'] = $info['first_name'];

	if ( isset( $info['last_name'] ) )
		$userdata['last_name'] = $info['last_name'];

	if ( isset( $info['nickname'] ) )
		$userdata['nickname'] = $info['nickname'];

	if ( isset( $info['description'] ) )
		$userdata['description'] = $info['description'];

	if ( isset( $info['user_pass'] ) )
		$userdata['user_pass'] = $info['user_pass'];

	if ( isset( $info['user_url'] ) )
		$userdata['user_url'] = $info['user_url'];

	$user_id = wp_update_user( $userdata );

	// User avatar
	if ( !empty( $info['user_avatar'] ) ) {

		require_once( ABSPATH . 'wp-admin/includes/admin.php' );

		$file = wp_handle_upload( $info['user_avatar'], array( 'test_form' => false ) );
		$info['user_avatar_path'] = str_replace( ABSPATH, '', $file['file'] );
		$info['user_avatar_option'] = 'uploaded';
		unset( $info['user_avatar'] );

	}

	$meta_info = array_diff_key( $info, $userdata );

	// Unset some important fields
	unset( $meta_info['user_pass'] );
	unset( $meta_info['user_pass2'] );
	unset( $meta_info['user_login'] );

	// Anything left gets added to user meta as separate fields
	if ( !empty( $meta_info ) )
		foreach( (array) $meta_info as $key => $value )
				update_user_meta( $user_id, $key, $value );

	if ( $user_id )
		hm_success_message( 'Information successfully updated', 'update-user' );

	return $user_id;
}

/**
 * Loads a user using any piece of userdata as input
 *
 * @todo this would be better as hma_get_user_by()
 * @param mixed $user. (default: null)
 * @return void
 */
function hma_parse_user( $user = null ) {

	if ( is_object( $user ) && isset( $user->ID ) && is_numeric( $user->ID ) )
		return get_userdata( $user->ID );

	if ( is_object( $user ) && isset( $user->user_id ) && is_numeric( $user->user_id ) )
		return get_userdata( $user->user_id );

	if ( is_array( $user ) && isset( $user['ID'] ) && is_numeric( $user['ID'] ) )
		return get_userdata( $user['ID'] );

	if ( is_numeric( $user ) )
		return get_userdata( $user );

	if ( is_string( $user ) ) {

		if ( strpos( $user, "@" ) > 0 && $user = get_user_by_email( $user ) )
			return $user;

		return get_userdatabylogin( $user );
	}

	if ( is_null( $user ) )
		return wp_get_current_user();
}

/**
 * Return the users avatar
 *
 * @param object $user
 * @param int $width
 * @param int $height
 * @param bool $crop. (default: true)
 * @param bool $try_normal. (default: true)
 * @return string
 */
function hma_get_avatar( $user = null, $width, $height, $crop = true, $try_normal = true ) {

	$user = hma_parse_user( $user );

	// Try to use avatar option classes
	if ( !empty( $user->user_avatar_option ) ) {

		$hma_avatar_option = hma_get_avatar_option( $user->user_avatar_option );
		$hma_avatar_option->set_user( $user );
		
		if ( is_a( $hma_avatar_option, 'hma_SSO_Avatar_Option' ) ) {

			$avatar = $hma_avatar_option->get_avatar( "width=$width&height=$height&crop=$crop" );

			if ( $avatar )
				return $avatar;

		}

	}

	if ( $avatar = hma_get_avatar_upload( $user, $width, $height, $crop ) ) {
		return $avatar;

	} elseif ( $avatar = apply_filters( 'hma_get_avatar_fallback', null, $user, $width, $height, $crop ) ) {
		return $avatar;

	} elseif ( $try_normal === true ) {

		preg_match( '/src=\'([^\']*)/', get_avatar( $user->user_email, $width ), $matches );
		return $matches[1];

	}

}

/**
 * hma_get_avatar_upload function.
 *
 * @param object $user
 * @param int $width
 * @param int $height
 * @param bool $crop
 * @return string
 */
function hma_get_avatar_upload( $user, $width, $height, $crop ) {

	if ( $path = hma_get_avatar_upload_path( $user ) )
		return wpthumb( $path, $width, $height, $crop );
		
	return '';

}

function hma_get_avatar_upload_path( $user ) {
	
	if ( empty( $user->user_avatar_path ) )
		return '';
	
	//windows hackery
	if ( strpos( $user->user_avatar_path, untrailingslashit( ABSPATH ) ) === 0 )
		return $user->user_avatar_path;
	
	return ABSPATH . $user->user_avatar_path;
}

/**
 * Handles resetting the user's password.
 *
 * @uses $wpdb WordPress Database object
 *
 * @param string $key Hash to validate sending user's password
 * @return bool|WP_Error
 */
function hma_override_reset_password($key, $login) {
	global $wpdb;

	$key = preg_replace('/[^a-z0-9]/i', '', $key);

	if ( empty( $key ) || !is_string( $key ) ) {
		hm_error_message( 'The key you provided was invalid', 'lost-password' );
		return new WP_Error('invalid_key', __('Invalid key'));
	}

	if ( empty($login) || !is_string($login) ){
		hm_error_message( 'The key you provided was invalid', 'lost-password' );
		return new WP_Error('invalid_key', __('Invalid key'));
	}

	$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));
	if ( empty( $user ) ){
		hm_error_message( 'The key you provided was invalid', 'lost-password' );
		return new WP_Error('invalid_key', __('Invalid key'));
	}

	// Generate something random for a password...
	$new_pass = wp_generate_password();

	do_action('password_reset', $user, $new_pass);

	wp_set_password($new_pass, $user->ID);
	update_user_meta($user->ID, 'default_password_nag', true); //Set up the Password change nag.
	$message  = sprintf(__('Username: %s'), $user->user_login) . "\r\n";
	$message .= sprintf(__('Password: %s'), $new_pass) . "\r\n";
	$message .= site_url('wp-login.php', 'login') . "\r\n";

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$title = sprintf(__('[%s] Your new password'), $blogname);

	$title = apply_filters('password_reset_title', $title);
	$message = apply_filters('password_reset_message', $message, $new_pass);

	if ( $message && !wp_mail($user->user_email, $title, $message) )
  		die('<p>' . __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function...') . '</p>');

	wp_password_change_notification($user);

	return true;
}

/**
 * Will make a username unique if it already exists.
 *
 * @param string $base_name
 * @return string
 */
function hma_unique_username( $base_name ) {

	if ( !username_exists( $base_name ) )
		return $base_name;

	$counter = 1;
	$new_name = $base_name . $counter;

	while( username_exists( $new_name ) ) {
		$counter++;
		$new_name = $base_name . $counter;
	}

	return $new_name;

}

/**
 * Register a new profile field
 *
 * @return null
 */
function hma_register_profile_field( $field ) {

	global $hma_profile_fields;

	if ( empty( $hma_profile_fields ) )
		$hma_profile_fields = array();

	$hma_profile_fields[] = $field;

}
add_action( 'init', 'hma_register_profile_field', 11 );

/**
 * Get the array of extra profile fields
 *
 * @return null
 */
function hma_get_profile_fields() {

	global $hma_profile_fields;

	return $hma_profile_fields;

}

function hma_custom_profile_fields() {
	
	return array_diff( hma_get_profile_fields(), hma_default_profile_fields() );
	
}

/**
 * Check if a field is registered
 *
 * @access public
 * @param mixed $field
 * @return null
 */
function hma_is_profile_field( $field ) {

	global $hma_profile_fields;

	return in_array( $field, (array) $hma_profile_fields );

}

/**
 * Wrapper for getting a profile field
 * checks the user object first then
 * checks usermeta
 *
 * @param int $user_id
 * @param string $field
 * @return string field value
 */
function hma_get_profile_field_data( $user_id, $field ) {

	if ( $meta = get_the_author_meta( $field, $user_id ) )
		return $meta;

	return get_user_meta( $user_id, $field, true );
}