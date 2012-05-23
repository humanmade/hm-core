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
		
		if ( !defined( 'HMA_SSO_FACEBOOK_APP_ID' ) || !defined( 'HMA_SSO_FACEBOOK_APPLICATION_SECRET' ) ) {
			
			throw new Exception( 'constants-not-defined' );
			return new WP_Error( 'constants-not-defined' );
		}
		
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
		
		$this->avatar_option = new HMA_Facebook_Avatar_Option( $this );
							
	}
	
	/**
	 * Gets the facebook access token for $this->user
	 * 
	 * @access public
	 * @return null
	 */
	public function get_access_token() {
	
		if( ! $this->user )
			return null;

		return $this->get_user_access_token( $this->user->ID );
	}

	/**
	 * Link the current Facebook with $this->user
	 * 
	 * @access public
	 * @return true | WP_Error on failure
	 */
	public function link() {
		
		if ( ! is_user_logged_in() )
			return new WP_Error( 'user-logged-in' );

		if ( $access_token = $this->get_access_token_from_cookie_session() ) {
			$this->access_token = $access_token;
		} else {
			hm_error_message( 'There was an unknown problem connecting your with Facebook, please try again.', 'update-user' );
			return new WP_Error( 'facebook-communication-error' );
		}
		
		$info = $this->get_facebook_user_info();

		//Check if this facebook account has already been connected with an account
		if ( $this->_get_user_id_from_sso_id( $info->id ) ) {
			
			hm_error_message( 'This Facebook account is already linked with another account, please try a different one.', 'update-user' );
			return new WP_Error( 'sso-provider-already-linked' );
		}
		
		update_user_meta( get_current_user_id(), '_fb_access_token', $this->get_offline_access_token( $this->access_token ) );
		update_user_meta( get_current_user_id(), '_fb_uid', $info['id'] );
		
		hm_success_message( 'Successfully connected the Facebook account "' . $info['name'] . '" with your profile.', 'update-user' );
		
		do_action( 'user_linked_facebook_account', $this );
		
		return true;
	}
	
	public function unlink() {
		
		if ( ! $this->user )
			return new WP_Error( 'no-user-set' );
		
		if ( ! get_user_meta( $this->user->ID, '_fb_uid', true ) ) {
			return true;	
		}
		
		delete_user_meta( $this->user->ID, '_fb_uid' );
		delete_user_meta( $this->user->ID, '_fb_access_token' );
		
		$this->avatar_option->remove_local_avatar();
					
		hm_success_message( 'Successfully unlinked Facebook from your account.', 'update-user' );
		
		return $this->logout( 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] );
	}
	
	public function is_authenticated( $check_valid_token = false ) {
		
		if( ! $this->user )
			return false;
		
		$access_token = get_user_meta( $this->user->ID, '_fb_access_token', true );
		
		if ( ! $access_token )
			return false;

		if ( $check_valid_token == false )
			return true;
			
		// Check that the access token is still valid
		try {
			$this->client->api('me', 'GET', array( 'access_token' => $this->access_token ));
		} catch( Exception $e ) {
			
			// They key is dead, or somethign else is wrong, clean up so this doesnt happen again.
			add_user_meta( $this->user->ID, '_fb_access_token_deleted', $e->getCode() . '::' . $e->getMessage() );
			delete_user_meta( $this->user->ID, '_fb_access_token' );
		}
		
		return true;
		
	}
	
	function get_user_for_access_token() {
		
		if( ! $this->access_token )
			return false;
		
		global $wpdb;

		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_access_token' AND meta_value = '{$this->access_token}'" );
		

		return $user_id;
	}
	
	public static function get_user_for_uid( $uid, $access_token = null, $flush = false ) {

		if ( ! $flush && ( $id = wp_cache_get( 'fb-uid' . $uid . $access_token, 'user_for_uid' ) ) !== false )
			return $id ? $id : null;

		global $wpdb;

		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_uid' AND meta_value = '{$uid}'" );
		
		if( ! $user_id ) {
			wp_cache_set( 'fb-uid' . $uid . $access_token, 0, 'user_for_uid', 3600 );
			return null;
		}
		
		if( $access_token ) {
			wp_cache_set( 'fb-uid' . $uid . $access_token, 0, 'user_for_uid', 3600 );
			return self::get_user_access_token( $user_id ) == $access_token ? $user_id : null;
		}
		
		wp_cache_set( 'fb-uid' . $uid . $access_token, $user_id, 'user_for_uid', 3600 );
		return $user_id;
	}
	
	function get_user_access_token( $wp_user_id = null ) {
		
		if( is_null( $wp_user_id ) )
			$wp_user_id = get_current_user_id();
		
		return get_user_meta( $wp_user_id, '_fb_access_token', true );
	}

	private function get_offline_access_token() {

		try {
			$res = wp_remote_get( add_query_arg( array( 'fb_exchange_token' => $this->access_token, 'grant_type' => 'fb_exchange_token', 'client_id' => $this->app_id, 'client_secret' => $this->application_secret ), 'https://graph.facebook.com/oauth/access_token' ) );
			$res = wp_remote_retrieve_body( $res );
			$res = wp_parse_args( $res, array( 'access_token' => '' ) );

		} catch( Exception $e ) {
			
			return false;
			
		}

		return $res['access_token'];
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
	
	public function check_for_provider_logged_in() {
		
		if ( $access_token = $this->get_access_token_from_cookie_session() ) {
			$this->access_token = $access_token;
		}
				
		return (bool) $this->access_token;
	}
	
	private function get_access_token_from_cookie_session() {
		
		if ( empty( $this->client ) )
			return null;
		
		if ( ! empty( $_REQUEST['access_token'] ) )
			$this->client->setAccessToken( $_REQUEST['access_token'] );
		
		if ( !$this->client->getUser() ) {
			return null;
		}
	
		return $this->client->getAccessToken();
	}
	
	protected function get_user_info() {

		$fb_profile_data = $this->get_facebook_user_info();

		$userdata = array( 
			'user_login'	=> hma_unique_username( sanitize_title( $fb_profile_data['name'] ) ),
			'user_email'	=> isset( $fb_profile_data['email'] ) ? $fb_profile_data['email'] : '',
			'first_name' 	=> $fb_profile_data['first_name'],
			'last_name'		=> $fb_profile_data['last_name'],
			'description'	=> isset( $fb_profile_data['bio'] ) ? $fb_profile_data['bio'] : '',
			'display_name'	=> $fb_profile_data['name'],
			'display_name_preference' => 'first_name last_name',
			'_fb_uid'		=> $fb_profile_data['id']
		);

		return $userdata;
	}
	
	function get_facebook_user_info() {
		
		if ( empty( $this->user_info ) ) {
			try {
				$this->user_info = @$this->client->api('me', 'GET', array( 'access_token' => $this->access_token ));
			} catch( Exception $e ) {
			
			}
		}
		
		return $this->user_info;
	}
	
	/**
	 * Log a user into the site via Facebook
	 * 
	 * @return WP_Error|bool
	 */
	public function login( $details = array() ) {
		
		$details = wp_parse_args( $details, array( 'remember' => false ) );

		if ( ! $this->check_for_provider_logged_in() ) {
			return new WP_Error( 'no-logged-in-to-facebook', 'You are not logged in to Facebook' );
		}
		
		global $wpdb;
		
		$fb_uid = $this->client->getUser();
		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fb_uid' AND meta_value = '{$fb_uid}'" );
		
		if ( ! $user_id ) {
			
			$fb_info = $this->get_facebook_user_info();
			$user_id = $this->_get_user_id_from_sso_id( $fb_info['username'] );

			if ( ! $user_id ) {
				return new WP_Error( 'facebook-account-not-connected', 'This Facebook account has not been linked to an account on this site.' );
			}
		}
		
		//Update their access token incase it has changed
		$this->set_user( get_userdata( $user_id ) );
		$this->access_token = $this->get_access_token_from_cookie_session();
		$this->save_access_token();

		wp_set_auth_cookie( $user_id, $details['remember'] );
		wp_set_current_user( $user_id );
		
		do_action( 'hma_log_user_in', $user_id);
		do_action( 'hma_log_user_in_via_facebook', $user_id);
		do_action( 'wp_login', get_userdata( $user_id )->user_login );
		do_action( 'hma_login_submitted_success' );
		
		return true;
	}

	public function register() {

		// Check if the SSO has already been registered with a WP account, if so then login them in and be done
		if ( ( $result = $this->login() ) && ! is_wp_error( $result ) ) {
			return $this->user->ID;
		}
		
		try {
			$fb_profile_data = $this->get_user_info();
			$_fb_profile_data = $this->get_facebook_user_info();

		} catch(Exception $e) {
			return new WP_Error( 'facebook-exception', $e->getMessage() );
		}
		
		$fb_profile_data['gender'] 		= $_fb_profile_data['gender'];
		$fb_profile_data['url'] 		= isset( $_fb_profile_data['website'] ) ? $_fb_profile_data['website'] : '';
		$fb_profile_data['location'] 	= $_fb_profile_data['location']['name'];

		if ( isset( $_fb_profile_data['birthday'] ) )
			$fb_profile_data['age'] 		= ( (int) date('Y') ) - ( (int) date( 'Y', strtotime( $_fb_profile_data['birthday'] ) ) );

		$userdata = apply_filters( 'hma_register_user_data_from_sso', array_merge( $fb_profile_data, array_filter( $this->registration_data ) ), $_fb_profile_data, $this );		

		$this->set_registration_data( $userdata );

		$result = parent::register();

		if ( is_wp_error( $result ) )
			return $result;
		
		// Set_user() will override access token
		$token = $this->access_token;
		$user = get_userdata( $result );
		$this->set_user( get_userdata( $result ) );
		$this->access_token = $token;
		
		$this->update_user_facebook_information();
		
		//set the avatar to their twitter avatar if registration completed
		if ( ! is_wp_error( $result ) && is_numeric( $result ) && $this->is_authenticated() ) {
			
			$this->avatar_option = new HMA_Facebook_Avatar_Option( $this );
			update_user_meta( $result, 'user_avatar_option', $this->avatar_option->service_id );
		}
		
		do_action( 'hma_registered_user_via_facebook', $user );

		do_action( 'hma_sso_register_attempt_completed', $this, $result );

		return $result;	
	}
	
	private function update_user_facebook_information() {
		

		$info = $this->get_facebook_user_info();
		$user_id = $this->user->ID;
		$this->access_token = $this->get_offline_access_token();
		update_user_meta( $user_id, '_fb_access_token', $this->access_token );
		update_user_meta( $user_id, '_facebook_data', $this->get_facebook_user_info() );
		update_user_meta( $user_id, '_fb_uid', $info['id'] );
		update_user_meta( $user_id, 'facebook_username', $info['username'] );
		$this->get_user_for_uid( $user_id, null, true );

	}
	
	public function _validate_hma_new_user( $result ) {

		if( is_wp_error( $result ) && $result->get_error_code() == 'invalid-email' )
			return null;
		
		return $result;
	}
	
	public function logout( $redirect ) {
		
		//redirect can be relitive, make it not so
		$redirect = get_bloginfo( 'url' ) . str_replace( get_bloginfo( 'url' ), '', $redirect );
		/*
		//only redirect to facebook is is logged in with a cookie
		if ( $this->client->getUser() ) {
			wp_redirect( $this->client->getLogoutUrl( array( 'next' => $redirect ) ), 303 );
			exit;
		}
		*/
		return true;
	
	}

	public function get_facebook_id() {

		return get_user_meta( $this->user->ID, '_fb_uid', true );
	}
	
	function _get_user_id_from_sso_id( $sso_id ) {
		global $wpdb;
		
		if( $sso_id && $var = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE ( meta_key = '_facebook_uid' OR meta_key = 'facebook_username' ) AND meta_value = '{$sso_id}'" ) ) {
			return $var;
		}
		
	}
	
	function _get_facebook_username() {
	
		$info = $this->get_facebook_user_info();
		
		return $info['username'];
	
	}

	public function get_facebook_friends() {

		if( ! $data = get_user_meta( $this->user->ID, '_facebook_friends', true ) ) {
			
			try {
				$data = @$this->client->api('me/friends', 'GET', array( 'access_token' => $this->access_token ));

				$data = reset( $data );
				update_user_meta( $this->user->ID, '_facebook_friends', $data );
			} catch( Exception $e ) {
				$data = array();
			}
		}

		foreach ( $data as &$fb_user )
			$fb_user['picture_small'] = 'https://graph.facebook.com/' . $fb_user['id'] . '/picture?type=square';

		usort( $data, function( array $a, array $b) {
			return $a['name'] > $b['name'];
		} );

		return $data;
	}
	
	public function save_access_token() {
		
		$this->_get_user_id_from_sso_id( $this->user->ID, null, true );
		update_user_meta( $this->user->ID, '_fb_access_token', $this->get_offline_access_token( $this->access_token ) );
	
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
		

		$this->avatar_path = null;

		if ( get_user_meta( $this->user->ID, '_facebook_avatar_last_fetch', true ) > time() - ( 3600 * 24 ) && ( $avatar = get_user_meta( $this->user->ID, '_facebook_avatar', true ) ) && file_exists( $avatar ) ) {

			$this->avatar_path = $avatar;

			$cache = true;

		} elseif ( $id = $this->sso_provider->get_facebook_id() ) {

			$image_url = "http://graph.facebook.com/{$id}/picture?type=large";

			// remove their old one
			$this->avatar_path = get_user_meta( $this->user->ID, '_facebook_avatar', true );
			$this->remove_local_avatar();
			$this->avatar_path = null;

			$this->avatar_path = $this->save_avatar_locally( $image_url, 'jpg' );
			
			update_user_meta( $this->user->ID, '_facebook_avatar', $this->avatar_path );
			update_user_meta( $this->user->ID, '_facebook_avatar_last_fetch', time() );

			$cache = false;
		}
		
		if ( ! $cache )
			$size .= '&cache=0';
		
		return wpthumb( $this->avatar_path, $size );
	}
	
	function remove_local_avatar() {
		
		if ( !is_user_logged_in() || empty( $this->avatar_path ) )
			return null;
		
		unlink( $this->avatar_path );
		
		delete_user_meta( get_current_user_id(), '_facebook_avatar' );
	}
	
}


/**
 * _facebook_add_username_to_editprofile_contact_info function.
 * 
 * @access private
 * @param mixed $methods
 * @param mixed $user
 * @return null
 */
function _facebook_add_username_to_editprofile_contact_info( $methods, $user ) {

	if( empty( $methods['facebook_username'] ) )
		$methods['facebook_username'] = __( 'Facebook Username' );
	
	return $methods;

}
add_filter( 'user_contactmethods', '_facebook_add_username_to_editprofile_contact_info', 10, 2 );
