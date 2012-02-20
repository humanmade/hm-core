<?php


/**
 * hma_SSO_Avatar_Option class.
 * Used as an abstract class, must be subclassed
 * 
 */
class HMA_SSO_Avatar_Option {

	public $service_name;
	public $service_id;
	public $avatar_url;
	public $user;
	
	function __construct() {
		global $hma_sso_avatar_options;
		$hma_sso_avatar_options->register_avatar_option( &$this );
		$this->user = wp_get_current_user();
	}
	
	function set_user( $user ) {
		$this->avatar_path = null;
		$this->avatar_url = null;
		$this->user = $user;
	}
	
	function get_avatar( $size = null ) {
		return $this->avatar_url;
	}
	
	function save_avatar_locally( $url, $ext = null ) {
	
		$upload_dir = wp_upload_dir();
		$upload_dir_base = $upload_dir['basedir'];
		$avatar_dir = $upload_dir_base . '/avatars';
			
		if ( !is_dir($avatar_dir) )
			mkdir( $avatar_dir, 0775, true );
		
		if ( !$ext )
			$ext = strtolower( end( explode( '.', $url ) ) );
		
		$image_path = $avatar_dir . '/' . $this->user->ID . '-' . $this->service_id . '.' . $ext;
		
		// Remove old one if was there
		if ( file_exists( $image_path ) )
			unlink( $image_path );
		
		file_put_contents( $image_path, file_get_contents( $url ) );
		
		//check that the image saved ok, if not then remove it and return null
		if ( !getimagesize( $image_path ) ) {
			unlink( $image_path );
			$image_path = null;
		}
		
		return $image_path;
	}
	
	function remove_local_avatar() {
		
		if ( !is_user_logged_in() || empty( $this->avatar_path ) )
			return null;
		
		unlink( $this->avatar_path );
		
	}
}

class HMA_Uploaded_Avatar_Option extends hma_SSO_Avatar_Option {

	function __construct() {
		
		parent::__construct();
		$this->service_name = "Uploaded";
		$this->service_id = "uploaded";		
	}
	
	function get_avatar( $size ) {
		
		if ( ! hma_get_avatar_upload_path( $this->user ) )
			return null;
		
		return wpthumb( hma_get_avatar_upload_path( $this->user ), $size );
	}
}


class HMA_Gravatar_Avatar_Option extends HMA_SSO_Avatar_Option {

	function __construct() {
		
		parent::__construct();
		$this->service_name = "Gravatar";
		$this->service_id = "gravatar";
	}
	
	function get_avatar( $size ) {
		$size = wp_parse_args( $size );
		return $this->avatar_url = add_query_arg( 's', $size['width'], 'http://www.gravatar.com/avatar/' . md5( strtolower( $this->user->user_email ) ) );
	}
}


/**
 * Absstract class for new SSO provider (e.g. Facebook, Twitter etc)
 * 
 */
class HMA_SSO_Provider {
	
	public $id;
	public $name;
	public $supports_publishing;
	public $user_info;
	
	function __construct() {
		
		global $hma_sso_providers;
		$hma_sso_providers[] = &$this;

		add_action( 'hm_parse_request_^login/sso/authenticated/?$', array( &$this, '_check_sso_login_submitted' ) );
		add_action( 'hm_parse_request_^register/sso/authenticated/?$', array( &$this, '_check_sso_register_submitted' ) );
		add_action( 'hm_parse_request_^profile/sso/authenticated/?$', array( &$this, '_check_sso_connect_with_account_submitted' ) );
		add_action( 'hm_parse_request_^profile/sso/deauthenticate/?$', array( &$this, '_check_sso_unlink_from_account_submitted' ) );

	}
	
	function set_user( $user ) {
		$this->user = $user;
		$this->user_info = null;
		$this->access_token = $this->get_access_token();
	}
	
	function get_unlink_from_account_url() {
		
		if ( !is_user_logged_in() )
			return null;
		
		return wp_nonce_url( add_query_arg( 'id', $this->id, get_bloginfo( 'edit_profile_url', 'display' ) . 'sso/deauthenticate/' ), 'sso_unlink_from_account_' . $this->id );
	}
	
	function get_access_token_string() {
		return $this->access_token;
	}

	/**
	 * Creates a new user based off the SSO provider details (must be check_for_provider_logged_in() = true)
	 * If the provider credentials have already been used, just log them in
	 *
	 * @return bool
	 */
	public function register() {
	
		if ( !$this->check_for_provider_logged_in() )
			return null;
		
		// Check if the SSO has already been registered with a WP account, if so then login them in and be done
		if ( $result = $this->login() ) {
			wp_redirect( get_bloginfo('edit_profile_url', 'display') );
			exit;
		}
		
	}
	
	//is functions
	
	/**
	 * Checks if the user has linked their account with the SSO provider.
	 * 
	 * @return bool
	 */
	function is_authenticated() {}
	
	function has_avatar_option() {
		
		return !empty( $this->avatar_option );
	}

	function login_link_submitted() {
		$return = $this->login();

		// If ther account was not connected, and we have register on login enabled, do that
		if( is_wp_error( $return ) && in_array( $return->get_error_code(), array( 'twitter-account-not-connected', 'facebook-account-not-connected' ) ) && defined( 'HMA_SSO_REGISTER_ACCOUNT_ON_LOGIN' ) && HMA_SSO_REGISTER_ACCOUNT_ON_LOGIN ) {

			hm_clear_messages( 'login' );
			$return = $this->register();
			hm_clear_messages( 'register' );
		}
		
		do_action( 'hma_sso_login_attempt_completed', &$this, $return );
		
		hma_do_login_redirect( $return );
	}
	
	function register_link_submitted() {
	
		$return = $this->register();
		
		do_action( 'hma_sso_register_attempt_completed', &$this, $return );
		
		if ( is_wp_error( $return ) )
			wp_redirect( wp_get_referer(), 303 );
		
		else
			wp_redirect( get_bloginfo( 'edit_profile_url', 'display' ), 303 );
		
		exit;
	}
	
	function _check_sso_login_submitted() {
		
		if ( isset( $_GET['id'] ) && $_GET['id'] == $this->id )
			$this->login_link_submitted();
		
	}
	
	function _check_sso_register_submitted() {
	
		if ( isset( $_GET['id'] ) && $_GET['id'] == $this->id )
			$this->register_link_submitted();
		
		
	}
	
	function _check_sso_connect_with_account_submitted() {
	
		if ( isset( $_GET['id'] ) && $_GET['id'] == $this->id ) {
			$result = $this->link();
			do_action( 'hma_sso_connect_with_account_completed', &$this, $result );
			
			wp_redirect( get_bloginfo( 'edit_profile_url', 'display' ), 303 );
			exit;
		}
	}
	
	function _check_sso_unlink_from_account_submitted() {
		
		if ( isset( $_GET['id'] ) && $_GET['id'] == $this->id && wp_verify_nonce( $_GET['_wpnonce'], 'sso_unlink_from_account_' . $this->id ) ) {
			$result = $this->unlink();
			do_action( 'hma_sso_unlink_from_account_completed', &$this, $result );
			wp_redirect( get_bloginfo( 'edit_profile_url', 'display' ), 303 );
			exit;
		}
	}
	
	function _get_sso_login_submit_url() {
		$url = add_query_arg( 'id', $this->id, get_bloginfo( 'login_url', 'display' ) . 'sso/authenticated/' );
		$url = add_query_arg( $_GET, $url );
		
		return $url;
	}
	
	function get_connect_with_account_submit_url() {
		return add_query_arg( 'id', $this->id, get_bloginfo( 'edit_profile_url', 'display' ) . 'sso/authenticated/' );
	}
	
	function _get_sso_register_submit_url() {
		
		$url = add_query_arg( 'id', $this->id, get_bloginfo( 'register_url', 'display' ) . 'sso/authenticated/' );
		$url = add_query_arg( $_GET, $url );
		
		return $url;		
	}
	
	function _get_provider_authentication_completed_register_redirect_url() {
		return html_entity_decode( wp_nonce_url( add_query_arg( 'sso_registrar_authorized', $this->id, get_bloginfo( 'register_url', 'display' ) ), 'sso_registrar_authorized_' . $this->id ) );
	}
	
	
	//Puishing
	function can_publish() {	
		return $this->supports_publishing && $this->user && $this->is_authenticated();
	}
	
	
	function log( $var ) {
		error_log( $var );
	}
	
}

/**
 * hma_SSO_Avatar_Options class.
 * 
 */
class HMA_SSO_Avatar_Options {
	
	public $avatar_options;
	
	function __construct() {
		$this->avatar_options = array();
	}
	
	function register_avatar_option( $HMA_SSO_Avatar ) {
		$this->avatar_options[] = $HMA_SSO_Avatar;
	}

}