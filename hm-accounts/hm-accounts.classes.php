<?php

class HM_Accounts {

	public $registration_data;
	public $id = 'manual';

	public static function get_instance( $id = 'manual' ) {

		$classes = array( 'maunal' => 'HM_Accounts', 'twitter' => 'HMA_SSO_Twitter', 'facebook' => 'HMA_SSO_Facebook' );
		$class = $classes[$id];

		return new $class();
	}

	public function __construct() {

	}

	public function set_registration_data( $data ) {

		$this->registration_data = $data;
	}

	public function register() {

		$user_data = $this->registration_data;

		$checks = array(
			'use_password' => false,
			'tos' => '',
			'use_tos' => true,
			'unique_email' => false,
			'do_login' => false,
			'send_email' => false
		);

		$defaults = array(
			'user_login' => '',
			'user_email' => '',
			'user_pass' => false,
			'role' => 'subscriber'
		);

		$original_args = $user_data;

		$default_args = array_merge( $defaults, $checks );

		$args = wp_parse_args( $user_data, $default_args );
		extract( $args, EXTR_SKIP );

		unset( $args['user_pass2'] );
		unset( $original_args['user_pass2'] );
		unset( $user_pass2 );

		if ( is_wp_error( $err = $this->validate_registration_data() ) )
			return $err;

		// Merge arrays overwritting defaults, remove any non-standard keys keys with empty values.
		$user_vars = array_filter( array( 'user_login' => $user_login, 'user_pass' => $user_pass, 'user_email' => $user_email, 'display_name' => $display_name ) );
		$user_id = wp_insert_user( $user_vars );

		if ( ! $user_id || is_wp_error( $user_id ) )
			return $user_id;

		// Setup the users role
		if ( $role ) {
			$user = new WP_User( $user_id );
			$user->set_role( $role );
		}

		// Get any remaining variable that were passed
		$meta_vars = array_diff_key( $original_args, $defaults, $checks, $user_vars );
		
		foreach ( (array) $meta_vars as $key => $value ) {

			if ( hma_is_profile_field( $key ) || ! hma_custom_profile_fields() ) {
				update_user_meta( $user_id, $key, $value );
			}
		}

		$user = get_userdata( $user_id );

		// Send Notifcation email if specified
		if ( $send_email )
			$email = hma_email_registration_success( $user, $user_pass );

		// If they chose a password, login them in
		if ( ( $use_password == 'true' || $do_login == true ) && !empty( $user->ID ) ) :

			wp_login( $user->user_login, $user_pass );

			wp_clearcookie();
			wp_setcookie($user->user_login, $user_pass, false);

			do_action( 'wp_login', $user->user_login );

			wp_set_current_user( $user->ID );

		endif;

		do_action( 'hma_registered_user', $user );

		return $user_id;
	}

	/**
	 * Validation the registration data
	 * 
	 * @return true on success, WP_error on fail
	 */
	public function validate_registration_data() {

		$args = $this->registration_data;

		// Unique username?
		// TODO could this not use username_exists?
		if ( !empty( $args['user_login'] ) && !empty( get_user_by( 'login', $args['user_login'] )->ID ) ) {
			return new WP_Error( 'username-exists', 'Sorry, the username: ' . $args['user_login'] . ' already exists.');
		}

		// Valid email?
		if ( !empty( $user->ID ) && !is_email( $args['user_email'] ) ) {
			return new WP_Error( 'invalid-email', 'Invalid email address.');
		}

		// Unique email?
		// TODO whats wrong with email_exists?
		if ( !empty( $args['unique_email'] ) && !empty( $args['user_email'] ) && get_user_by_email( $args['user_email'] ) ) {
			return new WP_Error( 'email-in-use', 'That email is already in use.');
		}

		// Passwords match
		if ( ! empty( $args['user_pass'] ) && ! empty( $args['user_pass2'] ) && $args['user_pass'] != $args['user_pass2'] ) {
			return new WP_Error( 'password-mismatch', 'The passwords you entered do not match.');
		}

		return true;
	}

	public function login() {

		$args = apply_filters( 'hma_log_user_in_args', $args );

		if ( empty( $args['username'] ) ) {
			return new WP_Error( 'no-username', 'Please enter your username' );
		}

		$user = hma_parse_user( $args['username'] );

		$defaults = array(
			'remember' => false,
			'allow_email_login' => true,
			'password_hashed' => false
		);

		// Strip any tags then may have been put into the array
		// TODO array_map?
		foreach( $args as $i => $a ) {
			if ( is_string( $a ) )
				$args[$i] = strip_tags( $a );
		}


		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		if ( !is_numeric( $user->ID ) ) {
			return new WP_Error( 'unrecognized-username', 'The username you entered was not recognized');
		}

		if ( $password_hashed != true ) {

			if ( !wp_check_password( $password, $user->user_pass ) ) {

				return new WP_Error('incorrect-password', 'The password you entered is incorrect');
			}

		} else {

			if ( $password != $user->user_pass ) {

				return new WP_Error('incorrect-password', 'The password you entered is incorrect');
			}

		}

		wp_set_auth_cookie( $user->ID, $remember );
		wp_set_current_user( $user->ID );

		do_action( 'wp_login', $user->user_login );
		do_action( 'hma_log_user_in', $user);

		return true;

	}

	public function get_register_submit_url() {
		return add_query_arg( 'type', $this->id, get_bloginfo( 'url' ) . '/register/submit/' );
	}
}

add_action( 'init', function() {

	hm_add_rewrite_rule( array(
		'rewrite' => '^register/submit/?$',
		'request_callback' => function() {

			$type = ! empty( $_GET['type'] ) ? $_GET['type']  : 'manual';

			$hm_accounts = HM_Accounts::get_instance( $type );

			$hm_accounts->set_registration_data( 
				apply_filters( 'hma_register_args', array(

					'user_login' 	=> $_POST['user_login'],
					'user_email'	=> $_POST['user_email'],
					'use_password' 	=> true,
					'user_pass'		=> $_POST['user_pass'],
					'user_pass2'	=> $_POST['user_pass_1'],
					'unique_email'	=> true,
					'send_email'	=> true,
					'override_nonce'=> true
				) )
			);

			$hm_return = $hm_accounts->register();

			if ( is_wp_error( $hm_return ) ) {

				do_action( 'hma_register_submitted_error', $hm_return );
				hm_error_message( $hm_return->get_error_message() ? $hm_return->get_error_message() : 'Something went wrong, error code: ' . $hm_return->get_error_code(), 'register' );
				wp_redirect( wp_get_referer() );
				exit;

			} else {

				do_action( 'hma_register_completed', $hm_return );

				if ( $_POST['redirect_to'] )
					$redirect = $_POST['redirect_to'];

				elseif ( $_POST['referer'] )
					$redirect = $_POST['referer'];

				else
					$redirect = get_bloginfo( 'edit_profile_url', 'display' );

				wp_redirect( $redirect );
				exit;
			}
		}
	) );
} );