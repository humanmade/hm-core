<?php

class HMA_SSO_Facebook extends HMA_SSO_Provider {
	
	public $facebook_uid;
	private static $instance;
	
	static function instance() {
		
		if ( empty( self::$instance ) ) {
			$className = __CLASS__;
            self::$instance = new $className();
		}
		
		return self::$instance;
		
	}
	
	function __construct() {
	
		parent::__construct();
		
		if ( !defined( 'HMA_SSO_FACEBOOK_APP_ID' ) || !defined( 'HMA_SSO_FACEBOOK_APPLICATION_SECRET' ) )
			return new WP_Error( 'constants-not-defined' );
		
		$this->id = 'facebook';
		$this->name = 'Facebook';
		$this->app_id = HMA_SSO_FACEBOOK_APP_ID;
		$this->application_secret = HMA_SSO_FACEBOOK_APPLICATION_SECRET;
		$this->facebook_uid = null;
		$this->supports_publishing = true;
		$this->set_user( wp_get_current_user() );
		
		require_once( 'facebook-sdk/src/facebook.php' );
		
		$this->client = new Facebook(array(
		  'appId'  => $this->app_id,
		  'secret' => $this->application_secret,
		  'cookie' => true,
		));
		
		$this->avatar_option = new HMA_Facebook_Avatar_Option( &$this );
							
	}
	
	public function get_access_token() {
	
		if( !$this->user )
			return null;

		return $this->get_user_access_token( $this->user->ID );
	
	}
	
	function get_login_button_image() {
		return HELPERURL . 'assets/images/facebook-login-button.png';
	}

	function register_sso_submitted() {
		
		return $this->perform_wordpress_register_from_provider();
	
	}
	
	/**
	 * Gets the access token and fires any errors before showing the Register With This SSO form.
	 * 
	 * @return wp_error || true on success
	 */
	function provider_authentication_register_completed() {
		
		//try to use the fb cookie first
		if ( $access_token = $this->get_access_token_from_cookie_session() ) {
			$this->access_token = $access_token;
		} else {

			return new WP_Error( 'no-access-token' );
		}
		
		$info = $info = $this->get_user_info();
		
		//Check if this facebook account has already been connected with an account, if so log them in and dont register
		if ( !empty( $info['_fb_uid'] ) && $this->_get_user_id_from_sso_id( $info['_fb_uid'] ) ) {

			$result = $this->perform_wordpress_login_from_provider();
			do_action( 'hma_sso_register_completed', &$this );
		} elseif ( empty( $info['_fb_uid'] ) ) {
			
			hm_error_message( 'There was a problem communication with Facebook, please try again.', 'register' );
			return new WP_Error( 'facebook-connection-error' );
			
		}
		
		return true;
	}
	
	function provider_authentication_connect_with_account_completed() {
		
		if ( !is_user_logged_in() )
			return new WP_Error( 'user-logged-in' );
		
		if ( $access_token = $this->get_access_token_from_cookie_session() ) {
			$this->access_token = $access_token;
		} else {
			hm_error_message( 'There was an unknown problem connecting your with Facebook, please try again.', 'update-user' );
			return new WP_Error( 'facebook-communication-error' );
		}
		
		$info = $this->get_facebook_user_info();

		//Check if this twitter account has already been connected with an account, if so log them in and dont register
		if ( $this->_get_user_id_from_sso_id( $info->id ) ) {
			
			hm_error_message( 'This Twitter account is already linked with another account, please try a different one.', 'update-user' );
			return new WP_Error( 'sso-provider-already-linked' );
		}
		
		update_user_meta( get_current_user_id(), '_fb_access_token', $this->access_token );
		update_user_meta( get_current_user_id(), '_fb_uid', $info['id'] );
		
		hm_success_message( 'Successfully connected the Facebook account "' . $info['name'] . '" with your profile.', 'update-user' );
		
		return true;
	}
	
	public function connect() {
		
		if( !$this->client->getUser() )
			return new WP_Error( 'not-logged-in-to-facebook' );
		
		$fb_uid = $this->client->getUser();
		$this->access_token = $this->client->getAccessToken();
		
		update_user_meta( $this->user->ID, '_fb_access_token', $this->access_token );
		update_user_meta( $this->user->ID, '_fb_uid', $fb_uid );
		
		return $this->user->ID;
	}
	
	function unlink() {
		
		if ( !$this->user )
			return new WP_Error( 'user-not-logged-in' );
		
		if ( !get_user_meta( $this->user->ID, '_fb_uid', true ) ) {
			return true;	
		}
		
		delete_user_meta( $this->user->ID, '_fb_uid' );
		delete_user_meta( $this->user->ID, '_fb_access_token' );
		
		$this->avatar_option->remove_local_avatar();
					
		hm_success_message( 'Successfully unlinked Facebook from your account.', 'update-user' );
		
		return $this->logout( 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] );
	}
	
	function is_authenticated() {
		
		if( !$this->user )
			return false;
		
		$access_token = get_user_meta( $this->user->ID, '_fb_access_token', true );
		
		if ( !$access_token )
			return false;
			
		// Check that the access token is still valid
		try {
			$this->client->api('me', 'GET', array( 'access_token' => $this->access_token ));
		} catch( Exception $e ) {
			
			// They key is dead, or somethign else is wrong, clean up so this doesnt happen again.
			delete_user_meta( $this->user->ID, '_fb_access_token' );
		}
		
		return true;
		
	}
	
	function get_user_for_access_token() {
		
		if( !$this->access_token )
			return false;
		
		global $wpdb;
		
		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_access_token' AND meta_value = '{$this->access_token}'" );
		
		return $user_id;
	}
	
	function get_user_for_uid( $uid, $access_token = null ) {
		
		global $wpdb;
		
		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_uid' AND meta_value = '{$uid}'" );
		
		if( !$user_id )
			return null;
		
		if( $access_token )
			return $this->get_user_access_token( $user_id ) == $access_token ? $user_id : null;
		
		return $user_id;
	}
	
	function get_user_access_token( $wp_user_id = null ) {
		
		if( is_null( $wp_user_id ) )
			$wp_user_id = get_current_user_id();
		
		return get_user_meta( $wp_user_id, '_fb_access_token', true );
	}
	
	public function is_acccess_token_valid() {
	
			
		// Check that the access token is still valid
		try {
			$this->client->api('me', 'GET', array( 'access_token' => $this->access_token ));

		} catch( Exception $e ) {
			
			return false;
			
		}
		
		return true;
	}
	
	//internal
	
	function _get_oauth_login_url() {
		return 'https://graph.facebook.com/oauth/authorize?client_id=' . $this->api_key . '&redirect_uri=' . $this->_get_oauth_redirect_url();
	}
	
	function check_for_provider_logged_in() {
		
		if ( $access_token = $this->get_access_token_from_cookie_session() ) {
			$this->access_token = $access_token;
		}
				
		return (bool) $this->access_token;
	}
	
	function register_form_fields() {
		
		if ( empty( $this->access_token ) )
			return;
		
		?>
		<input type="hidden" name="sso_registrar_authorized" value="<?php echo $this->id ?>" />
		<input type="hidden" name="access_token" value="<?php echo $this->access_token ?>" />
		<?php
	}
	
	function get_access_token_from_cookie_session() {
		
		if ( empty( $this->client ) )
			return null;

		if ( !$this->client->getUser() ) {
			return null;
		}
				
		return $this->client->getAccessToken();
	}
	
	function get_user_info() {

		$fb_profile_data = $this->get_facebook_user_info();
			
		$userdata = array( 
 			'user_login'	=> sanitize_title( $fb_profile_data['name'] ),
			'first_name' 	=> $fb_profile_data['first_name'],
			'last_name'		=> $fb_profile_data['last_name'],
			'description'	=> $fb_profile_data['bio'],
			'display_name'	=> $fb_profile_data['name'],
			'display_name_preference' => 'first_name last_name',
			'_fb_uid'		=> $fb_profile_data['id']
		);

		return $userdata;
	}
	
	function get_facebook_user_info() {
		
		if ( empty( $this->user_info ) ) {
			$this->user_info = @$this->client->api('me', 'GET', array( 'access_token' => $this->access_token ));
		}
		
		return $this->user_info;
	}
	
	
	function perform_wordpress_login_from_provider() {
		
		if ( !$this->check_for_provider_logged_in() )
			return new WP_Error( 'no-logged-in-to-facebook' );
		
		global $wpdb;
		
		$fb_uid = $this->client->getUser();
		
		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_uid' AND meta_value = '{$fb_uid}'" );
		
		if ( !$user_id ) {
			hm_error_message( 'This Facebook account has not been linked to an account on this site.', 'login' );
			return new WP_Error( 'facebook-account-not-connected' );
		}
		
		//Update their access token incase it has changed
		update_user_meta( $user_id, '_fb_access_token', $this->get_access_token_from_cookie_session() );		
		
		wp_set_auth_cookie( $user_id, false );
		set_current_user( $user_id );
		
		do_action( 'hma_log_user_in', $user_id);
		do_action( 'hma_login_submitted_success' );
		
		return true;
		
	}
	
	function perform_wordpress_register_from_provider() {
		
		try {
			$fb_profile_data = $this->get_user_info();
			$_fb_profile_data = $this->get_facebook_user_info();
		} catch(Exception $e) {
			return new WP_Error( 'facebook-exception', $e->getMessage() );
		}
		
		$userdata = apply_filters( 'hma_register_user_data_from_sso', $fb_profile_data, $_fb_profile_data, &$this );
		
		if ( !empty( $_POST['user_login'] ) )
			$userdata['user_login'] = esc_attr( $_POST['user_login'] );
		
		if (  !empty( $_POST['user_email'] ) )
			$userdata['user_email'] = esc_attr( $_POST['user_email'] );
		elseif( !empty( $this->user_info['email'] ) )
			$userdata['user_email'] = $this->user_info['email'];
		
		//Don't use such strict validation for registration via facebook
		add_action( 'hma_registration_info', array( &$this, '_validate_hma_new_user' ),11 );
		
		$userdata['override_nonce'] = true;
		$userdata['do_login'] = true;
		$userdata['_fb_access_token'] = $this->access_token;
		$userdata['do_redirect'] = false;
		$userdata['unique_email'] = false;
		$userdata['send_email'] = true;
		$userdata['gender'] = $_fb_profile_data['gender'];
		$userdata['facebook_url'] = $_fb_profile_data['link'];
		$userdata['url'] = $_fb_profile_data['website'];
		$userdata['location'] = $_fb_profile_data['location']['name'];
		$userdata['age'] = ( (int) date('Y') ) - ( (int) date( 'Y', strtotime( $_fb_profile_data['birthday'] ) ) );
		$userdata['_facebook_data'] = $_fb_profile_data;
		
		// Lets us skip email check from wp_insert_user()
		define( 'WP_IMPORTING', true );
		
	 	$result = hma_new_user( $userdata );
	 	
	 	if ( is_wp_error( $result ) )
			add_action( 'hma_sso_login_connect_provider_with_account_form', array( &$this, 'wordpress_login_and_connect_provider_with_account_form_field' ) );
	 	
	 	$this->set_user( get_userdata( $result ) ); 
	 	
	 	//set the avatar to their twitter avatar if registration completed
		if ( !is_wp_error( $result ) && is_numeric( $result ) && $this->is_authenticated() ) {
			
			$this->avatar_option = new HMA_Facebook_Avatar_Option( &$this );
			update_user_meta( $result, 'user_avatar_option', $this->avatar_option->service_id );
		}
		
		return $result;	
	}
	
	public function _validate_hma_new_user( $result ) {

		if( is_wp_error( $result ) && $result->get_error_code() == 'invalid-email' )
			return null;
		
		return $result;
	}
	
	public function logout( $redirect ) {
		
		//redirect can be relitive, make it not so
		$redirect = get_bloginfo( 'url' ) . str_replace( get_bloginfo( 'url' ), '', $redirect );
		
		//only redirect to facebook is is logged in with a cookie
		if ( $this->client->getUser() ) {
			wp_redirect( $this->client->getLogoutUrl( array( 'next' => $redirect ) ), 303 );
			exit;
		}
		
		return true;
	
	}
	
	function _get_user_id_from_sso_id( $sso_id ) {
		global $wpdb;
		return $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_uid' AND meta_value = '{$sso_id}'" );
	}
	
	
	function _get_facebook_username() {
	
		$info = $this->get_facebook_user_info();
		
		return $info['username'];
	
	}
	
	public function save_access_token() {
	
		update_user_meta( $this->user->ID, '_fb_access_token', $this->access_token );
	
	}
	
	public function user_info() {
		
		if( $data = get_user_meta( $this->user->ID, '_facebook_data', true ) )
			return (array) $data;
		
		$data = (array) $this->get_facebook_user_info();
		
		update_user_meta( $this->user->ID, '_facebook_data', $data );
		
		return $data;
	}
	
	/**
	 * Published a message to a user's facebook wall.
	 * 
	 * @access public
	 * @param array : message, image_src, image_link, link_url, link_name
	 * @return true | wp_error
	 */
	public function publish( $data ) {
	
		if( !$this->can_publish() )
			return new WP_Error( 'can-not-publish' );
		
		$fb_data = array( 'type' => $data['type'] == 'image' ? 'photo' : $data['type'], 'message' => $data['message'], 'picture' => $data['image_src'], 'link' => $data['link_url'], 'description' => $link_name );

		$fb_data['access_token'] = $this->access_token;
		
		$fb_data = array_filter( $fb_data );
		
		return @$this->client->api( $this->_get_facebook_username() . '/feed', 'POST', $fb_data );
	}
	
}

class HMA_Facebook_Avatar_Option extends HMA_SSO_Avatar_Option {
	
	public $sso_provider;
	
	function __construct( $sso_provider ) {
		$this->sso_provider = $sso_provider;
		
		parent::__construct();
		$this->service_name = "Facebook";
		$this->service_id = "facebook";

	}
	
	function set_user( $user ) {
		parent::set_user( $user );
		$this->sso_provider->set_user( $user );
	}
	
	function get_avatar( $size = null ) {			

		if ( ( $avatar = get_user_meta( $this->user->ID, '_facebook_avatar', true ) ) && file_exists( $avatar ) ) {
		    $this->avatar_path = $avatar;

		} elseif ( $this->sso_provider->is_authenticated() ) {
		    $user_info = $this->sso_provider->get_facebook_user_info();
		    
			$image_url = "http://graph.facebook.com/{$user_info['id']}/picture?type=large";			
		    $this->avatar_path = $this->save_avatar_locally( $image_url, 'jpg' ) ;
		    
		    update_user_meta( $this->user->ID, '_facebook_avatar', $this->avatar_path );
		}
		
		
		return wpthumb( $this->avatar_path, $size );
	}
	
	function remove_local_avatar() {
		
		if ( !is_user_logged_in() || empty( $this->avatar_path ) )
			return null;
		
		unlink( $this->avatar_path );
		
		delete_user_meta( get_current_user_id(), '_facebook_avatar' );
	}
	
}