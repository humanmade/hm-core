<?php

class HMA_SSO_Facebook extends HMA_SSO_Provider {
	
	public $facebook_uid;
	
	function __construct() {
	
		parent::__construct();
		
		if ( !defined( 'HMA_SSO_FACEBOOK_APP_ID' ) || !defined( 'HMA_SSO_FACEBOOK_APPLICATION_SECRET' ) || !defined( 'HMA_SSO_FACEBOOK_API_KEY' ) )
			return new WP_Error( 'constants-not-defined' );
		
		$this->id = 'facebook';
		$this->name = 'Facebook';
		$this->app_id = HMA_SSO_FACEBOOK_APP_ID;
		$this->application_secret = HMA_SSO_FACEBOOK_APPLICATION_SECRET;
		$this->api_key = HMA_SSO_FACEBOOK_API_KEY;
		$this->facebook_uid = null;
		$this->supports_publishing = true;
		$this->set_user( wp_get_current_user() );
		
		require_once( 'facebook-sdk/facebook.php' );
		
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
	
	function get_login_button() {
		
		$output = '
		
		<script type="text/javascript">
		  	FB.init({appId: ' . $this->client->getAppId() . ', status: true, cookie: true, xfbml: true, session: ' . json_encode( $this->client->getSession() ) . ' });
			FB.logout();
		  	FB.Event.subscribe("auth.login", function() {
				document.location = "' . $this->_get_sso_login_submit_url() . '&rand=" + new Date().getTime();
  			});

		</script>';
		
		$output .= '<fb:login-button perms="offline_access" width=100></fb:login-button>';
		
		return $output;
	
	}
	
	function get_init_js() {
		$output = '
		
		<script type="text/javascript">
		  	FB.init({appId: ' . $this->client->getAppId() . ', status: true, cookie: true, xfbml: true, session: ' . json_encode( $this->client->getSession() ) . ' });
			FB.logout();  
			FB.Event.subscribe("auth.login", function() {
				document.location = "' . $this->_get_sso_login_submit_url() . '&rand=" + new Date().getTime();
  			});

		</script>';
		
		return $output;
	}
	
	function get_init_js_connect_with_account() {
		$output = '
		
		<script type="text/javascript">
		  	FB.init({appId: ' . $this->client->getAppId() . ', status: true, cookie: true, xfbml: true, session: ' . json_encode( $this->client->getSession() ) . ' });
		  	
		  	//log them for for easy of use
		  	if ( FB._userStatus == "connected" ) {
				FB.logout();
		  	}
		  	
		  	FB.Event.subscribe("auth.login", function() {
		 		document.location = "' . $this->_get_provider_authentication_completed_connect_account_redirect_url() . '";
		 	});
		 	
		 	function SignInWithFacebookClicked( elem ) {

				FB.login( function() {}, { perms:"read_stream,publish_stream,offline_access,user_about_me,user_birthday,user_location,user_website,email"} );
				return false;
			}
		</script>';
		
						
		return $output;
	}
	
	function get_login_open_authentication_js() {
		?>
		<script>
			jQuery( window ).load( function() { jQuery('.fb_button_medium').click() } );
		</script>
		<?php
	}
	
	function get_login_button_image() {
		return HELPERURL . 'assets/images/facebook-login-button.png';
	}

	
	function get_register_button() {
		
		$output = '<script src="http://connect.facebook.net/en_US/all.js" type="text/javascript"></script><div id="fb-root"></div>
		
		<script type="text/javascript">
		  	FB.init({appId: ' . $this->client->getAppId() . ', status: true, cookie: true, xfbml: true, session: ' . json_encode( $this->client->getSession() ) . ' });
		  	
		  	FB.getLoginStatus(function(response) {
			 	if (response.session) {
			    	// logged in and connected user, someone you know
			    	parent.jQuery.fancybox.showActivity();
					document.location = "' . $this->_get_provider_authentication_completed_register_redirect_url() . '&rand=" + new Date().getTime();
			  	}
			});
			
		  	FB.Event.subscribe("auth.login", function() {
		 		parent.jQuery.fancybox.showActivity();
		 		document.location = "' . $this->_get_provider_authentication_completed_register_redirect_url() . '&rand=" + new Date().getTime();
		 	});
		</script>';
		
		$output .= '<fb:login-button perms="offline_access" width=100></fb:login-button>';
		
		return $output;		
	}
	
	function get_connect_with_account_button() {
		
		$output = '<script src="http://connect.facebook.net/en_US/all.js" type="text/javascript"></script><div id="fb-root"></div>
		
		<script type="text/javascript">
		  	FB.init({appId: ' . $this->client->getAppId() . ', status: true, cookie: true, xfbml: true, session: ' . json_encode( $this->client->getSession() ) . ' });
		  	
		  	//log them for for easy of use
		  	if ( FB._userStatus == "connected" ) {
				FB.logout();
		  	}
		  	
		  	FB.Event.subscribe("auth.login", function() {
		 		document.location = "' . $this->_get_provider_authentication_completed_connect_account_redirect_url() . '";
		 	});
		</script>';
		
		$output .= '<fb:login-button perms="offline_access" width=100></fb:login-button>';
						
		return $output;
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
		}
		
		else {
		
			$access_token_request = 'https://graph.facebook.com/oauth/access_token?client_id=' . $this->api_key . '&redirect_uri=' . $this->_get_provider_authentication_completed_register_redirect_url() . '&client_secret=' . $this->application_secret . '&code=' . $_GET['code'];
			
			$response = wp_remote_get( $access_token_request );
			
			if ( is_wp_error( $response ) ) {
				hm_error_message( 'There was a problem communicating with ' . $this->name . ', please try again.', 'register' );
				return new WP_Error( 'facebook-communication-error' );
			}
			
			if ( $response['response']['code'] == 200 ) {
				
				$args = wp_parse_args( wp_remote_retrieve_body( $response ) );
				$this->access_token = $args['access_token'];
			} else {

				hm_error_message( 'There was a problem communicating with ' . $this->name . ', please try again.', 'register' );
				return new WP_Error( 'facebook-communication-error' );
			
			}
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
	
	function unlink() {
		
		if ( !$this->user() )
			return new WP_Error( 'user-logged-in' );
		
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
	
	function get_wp_user_from_facebook_user( $uid, $access_token = null ) {
		
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
	
	//internal
	
	function _get_oauth_login_url() {
		return 'https://graph.facebook.com/oauth/authorize?client_id=' . $this->api_key . '&redirect_uri=' . $this->_get_oauth_redirect_url();
	}
	
	function check_for_provider_logged_in() {
		
		if ( isset( $_REQUEST['sso_registrar_authorized'] ) && $_REQUEST['sso_registrar_authorized'] == $this->id && $_REQUEST['access_token'] )  {
			$this->access_token = $_REQUEST['access_token'];
		} elseif ( $access_token = $this->get_access_token_from_cookie_session() ) {
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
			$this->log( 'get_access_token_from_cookie_session: ' . '$this->client->getUser() failed for object:' );
			return null;
		}
				
		$session = $this->client->getSession();
		$post_url = 'https://graph.facebook.com/oauth/exchange_sessions?client_id=' . $this->api_key . '&client_secret=' . $this->application_secret . '&sessions=' . $session['session_key'];
		$response = wp_remote_get( $post_url );

		if ( is_wp_error( $response ) ) {
			$this->log( 'get_access_token_from_cookie_session: ' . 'wp_remote_get() failed with wp_error:' );
			$this->log( print_r( $response, true ) );
			return null;
		}
		
		$return = json_decode( wp_remote_retrieve_body( $response ) );
			
		return reset( $return )->access_token;
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
	
	function update_user_access_token() {
		global $current_user;
		$current_user->_fb_access_token = $this->access_token;
		update_user_meta( get_current_user_id(), '_fb_access_token', $this->access_token );
	}
	
	function perform_wordpress_login_from_provider() {
		
		if ( !$this->check_for_provider_logged_in() )
			return new WP_Error( 'no-logged-in-to-facebook' );
		
		global $wpdb;
		
		$fb_uid = $this->client->getUser();
		
		if ( !$fb_uid ) {
			$this->log( 'perform_wordpress_login_from_provider: ' . 'check_for_provider_logged_in() returned true, but client->getUser() returning an empty FB UID' );
		}
		
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
		else
			$userdata['user_login'] = hma_unique_username( $this->user_info['username'] );
		
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
	 	
	 	//set the avatar to their twitter avatar if registration completed
		if ( !is_wp_error( $result ) && is_numeric( $result ) && $this->is_authenticated() ) {
			
			$this->avatar_option = new HMA_Facebook_Avatar_Option( &$this );
			update_user_meta( $result, 'user_avatar_option', $this->avatar_option->service_id );
		}

		return $result;	
	}
	
	function _validate_hma_new_user( $result ) {

		if( is_wp_error( $result ) && $result->get_error_code() == 'invalid-email' )
			return null;
		
		return $result;
	}
	
	public function logout( $redirect ) {
		
		//redirect can be relitive, make it not so
		$redirect = get_bloginfo( 'url' ) . str_replace( get_bloginfo( 'url' ), '', $redirect );
		
		//only redirect to facebook is is logged in with a cookie
		if ( $this->client->getSession() ) {
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