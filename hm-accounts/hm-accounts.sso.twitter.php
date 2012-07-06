<?php

class HMA_SSO_Twitter extends HMA_SSO_Provider {
	
	public $usingSession;
	
	function __construct() {
		
		if ( !defined( 'HMA_SSO_TWITTER_API_KEY' ) || !defined( 'HMA_SSO_TWITTER_CONSUMER_SECRET' ) )
			return new WP_Error( 'constants-not-defined' );
			
		require_once( 'twitterauth/config.php' );
		require_once( 'twitterauth/twitteroauth/twitteroauth.php' );
		
		parent::__construct();
		
		$this->id = 'twitter';
		$this->name = 'Twitter';
		$this->api_key = HMA_SSO_TWITTER_API_KEY;
		$this->consumer_secret = HMA_SSO_TWITTER_CONSUMER_SECRET;
		$this->sign_in_client = null;
		$this->supports_publishing = true;
		$this->set_user( wp_get_current_user() );
		
		$this->usingSession = true;
		
		if ( !isset( $_SESSION ) )
			session_start();
		
		$this->avatar_option = new HMA_Twitter_Avatar_Option( $this );

		parent::__construct();
	}
	
	public function set_user( $user ) {
		
		parent::set_user( $user );
		
		if ( $this->is_authenticated() ) {
			$this->access_token = get_user_meta( $this->user->ID, '_twitter_oauth_token', true );
			$this->client = new TwitterOAuth( $this->api_key ,  $this->consumer_secret, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
		} else {
			$this->client = new TwitterOAuth( $this->api_key, $this->consumer_secret );
		}
		
	}
	
	public function get_access_token() {
	
		return $this->user ? get_user_meta( $this->user->ID, '_twitter_access_token', true ) : null;
	
	}
	
	public function get_sign_in_client() {
		if ( !$this->sign_in_client )
			$this->sign_in_client = new Twitter_Sign_in( $this->client, $this->usingSession );

		return $this->sign_in_client ;
	}
			
	function check_for_provider_logged_in() {
		
		if ( empty( $this->access_token ) )
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
			'description'	=> $this->user_info->description
		);

		if ( count( explode( ' ', $this->user_info->name ) ) > 1 ) {
			$userdata['last_name'] = end( explode( ' ', $this->user_info->name ) );
		}
		
		return $userdata;
	}
	
	function get_twitter_user_info() {
		
		if ( empty( $this->user_info ) ) {
			
			$this->client = new TwitterOAuth( $this->api_key ,  $this->consumer_secret, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
			$this->user_info = $this->client->get('account/verify_credentials');
		}
				
		return $this->user_info;
	}
	
	function get_user_for_access_token() {
	
		if( !$this->access_token )
			return false;
		
		global $wpdb;
		
		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_twitter_oauth_token_secret' AND meta_value = '" . $this->access_token['oauth_token_secret'] . "'" );
		
		return $user_id;
	}
	
	public function login( $details = array() ) {
		
		$details = wp_parse_args( $details, array( 'remember' => false ) );

		//we are in the popup were (seperate window)
		if ( $this->usingSession ) {
			$this->access_token = $_SESSION['twitter_oauth_token'];
			unset( $_SESSION['twitter_oauth_token'] );
		} else {
			$this->access_token = unserialize( base64_decode( $_COOKIE['twitter_oauth_token'] ) );
			setcookie( 'twitter_oauth_token', '', time() - 100, COOKIEPATH );
		}
						
		$info = $this->get_user_info();
		$twitter_info = $this->user_info();

		$twitter_uid = $info['_twitter_uid'];
		
		if ( !$twitter_uid ) {
			return new WP_Error( 'twitter-authentication-failed', 'There was a problem verifying your credentials with Twitter, please try again.' );
		}

		$user_id = $this->_get_user_id_from_sso_id( $twitter_uid );
		
		// If we don't have their twitter_uid, try twitter handle
		if( ! $user_id )
			$user_id = $this->_get_user_id_from_sso_id( $twitter_info['screen_name'] );
		
		if ( ! $user_id ) {
			return new WP_Error( 'twitter-account-not-connected', 'This Twitter account has not been linked to an account on this site.' );
		}
		
		$user = get_userdata( $user_id );
		$token = $this->access_token;
	 	$this->set_user( $user );
	 	$this->access_token = $token;
	 	
		$this->update_user_twitter_information();
		
		wp_set_auth_cookie( $user_id, $details['remember'] );
		wp_set_current_user( $user_id );
		
		do_action( 'hma_log_user_in', $user_id);
		do_action( 'hma_log_user_in_via_twitter', $user_id);
		do_action( 'hma_login_submitted_success' );
		do_action( 'wp_login', get_userdata( $user_id )->user_login );
		
		return true;
	}
	
	public function register() {
		
		// Check if the SSO has already been registered with a WP account, if so then login them in and be done
		if ( ( $result = $this->login() ) && ! is_wp_error( $result ) ) {
			return $result;
		}
		
		if ( $this->usingSession && !empty( $_SESSION['twitter_oauth_token'] ) ) {
			$this->access_token = $_SESSION['twitter_oauth_token'];
			unset( $_SESSION['twitter_oauth_token'] );
		} elseif ( !empty(  $_COOKIE['twitter_oauth_token'] ) ) {
			$this->access_token = unserialize( base64_decode( $_COOKIE['twitter_oauth_token'] ) );
			setcookie( 'twitter_oauth_token', '', time() - 100, COOKIEPATH );
		}
		
		$_info = $this->get_twitter_user_info();

		$info = $this->get_user_info();
		
		if ( empty( $info['_twitter_uid'] ) ) {
			return new WP_Error( 'twitter-connection-error', 'There was a problem communication with Twitter, please try again.' );
		}

		$userdata['user_login'] = hma_unique_username( $this->user_info->screen_name );
		$userdata['do_login'] = true;
		$userdata['_twitter_access_token'] = $this->access_token;
		$userdata['_twitter_oauth_token'] = $this->access_token['oauth_token'];
		$userdata['_twitter_oauth_token_secret'] = $this->access_token['oauth_token_secret'];
		$userdata['unique_email'] = false;
		$userdata['location'] = $_info->location;
		$userdata['_twitter_data'] = (array) $_info;
		$userdata['first_name'] = $_info->name ? $_info->name : $_info->screen_name;
		$userdata['display_name_preference'] = 'first_name last_name';
		$userdata['display_name'] = $userdata['first_name'];

		$this->set_registration_data( array_merge( $this->registration_data, $userdata ) );

	 	$result = parent::register();
	 	
	 	// Set_user() will wide access token
	 	$token = $this->access_token;
	 	$user = get_userdata( $result );
	 	$this->set_user( $user );
	 	$this->access_token = $token;
	 	
	 	$this->update_user_twitter_information();
 		
		//set the avatar to their twitter avatar if registration completed
		if ( !is_wp_error( $result ) && is_numeric( $result ) ) {
			$this->avatar_option = new HMA_Twitter_Avatar_Option( $this );
			update_user_meta( $result, 'user_avatar_option', $this->avatar_option->service_id );
		}
		
		wp_set_auth_cookie( $user->ID, false );
		wp_set_current_user( $user->ID );
		
		do_action( 'hma_registered_user_via_twitter', $user );

		do_action( 'hma_sso_register_attempt_completed', $this, $result );
		
		return $result;	
	}
	
	public function update_user_twitter_information() {
		
		$info = $this->user_info();
		
		update_user_meta( $this->user->ID, '_twitter_uid', $info['id'] );
		update_user_meta( $this->user->ID, 'twitter_username', $info['screen_name'] );		
		update_user_meta( $this->user->ID, '_twitter_oauth_token', $this->access_token );
		update_user_meta( $this->user->ID, '_twitter_access_token', $this->access_token['oauth_token'] );
		update_user_meta( $this->user->ID, '_twitter_oauth_token_secret', $this->access_token['oauth_token_secret'] );
		update_user_meta( $this->user->ID, '_twitter_data', $info );
	}
	
	/**
	 * Link the current Twitter with $this->user
	 * 
	 * @access public
	 * @return true | WP_Error on failure
	 */
	function link() {
		
		if ( !is_user_logged_in() )
			return new WP_Error( 'user-logged-in' );
		
		//we are in the popup were (seperate window)
		if ( $this->usingSession && !empty( $_SESSION['twitter_oauth_token'] ) ) {
			$this->access_token = $_SESSION['twitter_oauth_token'];
		} elseif ( !empty( $_POST['access_token'] ) ) {
			
			$this->access_token = $this->get_access_token_from_string( $_POST['access_token'] );
		} else {
			$this->access_token = unserialize( base64_decode( $_COOKIE['twitter_oauth_token'] ) );
			setcookie( 'twitter_oauth_token', '', time() - 100, COOKIEPATH );
		}
		
		$info = $this->get_twitter_user_info();

		if ( !empty( $info->error ) ) {
			hm_error_message( 'There was a problem connecting you with Twitter, please try again.', 'update-user' );
			return new WP_Error( $info->error );
		}
		
		//Check if this twitter account has already been connected with an account, if so log them in and dont register
		if ( $this->_get_user_id_from_sso_id( $info->id ) ) {
			
			hm_error_message( 'This Twitter account is already linked with another account, please try a different one.', 'update-user' );
			return new WP_Error( 'sso-provider-already-linked' );
		}
		
		update_user_meta( get_current_user_id(), '_twitter_oauth_token', $this->access_token['oauth_token'] );
		update_user_meta( get_current_user_id(), '_twitter_oauth_token_secret', $this->access_token['oauth_token_secret'] );
		
		update_user_meta( get_current_user_id(), '_twitter_access_token', $this->access_token );
		update_user_meta( get_current_user_id(), '_twitter_uid', $info->id );
		
		hm_success_message( 'Successfully connected the Twitter account "' . $info->screen_name . '" with your profile.', 'update-user' );
		
		do_action( 'user_linked_twitter_account', $this );

		return true;
	}
	
	public function unlink() {
		
		if ( !$this->user->ID )
			return new WP_Error( 'user-not-logged-in' );
		
		delete_user_meta( $this->user->ID, '_twitter_uid' );
		delete_user_meta( $this->user->ID, '_twitter_access_token' );
		delete_user_meta( $this->user->ID, '_twitter_oauth_token' );
		delete_user_meta( $this->user->ID, '_twitter_oauth_token_secret' );	
		delete_user_meta( $this->user->ID, '_twitter_data' );
		delete_user_meta( $this->user->ID, 'twitter_username' );
		
		if ( !$this->usingSession ) {
			setcookie('twitter_oauth_token', '', time() - 100, COOKIEPATH);
			setcookie('twitter_oauth_token_secret', '', time() - 100, COOKIEPATH);
		}
		
		$this->avatar_option->remove_local_avatar();
		
		setcookie('twitter_anywhere_identity', '', time() - 100, COOKIEPATH);
		
		hm_success_message( 'Successfully unlinked Twitter from your account.', 'update-user' );
		
		return true;
	}
	
	function get_access_token_string() {
		return base64_encode( serialize(  $this->access_token ) );
	}
	
	function get_access_token_from_string( $string ) {
		return unserialize( base64_decode( $string ) );
	}
	
	public function is_access_token_valid() {
	
		$client = new TwitterOAuth( $this->api_key ,  $this->consumer_secret, $this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
		$user_info = $client->get('account/verify_credentials');
		
		if( $user_info->error || empty( $user_info->id ) )
			return false;
		
		return true;

	}
	
	public function is_authenticated() {
		if ( !$this->user )
			return false;
		
		$twitter_uid = get_user_meta( $this->user->ID, '_twitter_uid', true );
		$access_token = get_user_meta( $this->user->ID, '_twitter_access_token', true );
		
		if ( !$twitter_uid || !$access_token )
			return false;
			
		//TODO: check the access token is still good
		return true;
	}
	
	public function user_info() {
		
		if( $data = get_user_meta( $this->user->ID, '_twitter_data', true ) )
			return (array) $data;
		
		$data = (array) $this->get_twitter_user_info();
		
		update_user_meta( $this->user->ID, '_twitter_data', $data );
		
		return $data;
	}
	
	public function get_user_for_uid( $sso_id ) {
		return $this->_get_user_id_from_sso_id( $sso_id );
	}
	
	function _get_user_id_from_sso_id( $sso_id ) {
		global $wpdb;
		
		if( $sso_id && $var = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE ( meta_key = '_twitter_uid' OR meta_key = 'twitter_username' ) AND meta_value = '{$sso_id}'" ) ) {
			return $var;
		}
		
		return null;
	}

	public function get_twitter_following() {

		$twitter_uid = get_user_meta( $this->user->ID, '_twitter_uid', true );
		$following = (array) $this->client->get( '/friends/ids', array( 'user_id' => $twitter_uid ) )->ids;

		return $following;
	}
	
	public function get_login_popup_url() {

		return $this->get_sign_in_client()->get_login_popup_url();
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

		// TODO Trim to 140 (excluding links)	
		if ( !empty( $data['link_url'] ) )
			$tweet = $data['message'] . ' | ' . $data['link_url'];
		else
			$tweet = $data['message'];
		
		return $this->client->post('statuses/update', array('status' => $tweet, 'wrap_links' => true, 'short_url_length' => 19 ) );

	}
}

class HMA_Twitter_Avatar_Option extends HMA_SSO_Avatar_Option {
	
	public $sso_provider;
	
	function __construct( $sso_provider ) {
		$this->sso_provider = $sso_provider;
		
		parent::__construct();
		$this->service_name = "Twitter";
		$this->service_id = "twitter";
	}
	
	function set_user( $user ) {
		parent::set_user( $user );
		$this->sso_provider->set_user( $user );
	}
	
	function get_avatar( $size = null ) {
		
		$this->avatar_path = null;
		
		if ( ( $avatar = get_user_meta( $this->user->ID, '_twitter_avatar', true ) ) && file_exists( $avatar ) ) {
		    $this->avatar_path = $avatar;
		    
		} elseif ( $this->sso_provider->is_authenticated() ) {
			$user_info = $this->sso_provider->user_info();

			if ( empty( $user_info['screen_name'] ) )
				return null;
				
			$image_url = "http://api.twitter.com/1/users/profile_image?screen_name={$user_info['screen_name']}&size=original";

			$this->avatar_path = $this->save_avatar_locally( $image_url, 'png' ) ;
			
			// saving teh image failed
			if ( ! $this->avatar_path )
				return null;
			
			update_user_meta( $this->user->ID, '_twitter_avatar', $this->avatar_path );
		}
		
		$img =  wpthumb( $this->avatar_path, $size );

		return $img;
	}
	
	function remove_local_avatar() {
		
		if ( !is_user_logged_in() || empty( $this->avatar_path ) )
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
		</script>';
		
		$output .= '<a onclick="return SignInWithTwitterClicked(this);" class="sign-in-with-twitter" href="' . $this->get_login_popup_url() . '"><img alt="Sign In with Twitter" src="http://a0.twimg.com/images/dev/buttons/sign-in-with-twitter-d.png" /></a>';
		
		return $output;
	}
	
	function get_login_popup_url() {
		return get_bloginfo( 'login_url', 'display' ) . 'sso/twitter/authenticate/';
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
		if ( $this->usingSession ) {
			
			$oath_token = $_SESSION['twitter_oauth_token'];
			$oath_token_secret = $_SESSION['twitter_oauth_token_secret'];

		} else {
			
			$oath_token = $_COOKIE['twitter_oauth_token'];
			$oath_token_secret = $_COOKIE['twitter_oauth_token_secret'];
		}
		
		$twitterOAuth = new TwitterOAuth( $this->twitterOAuth->consumer->key, $this->twitterOAuth->consumer->secret, $oath_token, $oath_token_secret);
		$access_token = $twitterOAuth->getAccessToken( $_GET['oauth_verifier'] );
		
		//put the new access token into session / cookie, so when the login / register etc picks it up, they are verified
		if ( $this->usingSession ) {
			
			if ( !isset( $_SESSION ) )
				session_start();
			
			$_SESSION['twitter_oauth_token'] = $access_token;
			$_SESSION['twitter_oauth_token_secret'] = '';
		} else {
			setcookie("twitter_oauth_token", base64_encode( serialize( $access_token ) ), 0, COOKIEPATH);
			setcookie("twitter_oauth_token_secret", '', time()-100, COOKIEPATH);
		}

		$user_info = $twitterOAuth->get('account/verify_credentials');
		
		header("Status: 200");
		
		?>
		<html>
			<body>
				<script type="text/javascript">
					//call the parent window
					if ( typeof window.opener.TwitterSignInCompleted != 'undefined' ) {
						
						window.opener.TwitterSignInCompleted( <?php echo json_encode( $user_info ) ?> );
					
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
		return add_query_arg( 'session', (int) $this->usingSession, get_bloginfo( 'login_url', 'display' ) . 'sso/twitter/authenticate/callback/' );
	}
}

function _twitter_sign_in_completed_hook() {

	$twitter_sso = new HMA_SSO_Twitter();
	$twitter_sign_in = new Twitter_Sign_in( $twitter_sso->client );
	
	if ( isset( $_GET['session'] ) ) {
	    $twitter_sign_in->usingSession = (bool) $_GET['session'];
	}
	
	$twitter_sign_in->login_completed_page();
	
	exit;
}
add_action( 'hm_parse_request_^login/sso/twitter/authenticate/callback/?$', '_twitter_sign_in_completed_hook' );


function _twitter_sign_in_start_hook() {
			
	$twitter_sso = new HMA_SSO_Twitter();
	
	//generate a new access token
	$twitter_sso->get_sign_in_client()->access_token = $twitter_sso->get_sign_in_client()->twitterOAuth->getRequestToken( $twitter_sso->get_sign_in_client()->_get_oauth_redirect_url() );
	
	//User $_SESSION instead of cookie - more secure, less flexible for 2+ servers
	// store the access token in session / cookie, as we need the it when teh redirect finished
	if ( $twitter_sso->usingSession ) {
	    
	    if ( !isset( $_SESSION ) )
	    	session_start();
	    	
	    $_SESSION['twitter_oauth_token'] = $twitter_sso->get_sign_in_client()->access_token['oauth_token'];
	    $_SESSION['twitter_oauth_token_secret'] = $twitter_sso->get_sign_in_client()->access_token['oauth_token_secret'];
	} else {
	    @setcookie('twitter_oauth_token', $twitter_sso->get_sign_in_client()->access_token['oauth_token'], 0, COOKIEPATH);
	    @setcookie('twitter_oauth_token_secret', $twitter_sso->get_sign_in_client()->access_token['oauth_token_secret'], 0, COOKIEPATH);
	}
	
	wp_redirect( $twitter_sso->get_sign_in_client()->get_login_url(), 303 );
	exit;
}
add_action( 'hm_parse_request_^login/sso/twitter/authenticate/?$', '_twitter_sign_in_start_hook' );

/**
 * _twitter_add_username_to_editprofile_contact_info function.
 * 
 * @access private
 * @param mixed $methods
 * @param mixed $user
 * @return null
 */
function _twitter_add_username_to_editprofile_contact_info( $methods, $user ) {

	if( empty( $methods['twitter_username'] ) )
		$methods['twitter_username'] = __( 'Twitter Username' );
	
	return $methods;

}
add_filter( 'user_contactmethods', '_twitter_add_username_to_editprofile_contact_info', 10, 2 );
