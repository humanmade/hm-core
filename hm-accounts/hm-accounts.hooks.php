<?php

/**
 * Check for form submissions
 *
 * @return null
 */
function hma_check_for_pages() {

	hma_check_for_submit( 'register' );
	hma_check_for_submit( 'sso_register' );
	hma_check_for_submit( 'login' );
	hma_check_for_submit( 'lost_password' );
	hma_check_for_submit( 'profile' );

}
add_action( 'init', 'hma_check_for_pages' );

/**
 * Output the lost password form fields
 *
 * @access public
 * @return null
 */
function hma_add_lost_password_inputs() {
	hma_add_form_fields( 'lost_password' );
	echo '<input type="hidden" name="referer" value="' . ( !empty( $_REQUEST['referer'] ) ? $_REQUEST['referer'] : wp_get_referer()) . '" />' . "\n";
}
add_action( 'hma_lost_password_form', 'hma_add_lost_password_inputs' );

/**
 * Output the edit profile form fields
 *
 * @return null
 */
function hma_add_profile_inputs() {
	hma_add_form_fields( 'profile' );
}
add_action( 'hma_profile_form', 'hma_add_profile_inputs' );

/**
 * Output the hidden form submissions tracking field and
 * optionally the wp_nonce
 *
 * @param string $page
 * @param bool $add_nonce. (default: true)
 * @return null
 */
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
 * Process the password reset form submission
 *
 * @return null
 */
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
add_action( 'init', 'hma_check_for_password_reset' );

/**
 * hma_replace_avatar function.
 *
 * @param string $avatar
 * @param mixed $id_or_email
 * @param int $size
 * @param string $default
 * @param string $alt. (default: null)
 * @todo email verification should use is_email
 * @return string
 */
function hma_replace_avatar( $avatar, $id_or_email, $size, $default, $alt = null ) {

	// If the default is supplied and an email - don't hook in (as the avatar is handled through wp-admin settings)
	if ( is_string( $id_or_email ) && strpos( $id_or_email, '@' ) > 0 && $default )
		return $avatar;

	$user = hma_parse_user( $id_or_email );

	if ( !$user )
		return $avatar;

	$src = hma_get_avatar( $user, $size, $size, true, false );

	if ( !$src )
		return $avatar;

	return '<img alt="' . $alt . '" src="' . $src . '" class="avatar avatar-' . $size . ' photo" height="' . $size . '" width="' . $size . '" />';

}
add_filter( 'get_avatar', 'hma_replace_avatar', 10, 5 );


/**
 * Add avatar select/upload fields to wordpress admin edit user.
 *
 * @access public
 * @param mixed $user
 * @return void
 */
function hma_admin_add_avatar( $user ) { ?>

	<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( '#hma_user_avatar' ).show();
			jQuery( 'form#your-profile' ).attr( 'enctype', 'multipart/form-data' );
		});
	</script>

	<style type="text/css">
		#hma_user_avatar { display: none; }
		#hma_user_avatar_select_row .hma_avatars { display: inline-block; width: 100px;  }
		#hma_user_avatar_select_row .hma_avatars input { margin-right: 5px; }
		#hma_user_avatar_file_row .avatar { display: block; float: left; margin-right: 20px;  }
		#hma_user_avatar_file_row #hma_user_avatar_file { display: inline-block; margin-bottom: 5px;  }
	</style>

	<div id="hma_user_avatar">

		<h3>User Avatar</h3>

		<table class="form-table">

			<?php $avatar_options = hma_get_avatar_options();
			$current_avatar_service = get_user_meta( $user->ID, 'user_avatar_option' );

		if ( $current_avatar_service ) :  ?>

			<tr id="hma_user_avatar_select_row">

				<th><label for="hma_user_avatar_file">Select which avatar is used</label></th>

	    		<td>
					<?php foreach ( $avatar_options as $avatar_option ) {

			    		$avatar_option->set_user( $user );

						if ( ! $avatar_option->get_avatar( 60 ) )
							continue; ?>

		    		<div class="hma_avatars">

		    			<img src="<?php echo $avatar_option->get_avatar( 60 ); ?>" height="60" width="60" alt="Avatar <?php echo $avatar_option->service_name; ?>" class="avatar" />

		    			<br/>

						<?php // TODO for attribute? ?>

		    			<label>

		    				<input type="radio" name="hma_user_avatar_service" value="<?php echo $avatar_option->service_id; ?>"

		    					<?php if ( ! empty( $current_avatar_service ) )
			    					checked( $avatar_option->service_id, $current_avatar_service );
			    				
			    				else
			    					checked( $avatar_option->service_id, 'gravatar' );  ?>
			    			/>

			    			<?php echo $avatar_option->service_name; ?>

		    			</label>

		    		</div>

	    		<?php } ?>

		    	</td>

			</tr>

		<?php else : ?>

			<tr id="hma_user_avatar_current_row">
				<th><label for="hma_user_avatar_file">Current Avatar</label></th>
	    		<td><?php echo get_avatar( $user->ID, 60 ); ?></td>
	    	</tr>

		<?php endif; ?>

			<tr id="hma_user_avatar_file_row">

				<th><label for="hma_user_avatar_file">Upload new avatar</label></th>

				<td>
					<input type="file" name="hma_user_avatar_file" id="hma_user_avatar_file"><br/>
					<span class="description">If you would like to upload a new avatar image. Otherwise leave this empty.</span>
				</td>

			</tr>

		</table>
	</div>

<?php }
add_action( 'show_user_profile', 'hma_admin_add_avatar' );
add_action( 'edit_user_profile', 'hma_admin_add_avatar' );

/**
 * Process the avatar select/upload fields on save/update.
 *
 * @param int $user_id
 * @return null
 */
function hma_admin_add_avatar_save( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) )
		return false;

	if ( isset( $_POST['hma_user_avatar_service'] )  )
		update_user_meta( $user_id, 'user_avatar_option', $_POST['hma_user_avatar_service'] );

	if ( isset( $_FILES['hma_user_avatar_file'] ) && $_FILES['hma_user_avatar_file'] != '' ) {

		$file = wp_handle_upload( $_FILES['hma_user_avatar_file'], array( 'test_form' => false ) );

		if ( ! isset( $file['file'] ) )
			return;

		update_user_meta( $user_id, 'user_avatar_path', str_replace( array( ABSPATH, '\\' ), array( ABSPATH, '/' ), $file['file'] ) );
		update_user_meta( $user_id, 'user_avatar_option', 'uploaded' );

	}

}
add_action( 'personal_options_update', 'hma_admin_add_avatar_save' );
add_action( 'edit_user_profile_update', 'hma_admin_add_avatar_save' );

/**
 * Adds some classes to body_class for account pages.
 *
 * @param array $classes
 * @return array
 */
function hma_body_class( $classes ) {

	if ( get_query_var( 'is_login' ) == '1' )
		$classes[] = 'login';

	if ( get_query_var( 'is_lost_password' ) == '1' ) {
		$classes[] = 'login';
		$classes[] = 'lost-password';
	}

	return $classes;

}
add_filter( 'body_class', 'hma_body_class' );

/**
 * Returns the login url
 *
 * @param string $login_url
 * @param string $redirect
 * @return string - new url
 */
function hma_login_url_hook( $login_url, $redirect ) {

	if ( ! file_exists( hma_get_login_template() ) )
		return $login_url;

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
	return hma_get_logout_url( $redirect );
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