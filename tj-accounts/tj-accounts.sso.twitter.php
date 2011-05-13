<?php

class tja_SSO_Twitter extends tja_SSO_Provider {
	
	public $usingSession;
	
	function __construct() {
		
		if( !defined( 'tja_SSO_TWITTER_API_KEY' ) || !defined( 'tja_SSO_TWITTER_CONSUMER_SECRET' ) )
			return new WP_Error( 'constants-not-defined' );
			
		require_once( 'twitterauth/config.php' );
		require_once( 'twitterauth/twitteroauth/twitteroauth.php' );
		
		parent::__construct();
		
		$this->id = 'twitter';
		$this->name = 'Twitter';
		$this->api_key = tja_SSO_TWITTER_API_KEY;
		$this->consumer_secret = tja_SSO_TWITTER_CONSUMER_SECRET;
		$this->sign_in_client = null;
		
		$this->usingSession = true;
		
		if( !isset( $_SESSION ) )
			session_start();
		
		if( $this->is_authenticated_for_current_user() ) {
			$this->access_token = get_user_meta( get_current_user_id(), '_twitter_access_token', true );
			$this->client = new TwitterOAuth( $this->api_key ,  $this->consumer_secret, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
		}
		
		else {
			$this->client = new TwitterOAuth( $this->api_key, $this->consumer_secret );
		}
		
		$this->avatar_option = new tja_Twitter_Avatar_Option( &$this );
	}
	
	function get_login_button() {
	
		$button = new Twitter_Sign_in( $this->client, $this->usingSession );
		
		$output = '
		<script type="text/javascript">
			function TwitterSignInCompleted() {
				parent.jQuery.fancybox.showActivity();
		 		document.location = "' . $this->_get_sso_login_submit_url() . '";
			}
		</script>
		';
		
		$output .= $button->get_login_link();		
				
		return $output;
		
	}
	
	function get_sign_in_client() {
		if( !$this->sign_in_client )
			$this->sign_in_client = new Twitter_Sign_in( $this->client, $this->usingSession );
		return $this->sign_in_client ;
	}
	
	function get_login_button_image() {
		return 'http://a0.twimg.com/images/dev/buttons/sign-in-with-twitter-d.png';
	}
	
	function get_login_open_authentication_js() {
		?>
		<script>
			jQuery( window ).load( function() { jQuery('.sign-in-with-twitter').click() } );
		</script>
		<?php
	}
	
	function get_register_button() {
		
		$button = new Twitter_Sign_in( $this->client, $this->usingSession );
		
		$output = '
		<script type="text/javascript">
			function TwitterSignInCompleted() {
				parent.jQuery.fancybox.showActivity();
		 		document.location = "' . $this->_get_provider_authentication_completed_register_redirect_url() . '";
			}
		</script>
		';
		
		$output .= $button->get_login_link();		
				
		return $output;
	}
	
	function get_connect_with_account_button() {
		$button = new Twitter_Sign_in( $this->client, $this->usingSession );
		
		$output = '
		<script type="text/javascript">
			function TwitterSignInCompleted() {
		 		document.location = "' . $this->_get_provider_authentication_completed_connect_account_redirect_url() . '";
			}
		</script>
		';
		
		$output .= $button->get_login_link();		
				
		return $output;
	}
	
	function check_for_provider_logged_in() {
		
		if( empty( $this->access_token ) )
			$this->access_token = unserialize( base64_decode( $_POST['access_token'] ) );
		
		return $this->access_token;
	}
	
	function get_user_info() {
		
		$this->get_twitter_user_info();
		
		$userdata = array( 
			'user_login'	=> $this->user_info->screen_name,
			'display_name'	=> $this->user_info->name,
			'first_name'	=> reset( explode( ' ', $this->user_info->name ) ),
			'display_name_preference' => 'first_name last_name',
			'_twitter_uid'	=> $this->user_info->id,
		);

		if( count( explode( ' ', $this->user_info->name ) ) > 1 ) {
			$userdata['last_name'] = end( explode( ' ', $this->user_info->name ) );
		}
		
		return $userdata;
	}
	
	function get_twitter_user_info() {
		
		if( empty( $this->user_info ) ) {
			
			$this->client = new TwitterOAuth( $this->api_key ,  $this->consumer_secret, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
			$this->user_info = $this->client->get('account/verify_credentials');
		}
				
		return $this->user_info;
	}
	
	function perform_wordpress_login_from_provider() {
		
		//we are in the popup were (seperate window)
		if( $this->usingSession ) {
			$this->access_token = $_SESSION['twitter_oauth_token'];
			unset( $_SESSION['twitter_oauth_token'] );
		} else {
			$this->access_token = unserialize( base64_decode( $_COOKIE['twitter_oauth_token'] ) );
			setcookie( 'twitter_oauth_token', '', time() - 100, COOKIEPATH );
		}
						
		$info = $this->get_user_info();

		$twitter_uid = $info['_twitter_uid'];
		
		if( !$twitter_uid ) {
			hm_error_message( 'There was a problem verifying your credentials with Twitter, please try again.', 'login' );
			return new WP_Error( 'twitter-authentication-failed' );
		}

		$user_id = $this->_get_user_id_from_sso_id( $twitter_uid );
		
		if( !$user_id ) {
			hm_error_message( 'This Twitter account has not been linked to an account on this site.', 'login' );
			return new WP_Error( 'twitter-account-not-connected' );
		}
		
		wp_set_auth_cookie( $user_id, false );
		set_current_user( $user_id );
		
		do_action( 'tja_log_user_in', $user_id);
		do_action( 'tja_login_submitted_success' );

		
		return true;
	}
	
	function register_sso_submitted( ) {
		
		$this->access_token = unserialize( base64_decode( $_POST['access_token'] ) );

		$result = $this->perform_wordpress_register_from_provider();
		
		if( is_wp_error( $result ) )
			add_action( 'tja_sso_login_connect_provider_with_account_form', array( &$this, 'wordpress_login_and_connect_provider_with_account_form_field' ) );
		
		return $result;
	}
	
	function perform_wordpress_register_from_provider() {
			
		$info = $this->get_user_info();
		
		if( empty( $info['_twitter_uid'] ) ) {
			hm_error_message( 'There was a problem communication with Twitter, please try again.', 'register' );
			return new WP_Error( 'twitter-connection-error' );
		}
		
		$userdata = apply_filters( 'tja_register_user_data_from_sso', $info, &$this );
		
		if( !empty( $_POST['user_login'] ) )
			$userdata['user_login'] = esc_attr( $_POST['user_login'] );
		
		if(  !empty( $_POST['user_email'] ) )
			$userdata['user_email'] = esc_attr( $_POST['user_email'] );
		
		$userdata['override_nonce'] = true;
		$userdata['do_login'] = true;
		$userdata['_twitter_access_token'] = $this->access_token;
		$userdata['do_redirect'] = false;
		$userdata['unique_email'] = true;
		$userdata['send_email'] = true;
		
	 	$result = tja_new_user( $userdata );
		
		//set the avatar to their twitter avatar if registration completed
		if( !is_wp_error( $result ) && is_numeric( $result ) ) {
			$this->avatar_option = new tja_Twitter_Avatar_Option( &$this );
			update_user_meta( $result, 'user_avatar_option', $this->avatar_option->service_id );
		}
		
		return $result;	
	}
	
	function logout_from_provider( $redirect ) {
		
		if( !empty( $_COOKIE['twitter_anywhere_identity'] ) )
			setcookie( 'twitter_anywhere_identity', '', time() - 100, COOKIEPATH );
	}
	
	/**
	 * Gets the access token and fires any errors before showing the Register With This SSO form.
	 * 
	 * @return wp_error || true on success
	 */
	function provider_authentication_register_completed() {

		//we are in the popup were (seperate window)
		if( $this->usingSession ) {
			$this->access_token = $_SESSION['twitter_oauth_token'];
			unset( $_SESSION['twitter_oauth_token'] );
		} else {
			$this->access_token = unserialize( base64_decode( $_COOKIE['twitter_oauth_token'] ) );
			setcookie( 'twitter_oauth_token', '', time() - 100, COOKIEPATH );
		}
		
		$info = $this->get_user_info();

		//Check if this twitter account has already been connected with an account, if so log them in and dont register
		if( !empty( $info['_twitter_uid'] ) && $this->_get_user_id_from_sso_id( $info['_twitter_uid'] ) ) {

			$result = $this->perform_wordpress_login_from_provider();
			do_action( 'tja_sso_register_completed', &$this );
		} elseif( empty( $info['_twitter_uid'] ) ) {
			
			hm_error_message( 'There was a problem communication with Twitter, please try again.', 'register' );
			return new WP_Error( 'twitter-connection-error' );
			
		}
		
		return true;
	}
	
	function provider_authentication_connect_with_account_completed() {
		
		if( !is_user_logged_in() )
			return new WP_Error( 'user-logged-in' );
		
		//we are in the popup were (seperate window)
		if( $this->usingSession && !empty( $_SESSION['twitter_oauth_token'] ) ) {
			$this->access_token = $_SESSION['twitter_oauth_token'];
			unset( $_SESSION['twitter_oauth_token'] );
		} elseif( !empty( $_POST['access_token'] ) ) {
			
			$this->access_token = $this->get_access_token_from_string( $_POST['access_token'] );
		} else {
			$this->access_token = unserialize( base64_decode( $_COOKIE['twitter_oauth_token'] ) );
			setcookie( 'twitter_oauth_token', '', time() - 100, COOKIEPATH );
		}
		
		$info = $this->get_twitter_user_info();

		if( !empty( $info->error ) ) {
			hm_error_message( 'There was a problem connecting you with Twitter, please try again.', 'update-user' );
			return new WP_Error( $info->error );
		}
		
		//Check if this twitter account has already been connected with an account, if so log them in and dont register
		if( $this->_get_user_id_from_sso_id( $info->id ) ) {
			
			hm_error_message( 'This Twitter account is already linked with another account, please try a different one.', 'update-user' );
			return new WP_Error( 'sso-provider-already-linked' );
		}
		
		update_user_meta( get_current_user_id(), '_twitter_access_token', $this->access_token );
		update_user_meta( get_current_user_id(), '_twitter_uid', $info->id );
		
		hm_success_message( 'Successfully connected the Twitter account "' . $info->screen_name . '" with your profile.', 'update-user' );
		
		return true;
	}
	
	function unlink_provider_from_current_user() {
		
		if( !is_user_logged_in() )
			return new WP_Error( 'user-not-logged-in' );
		
		delete_user_meta( get_current_user_id(), '_twitter_uid' );
		delete_user_meta( get_current_user_id(), '_twitter_access_token' );
		
		if( !$this->userSession ) {
			setcookie('twitter_oauth_token', '', time() - 100, COOKIEPATH);
			setcookie('twitter_oauth_token_secret', '', time() - 100, COOKIEPATH);
		}
		
		$this->avatar_option->remove_local_avatar();
		
		setcookie('twitter_anywhere_identity', '', time() - 100, COOKIEPATH);
		
		hm_success_message( 'Successfully unlinked Twitter from your account.', 'update-user' );
		
		return true;
	}
	
	
	function register_form_fields() {
		?>
		<input type="hidden" name="sso_registrar_authorized" value="<?php echo $this->id ?>" />
		<input type="hidden" name="access_token" value="<?php echo $this->get_access_token_string() ?>" />
		<?php
	}
	
	function get_access_token_string() {
		return base64_encode( serialize(  $this->access_token ) );
	}
	
	function get_access_token_from_string( $string ) {
		return unserialize( base64_decode( $string ) );
	}
	
	function is_authenticated_for_current_user() {
		
		if( !is_user_logged_in() )
			return false;
		
		$twitter_uid = get_user_meta( get_current_user_id(), '_twitter_uid', true );
		$access_token = get_user_meta( get_current_user_id(), '_twitter_access_token', true );
		
		if( !$twitter_uid || !$access_token )
			return false;
			
		//TODO: check the access token is still good
		return true;
	}

	
	function _get_at_anywhere_user_cookie() {
		
		if( !isset( $_COOKIE["twitter_anywhere_identity"] ) )
			return null;
		
		$cookie = $_COOKIE["twitter_anywhere_identity"]; 
		
		preg_match("/(.*):(.*)/", $cookie, $matches);
		
		//verify the secret is correct
		if( sha1( $matches[1] . $this->consumer_secret ) != $matches[2] ) 
			return null;
		
		return $matches;
	}
	
	function _get_user_id_from_sso_id( $sso_id ) {
	
		global $wpdb;
		return $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_twitter_uid' AND meta_value = '{$sso_id}'" );
	
	}
	
}

class tja_Twitter_Avatar_Option extends tja_SSO_Avatar_Option {
	
	public $sso_provider;
	
	function __construct( $sso_provider ) {
		$this->sso_provider = $sso_provider;
		
		parent::__construct();
		$this->service_name = "Twitter";
		$this->service_id = "twitter";
	}
	
	function get_avatar( $size = null ) {
		
		if( ( $avatar = get_user_meta( $this->user->ID, '_twitter_avatar', true ) ) && file_exists( $avatar ) ) {
		    $this->avatar_path = $avatar;
		    
		} elseif( $this->sso_provider->is_authenticated_for_current_user() ) {
			$user_info = $this->sso_provider->get_twitter_user_info();
			$image_url = "http://img.tweetimag.es/i/{$user_info->screen_name}_o";
			
			$this->avatar_path = $this->save_avatar_locally( $image_url, 'png' ) ;
			
			// saving teh image failed
			if( !$this->avatar_path )
				return null;
			
			update_user_meta( $this->user->ID, '_twitter_avatar', $this->avatar_path );
		}
		
		return hm_phpthumb_it( $this->avatar_path, $size );
	}
	
	function remove_local_avatar() {
		
		if( !is_user_logged_in() || empty( $this->avatar_path ) )
			return null;
		
		unlink( $this->avatar_path );
		
		delete_user_meta( get_current_user_id(), '_twitter_avatar' );
	}
}

class Twitter_Sign_in {

	public $twitterOAuth;
	public $usingSession;
	
	function __construct( $twitterOAuth, $usingSession = false ) {
		$this->twitterOAuth = $twitterOAuth;
		$this->usingSession = $usingSession;
	}
	
	/**
	 * Gets the login link (include <a>) for the Twitter Sign in window.
	 * 
	 * @return string
	 */
	function get_login_link() {
		
		$output = '
		
		<script type="text/javascript">
			function SignInWithTwitterClicked( e ) {
				window.open("' . $this->get_login_popup_url() . '","Sign In With Twitter","width=800,height=400");
				return false;
			}
		</script>
		
		';
		
		$output .= '<a onclick="return SignInWithTwitterClicked(this);" class="sign-in-with-twitter" href="' . $this->get_login_popup_url() . '"><img alt="Sign In with Twitter" src="http://a0.twimg.com/images/dev/buttons/sign-in-with-twitter-d.png" /></a>';
		
		return $output;
	}
	
	function get_login_popup_url() {
		return add_query_arg( 'sign_in_with_twitter_started', '1', get_bloginfo( 'login_url', 'display' ) );
	}
	/**
	 * Returns the login url of the login window.
	 * 
	 * @return string
	 */
	function get_login_url() {
		return $this->twitterOAuth->getAuthorizeURL( $this->access_token, false );
	}
	
	/**
	 * Shows the blank page after authorization via Twitter is done.
	 * 
	 */
	function login_completed_page( ) {
		
		//get the token that we sent in _twitter_sign_in_completed_hook(), and generate a new access token based of this
		if( $this->usingSession ) {
			
			$oath_token = $_SESSION['twitter_oauth_token'];
			$oath_token_secret = $_SESSION['twitter_oauth_token_secret'];

		} else {
			
			$oath_token = $_COOKIE['twitter_oauth_token'];
			$oath_token_secret = $_COOKIE['twitter_oauth_token_secret'];
		}
		
		$twitterOAuth = new TwitterOAuth( $this->twitterOAuth->consumer->key, $this->twitterOAuth->consumer->secret, $oath_token, $oath_token_secret);
		$access_token = $twitterOAuth->getAccessToken( $_GET['oauth_verifier'] );
		
		//put the new access token into session / cookie, so when the login / register etc picks it up, they are verified
		if( $this->usingSession ) {
			
			if( !isset( $_SESSION ) )
				session_start();
			
			$_SESSION['twitter_oauth_token'] = $access_token;
			$_SESSION['twitter_oauth_token_secret'] = '';
		} else {
			setcookie("twitter_oauth_token", base64_encode( serialize( $access_token ) ), 0, COOKIEPATH);
			setcookie("twitter_oauth_token_secret", '', time()-100, COOKIEPATH);
		}
		
		header("Status: 200");
		
		?>
		<html>
			<body>
				<script type="text/javascript">
					//call the parent window
					if( typeof window.opener.TwitterSignInCompleted != 'undefined' ) {
						
						window.opener.TwitterSignInCompleted();
					
					}
					
					window.close();
				</script>
			</body>
		</html>
		<?php
	
	}
	
	/**
	 * Gets the callback url after twitter has authnticated.
	 * 
	 * @access private
	 * @return string
	 */
	function _get_oauth_redirect_url() {
		return wp_nonce_url( add_query_arg( 'session', (int) $this->usingSession, add_query_arg( 'sign_in_with_twitter_authorized', '1', get_bloginfo( 'register_url', 'display' ) ) ), 'sign_in_with_twitter_authorized'  );
	}
}

function _twitter_sign_in_completed_hook() {
	if( isset( $_GET['sign_in_with_twitter_authorized'] ) && $_GET['sign_in_with_twitter_authorized'] === '1' ) {
			
		$twitter_sso = new tja_SSO_Twitter();
		$twitter_sign_in = new Twitter_Sign_in( $twitter_sso->client );
		
		if( isset( $_GET['session'] ) ) {
			$twitter_sign_in->usingSession = (bool) $_GET['session'];
		}
		
		$twitter_sign_in->login_completed_page();
		
		exit;
	}
}
add_action( 'init', '_twitter_sign_in_completed_hook', 0 );

function _twitter_sign_in_start_hook() {
	if( isset( $_GET['sign_in_with_twitter_started'] ) ) {
			
		$twitter_sso = new tja_SSO_Twitter();
		
		//generate a new access token
		$twitter_sso->get_sign_in_client()->access_token = $twitter_sso->get_sign_in_client()->twitterOAuth->getRequestToken( $twitter_sso->get_sign_in_client()->_get_oauth_redirect_url() );
	
		//User $_SESSION instead of cookie - more secure, less flexible for 2+ servers
		// store the access token in session / cookie, as we need the it when teh redirect finished
		if( $twitter_sso->usingSession ) {
			
			if( !isset( $_SESSION ) )
				session_start();
				
			$_SESSION['twitter_oauth_token'] = $twitter_sso->get_sign_in_client()->access_token['oauth_token'];
			$_SESSION['twitter_oauth_token_secret'] = $twitter_sso->get_sign_in_client()->access_token['oauth_token_secret'];
		} else {
			@setcookie('twitter_oauth_token', $twitter_sso->get_sign_in_client()->access_token['oauth_token'], 0, COOKIEPATH);
			@setcookie('twitter_oauth_token_secret', $twitter_sso->get_sign_in_client()->access_token['oauth_token_secret'], 0, COOKIEPATH);
		}
		
		wp_redirect( $twitter_sso->get_sign_in_client()->get_login_url() );
		exit;
	}
}
add_action( 'init', '_twitter_sign_in_start_hook', 0 );