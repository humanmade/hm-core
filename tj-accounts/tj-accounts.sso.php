<?php

require_once( 'tj-accounts.sso.facebook.php' );
require_once( 'tj-accounts.sso.twitter.php' );

/**
 * Returns an array of all avatar options.
 * 
 * @return array
 */
function tja_get_avatar_options() {

	global $tja_sso_avatar_options;
	return $tja_sso_avatar_options->avatar_options;

}

/**
 * Returns an array of Avatar Option objects that a is authentication with or can use.
 * 
 * @return array
 */
function tja_get_user_avatar_options() {
	global $tja_sso_avatar_options;
	
	$array = $tja_sso_avatar_options->avatar_options;
	
	foreach( $array as $key => $value ) {
		if( isset( $value->sso_provider ) && method_exists( $value->sso_provider, 'is_authenticated_for_current_user' ) && !$value->sso_provider->is_authenticated_for_current_user() )
			unset( $array[$key] );
	}
	
	return $array;
}

/**
 * Returns an Avatar Option object for a given avatar option id.
 * 
 * @param string $service_id
 * @return object | null on not found
 */
function tja_get_avatar_option( $service_id ) {
	
	foreach( tja_get_avatar_options() as $option )
		if( $option->service_id == $service_id )
			return $option;
	
}

/**
 * Returns an array of SSO provider objects for all SSO provers.
 * 
 * @return array
 */
function tja_get_sso_providers() {
	
	global $tja_sso_providers;
	return (array) $tja_sso_providers;
	
}

/**
 * Gets an SSO provider object for a given SSO provider ID.
 * 
 * @param int $sso_provider_id
 * @return object | null on not found
 */
function tja_get_sso_provider( $sso_provider_id ) {
	
	foreach( tja_get_sso_providers() as $sso_provider )
		if( $sso_provider->id == $sso_provider_id )
			return $sso_provider;
	
}

/**
 * Checks if a user logged in with a given SSO provider.
 * 
 * @param object $sso_provider. (default: tja_get_logged_in_sso_provider())
 * @return bool
 */
function tja_is_logged_in_with_sso_provider( $sso_provider = null ) {
	
	if( $sso_provider ) {
		
		return $sso_provider == tja_get_logged_in_sso_provider();
		
	} else {
		
		return (bool) tja_get_logged_in_sso_provider();
		
	}
}

/**
 * Gets the SSO provider a user used to log in with (may be null).
 * 
 * @return SSO object | null
 */
function tja_get_logged_in_sso_provider() {

	foreach( tja_get_sso_providers() as $sso_provider ) {

		if( $sso_provider->check_for_provider_logged_in() )
			return $sso_provider;
		}
}

/**
 * Returns an array of sso providers the suer has authenticated with.
 * 
 * @return array
 */
function tja_get_sso_providers_for_current_user() {
	
	$user_providers = array();
	
	foreach( tja_get_sso_providers() as $sso_provider ) {
		if( $sso_provider->is_authenticated_for_current_user() )
			$user_providers[] = $sso_provider;
	}
	
	return $user_providers;
}

/**
 * Returns an array of all the SSO providers a user has not authenticated with.
 * 
 * @return array
 */
function tja_get_sso_providers_not_authenticated_for_current_user() {

	$authenticated_providers = tja_get_sso_providers_for_current_user();
	$unauthenticated_providers = array();
	
	foreach( tja_get_sso_providers() as $p ) {
		if( !in_array( $p, $authenticated_providers ) )
			$unauthenticated_providers[] = $p;
	}
	
	return $unauthenticated_providers;
}

/**
 * Loads the avatar options and sso classes.
 * 
 */
function tja_init_avatar_options() {
	
	//only show "Uploaded" if they have one
	if( !empty( wp_get_current_user()->user_avatar_path ) )
		new tja_Uploaded_Avatar_Option();
		
	new tja_Gravatar_Avatar_Option();
	
	new tja_SSO_Facebook();
	new tja_SSO_Twitter();
}
add_action( 'init', 'tja_init_avatar_options', 1 );

/**
 * tja_SSO_Avatar_Options class.
 * 
 */
class tja_SSO_Avatar_Options {
	
	public $avatar_options;
	
	function __construct() {
		$this->avatar_options = array();
	}
	
	function register_avatar_option( $tja_SSO_Avatar ) {
		$this->avatar_options[] = $tja_SSO_Avatar;
	}

}
global $tja_sso_avatar_options;
$tja_sso_avatar_options = new tja_SSO_Avatar_Options();


/**
 * tja_SSO_Avatar_Option class.
 * Used as an abstract class, must be subclassed
 * 
 */
class tja_SSO_Avatar_Option {

	public $service_name;
	public $service_id;
	public $avatar_url;
	public $user;
	
	function __construct() {
		global $tja_sso_avatar_options;
		$tja_sso_avatar_options->register_avatar_option( &$this );
		$this->user = wp_get_current_user();
	}
	
	function get_avatar( $size = null ) {
		return $this->avatar_url;
	}
	
	function save_avatar_locally( $url, $ext = null ) {
	
		$upload_dir = wp_upload_dir();
		$upload_dir_base = $upload_dir['basedir'];
		$avatar_dir = $upload_dir_base . '/avatars';
			
		if( !is_dir($avatar_dir) )
			mkdir( $avatar_dir, 0775, true );
		
		if( !$ext )
			$ext = strtolower( end( explode( '.', $url ) ) );
		
		$image_path = $avatar_dir . '/' . $this->user->ID . '-' . $this->service_id . '.' . $ext;
		file_put_contents( $image_path, file_get_contents( $url ) );
		
		//check that the image saved ok, if not then remove it and return null
		if( !getimagesize( $image_path ) ) {
			unlink( $image_path );
			$image_path = null;
		}
		
		return $image_path;
	}
	
	function remove_local_avatar() {
		
		if( !is_user_logged_in() || empty( $this->avatar_path ) )
			return null;
		
		unlink( $this->avatar_path );
		
	}
}

class tja_Uploaded_Avatar_Option extends tja_SSO_Avatar_Option {

	function __construct() {
		
		parent::__construct();
		$this->service_name = "Uploaded";
		$this->service_id = "uploaded";		
	}
	
	function get_avatar( $size ) {
		
		if( empty( $this->user->user_avatar_path ) )
			return null;
		
		return hm_phpthumb_it( $this->user->user_avatar_path, $size );
	}
}


class tja_Gravatar_Avatar_Option extends tja_SSO_Avatar_Option {

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
class tja_SSO_Provider {
	
	public $id;
	public $name;
	
	function __construct() {
		
		global $tja_sso_providers;
		$tja_sso_providers[] = &$this;
		add_action( 'wp_footer', array( &$this, '_run_logged_out_js' ) );
		
		add_action( 'init', array( &$this, '_check_sso_registrar_submitted' ) );
		add_action( 'init', array( &$this, '_check_for_oauth_register_completed' ) );
		add_action( 'init', array( &$this, '_check_sso_login_submitted' ) );
		add_action( 'init', array( &$this, '_check_sso_connect_with_account_submitted' ) );
		add_action( 'init', array( &$this, '_check_sso_unlink_from_account_submitted' ) );
		add_action( 'init', array( &$this, '_check_wordpress_login_and_connect_provider_with_account_submitted' ) );	
	}
	
	/**
	 * Returns markup for the SSO login button.
	 * 
	 * @return string
	 */
	function get_login_button() {
	
	}
	
	/**
	 * Outputs the JS needed to fire the popup open when teh login button is clicked.
	 * 
	 */
	function get_login_open_authentication_js() {
	
	}
	/**
	 * Returns markup for the SSO register button.
	 * 
	 * @return string
	 */
	function get_register_button() {
		return $this->get_login_button();
	}
	
	function get_login_button_image() {
		return '';
	}
	
	/**
	 * Returns markup for the SSO "connect with account" button.
	 * 
	 * @return string
	 */
	function get_connect_with_account_button() {
		return $this->get_login_button();
	}
	
	function get_user_info() {
	
	}
	
	function get_unlink_from_account_url() {
		
		if( !is_user_logged_in() )
			return null;
		
		return wp_nonce_url( add_query_arg( 'sso_unlink_from_account', $this->id, get_bloginfo( 'my_profile_url', 'display' ) ), 'sso_unlink_from_account_' . $this->id );
	}
	
	function get_access_token_string() {
		return $this->access_token;
	}

	
	/**
	 * Check if the user is logged into the SSO provider (not necissarily wordpress).
	 * 
	 * @return bool
	 */
	function check_for_provider_logged_in() {		
		return false;
	}
	
	/**
	 * Logs the user into WordPress from the SSO provider.
	 * This method is responsible for the wordpress login also
	 * 
	 * @return bool
	 */
	function perform_wordpress_login_from_provider() {
		return null;
	}
	
	/**
	 * Creates a new user based off the SSO provider details (must be check_for_provider_logged_in() = true)
	 * If the provider credentials have already been used, just log them in
	 *
	 * @return bool
	 */
	function perform_wordpress_register_from_provider() {
	
		if( !$this->check_for_provider_logged_in() )
			return null;
		
		// Check if the SSO has already been registered with a WP account, if so then login them in and be done
		if( $result = $this->perform_wordpress_login_from_provider() ) {
			wp_redirect( get_bloginfo('my_profile_url', 'display') );
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
		
		$login_status = tja_log_user_in( $wp_login_details );
		
		if( is_wp_error( $login_status ) || !$login_status ) {
			return $login_status;
		}
		
		$connect_status = $this->provider_authentication_connect_with_account_completed();
		
		if( is_wp_error( $connect_status ) ) {
			//connect failed, log them out
			wp_logout();
			
			return $connect_status;
		}
		
		return true;
	}
	
	/**
	 * Unlinks the SSO from the currently logged in user.
	 * 
	 * @return true | wp_error
	 */
	function unlink_provider_from_current_user() {
	
	}
	
	/**
	 * Handles logging the user our of the provider. I.e. destroying a cookie, or redirecting to the provider for logout.
	 * 
	 * @return void
	 */
	function logout_from_provider() {
		setcookie( "tja_sso_logged_out", $this->id, 0, COOKIEPATH );
	}
	
	
	//callbacks 
	
	/**
	 * register_oauth_submitted function.
	 * 
	 * @access public
	 * @return void
	 */
	function register_oauth_submitted() {
	
	}
	
	
	/**
	 * Gets the access token and fires any errors before showing the Register With This SSO form.
	 * 
	 * @return wp_error || true on success
	 */
	function provider_authentication_register_completed() {
		
	}
	
	//is functions
	
	/**
	 * Checks if the current user has linked their account with the SSO provider.
	 * 
	 * @return bool
	 */
	function is_authenticated_for_current_user() {
		
	}
	
	function has_avatar_option() {
		
		return !empty( $this->avatar_option );
	}
		
	//internal
	function _run_logged_out_js() {
		if( isset( $_COOKIE['tja_sso_logged_out'] ) && $_COOKIE['tja_sso_logged_out'] == $this->id ) {
			setcookie( 'tja_sso_logged_out', '', time() - 3600, COOKIEPATH );
			$this->logged_out_js();
		}
	}
	
	function _check_for_oauth_register_completed() {

		if( isset( $_GET['sso_registrar_authorized'] ) && $_GET['sso_registrar_authorized'] == $this->id ) {
			$result = $this->provider_authentication_register_completed();
			
			//show the register step 2 page
			add_action( 'tja_sso_login_connect_provider_with_account_form', array( &$this, 'wordpress_login_and_connect_provider_with_account_form_field' ) );
			add_action( 'tja_sso_register_form', array( &$this, 'register_form_fields' ) );
			do_action( 'tja_sso_provider_register_submitted_with_erroneous_details', &$this, $result );
			
			exit;
		}
	}
	
	function login_link_submitted() {
		$return = $this->perform_wordpress_login_from_provider();

		do_action( 'tja_sso_login_attempt_completed', &$this, $return );
		
		tja_do_login_redirect( $return );
	}
	
	function _check_sso_login_submitted() {
		
		if( isset( $_GET['sso_login_submitted'] ) && $_GET['sso_login_submitted'] == $this->id )
			$this->login_link_submitted();
	}
	
	function _check_sso_connect_with_account_submitted() {
	
		if( isset( $_GET['sso_connect_with_account'] ) && $_GET['sso_connect_with_account'] == $this->id && wp_verify_nonce( $_GET['_wpnonce'], 'sso_connect_with_account_' . $this->id ) ) {
			$result = $this->provider_authentication_connect_with_account_completed();
			do_action( 'tja_sso_connect_with_account_completed', &$this, $result );
		}
	}
	
	function _check_sso_unlink_from_account_submitted() {
		
		if( isset( $_GET['sso_unlink_from_account'] ) && $_GET['sso_unlink_from_account'] == $this->id && wp_verify_nonce( $_GET['_wpnonce'], 'sso_unlink_from_account_' . $this->id ) ) {
			$result = $this->unlink_provider_from_current_user();
			do_action( 'tja_sso_unlink_from_account_completed', &$this, $result );
		}
	}
	
	function _check_sso_registrar_submitted() {
		if( isset( $_GET['sso_registrar_submitted'] ) && $_GET['sso_registrar_submitted'] == $this->id && wp_verify_nonce( $_GET['_wpnonce'], 'sso_registrar_submitted_' . $this->id ) )
			$this->register_oauth_submitted();
	}
	
	function _check_wordpress_login_and_connect_provider_with_account_submitted() {
		if( isset( $_POST['sso_wordpress_login_connect_provider_with_account'] ) && $_POST['sso_wordpress_login_connect_provider_with_account'] == $this->id && check_admin_referer( 'tja_login_form_connect_with_sso_' . $this->id ) ) {
			
			$result = $this->perform_wordpress_login_from_site_and_connect_provider_with_account();
						
			if( ( !$result ) || is_wp_error( $result ) ) {
				
				//set the access token for the hooks below
				$this->access_token = $this->get_access_token_from_string( $_POST['access_token'] );
				
				add_action( 'tja_sso_login_connect_provider_with_account_form', array( &$this, 'wordpress_login_and_connect_provider_with_account_form_field' ) );
				add_action( 'tja_sso_register_form', array( &$this, 'register_form_fields' ) );
				
			    do_action( 'tja_sso_provider_register_submitted_with_erroneous_details', &$this, $result );
			    
			    if( isset( $_REQUEST['register_source'] ) && $_REQUEST['register_source'] == 'popup' )
				    wp_redirect( get_bloginfo( 'register_inline_url', 'display' ) . '?message=' );	    
			    else
				    wp_redirect( get_bloginfo( 'register_url', 'display' ) . '?message=' );
			    exit;
			}
			else {
				
				do_action( 'tja_sso_register_completed', &$this, $result );
				
			    if( $_POST['redirect_to'] )
			    	$redirect = $_POST['redirect_to'];
			    elseif( $_POST['referer'] )
			    	$redirect = $_POST['referer'];
			    elseif( wp_get_referer() )
			    	$redirect = wp_get_referer();
			    else
			    	$redirect = get_bloginfo('my_profile_url', 'display');
			    	
			    wp_redirect( $redirect );
			    exit;
			}
		}
	}
	
	function get_access_token_from_string( $string ) {
		return $string;
	}
	
	function _get_sso_login_submit_url() {
		return html_entity_decode( wp_nonce_url( add_query_arg( 'login_source', 'popup', add_query_arg( 'sso_login_submitted', $this->id, get_bloginfo( 'login_url', 'display' ) ) ), 'sso_login_submitted_' . $this->id ) );
	}
	
	function _get_provider_authentication_completed_connect_account_redirect_url() {
		return html_entity_decode( wp_nonce_url( add_query_arg( 'sso_connect_with_account', $this->id, get_bloginfo( 'my_profile_url', 'display' ) ), 'sso_connect_with_account_' . $this->id ) );
	}
	
	function _get_sso_register_submit_url() {
		
		return html_entity_decode( wp_nonce_url( add_query_arg( 'sso_registrar_submitted', $this->id, get_bloginfo( 'register_url', 'display' ) ), 'sso_registrar_submitted_' . $this->id ) );
		
	}
	
	function _get_provider_authentication_completed_register_redirect_url() {
		return html_entity_decode( wp_nonce_url( add_query_arg( 'sso_registrar_authorized', $this->id, get_bloginfo( 'register_url', 'display' ) ), 'sso_registrar_authorized_' . $this->id ) );
	}
	
	//form hooks
	function wordpress_login_and_connect_provider_with_account_form_field() {
		
		if( empty( $this->access_token ) )
			return;
		
		?>
		<input type="hidden" name="sso_wordpress_login_connect_provider_with_account" value="<?php echo $this->id ?>" />
		<input type="hidden" name="sso_provider_authorized" value="<?php echo $this->id ?>" />
		<input type="hidden" name="access_token" value="<?php echo $this->get_access_token_string() ?>" />
		<?php wp_nonce_field( 'tja_login_form_connect_with_sso_' . $this->id ) ?>
		<?php
	}

		
	function logged_out_js() {
		
	}
	
	function log( $var ) {
		error_log( $var );
	}
	
}
