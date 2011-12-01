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


class hma_Gravatar_Avatar_Option extends hma_SSO_Avatar_Option {

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
		add_action( 'wp_footer', array( &$this, '_run_logged_out_js' ) );
		
		add_action( 'init', array( &$this, '_check_for_oauth_register_completed' ) );
		add_action( 'init', array( &$this, '_check_wordpress_login_and_connect_provider_with_account_submitted' ) );

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

	function get_user_info() {
	
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
	function perform_wordpress_register_from_provider() {
	
		if ( !$this->check_for_provider_logged_in() )
			return null;
		
		// Check if the SSO has already been registered with a WP account, if so then login them in and be done
		if ( $result = $this->perform_wordpress_login_from_provider() ) {
			wp_redirect( get_bloginfo('edit_profile_url', 'display') );
			exit;
		}
		
	}
	
	/**
	 * Logs a user in based off a login form and connects their sso provider (which was just authencated) with it.
	 * 
	 * @return true | wp_error
	 */
	function perform_wordpress_login_from_site_and_connect_provider_with_account() {
				
		$wp_login_details = array( 'username' => $_POST['user_login'], 'password' => $_POST['user_pass'], 'remember' => ( !empty( $_POST['remember'] ) ? true : false ) );
		
		$login_status = hma_log_user_in( $wp_login_details );
		
		if ( is_wp_error( $login_status ) || !$login_status ) {
			return $login_status;
		}
		
		$connect_status = $this->provider_authentication_connect_with_account_completed();
		
		if ( is_wp_error( $connect_status ) ) {
			//connect failed, log them out
			wp_logout();
			
			return $connect_status;
		}
		
		return true;
	}
	
	/**
	 * Handles logging the user our of the provider. I.e. destroying a cookie, or redirecting to the provider for logout.
	 * 
	 * @return void
	 */
	function logout_from_provider() {
		setcookie( "hma_sso_logged_out", $this->id, 0, COOKIEPATH );
	}
	
	
	
	/**
	 * Gets the access token and fires any errors before showing the Register With This SSO form.
	 * 
	 * @return wp_error || true on success
	 */
	function provider_authentication_register_completed() {}
	
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
		
	//internal
	function _run_logged_out_js() {
		if ( isset( $_COOKIE['hma_sso_logged_out'] ) && $_COOKIE['hma_sso_logged_out'] == $this->id ) {
			setcookie( 'hma_sso_logged_out', '', time() - 3600, COOKIEPATH );
			$this->logged_out_js();
		}
	}
	
	function _check_for_oauth_register_completed() {

		if ( isset( $_GET['sso_registrar_authorized'] ) && $_GET['sso_registrar_authorized'] == $this->id ) {
			$result = $this->provider_authentication_register_completed();
			
			//show the register step 2 page
			add_action( 'hma_sso_login_connect_provider_with_account_form', array( &$this, 'wordpress_login_and_connect_provider_with_account_form_field' ) );
			add_action( 'hma_sso_register_form', array( &$this, 'register_form_fields' ) );
			do_action( 'hma_sso_provider_register_submitted_with_erroneous_details', &$this, $result );
			
			exit;
		}
	}
	
	function login_link_submitted() {
		$return = $this->perform_wordpress_login_from_provider();

		// If ther account was not connected, and we have register on login enabled, do that
		if( is_wp_error( $return ) && in_array( $return->get_error_code(), array( 'twitter-account-not-connected', 'facebook-account-not-connected' ) ) && defined( 'HMA_SSO_REGISTER_ACCOUNT_ON_LOGIN' ) && HMA_SSO_REGISTER_ACCOUNT_ON_LOGIN ) {

			hm_clear_messages( 'login' );
			$return = $this->perform_wordpress_register_from_provider();
			hm_clear_messages( 'register' );
		}
		
		do_action( 'hma_sso_login_attempt_completed', &$this, $return );
		
		hma_do_login_redirect( $return );
	}
	
	function register_link_submitted() {
	
		$return = $this->perform_wordpress_register_from_provider();
		
		do_action( 'hma_sso_register_attempt_completed', &$this, $return );
		
		if ( is_wp_error( $return ) )
			wp_redirect( get_bloginfo( 'register_url', 'display' ), 303 );
		
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
			$result = $this->provider_authentication_connect_with_account_completed();
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
	
	function _check_wordpress_login_and_connect_provider_with_account_submitted() {
		if ( isset( $_POST['sso_wordpress_login_connect_provider_with_account'] ) && $_POST['sso_wordpress_login_connect_provider_with_account'] == $this->id && check_admin_referer( 'hma_login_form_connect_with_sso_' . $this->id ) ) {
			
			$result = $this->perform_wordpress_login_from_site_and_connect_provider_with_account();
						
			if ( ( !$result ) || is_wp_error( $result ) ) {
				
				//set the access token for the hooks below
				$this->access_token = $this->get_access_token_from_string( $_POST['access_token'] );
				
				add_action( 'hma_sso_login_connect_provider_with_account_form', array( &$this, 'wordpress_login_and_connect_provider_with_account_form_field' ) );
				add_action( 'hma_sso_register_form', array( &$this, 'register_form_fields' ) );
				
			    do_action( 'hma_sso_provider_register_submitted_with_erroneous_details', &$this, $result );
			    
			    if ( isset( $_REQUEST['register_source'] ) && $_REQUEST['register_source'] == 'popup' )
				    wp_redirect( get_bloginfo( 'register_inline_url', 'display' ) . '?message=' );	    
			    else
				    wp_redirect( get_bloginfo( 'register_url', 'display' ) . '?message=' );
			    exit;
			}
			else {
				
				do_action( 'hma_sso_register_completed', &$this, $result );
				
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
	}
	
	function get_access_token_from_string( $string ) {
		return $string;
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
	
	//form hooks
	function wordpress_login_and_connect_provider_with_account_form_field() {
		
		if ( empty( $this->access_token ) )
			return;
		
		?>
		<input type="hidden" name="sso_wordpress_login_connect_provider_with_account" value="<?php echo $this->id ?>" />
		<input type="hidden" name="sso_provider_authorized" value="<?php echo $this->id ?>" />
		<input type="hidden" name="access_token" value="<?php echo $this->get_access_token_string() ?>" />
		<?php wp_nonce_field( 'hma_login_form_connect_with_sso_' . $this->id ) ?>
		<?php
	}
	
	function user_info() {
		return $this->get_user_info();
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
	
	function register_avatar_option( $hma_SSO_Avatar ) {
		$this->avatar_options[] = $hma_SSO_Avatar;
	}

}