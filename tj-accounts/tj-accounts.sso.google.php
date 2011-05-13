<?php

class tja_SSO_Google extends tja_SSO_Provider {

	function __construct() {
	
		parent::__construct();
		
		$this->id = 'facebook';
		$this->name = 'Facebook';
		$this->site_id = "13920072626782857380";
							
	}
	
	function get_login_button() {
		
		$script = '
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("friendconnect", "0.8");
		</script>
		<script type="text/javascript">
		  	google.friendconnect.container.loadOpenSocialApi({
		    	site: "' . $this->site_id . '",
		    	onload: function(securityToken) {
		    		google.friendconnect.renderSignInButton({ "id": "gfc-button" });
		    		
		    		if (!window.timesloaded) {
    				  window.timesloaded = 1;
    				} else {
    				  window.timesloaded++;
    				}

		    		if (window.timesloaded > 1) {
      					document.getElementById("loginform-tml-main").submit();
    				}
		    	}
		  	});
		  	
		</script>
		
		<div id="gfc-button"></div>';
		
		return $script;
	
	}
	
	function get_register_button() {
		
		$script = '
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("friendconnect", "0.8");
		</script>
		<script type="text/javascript">
		  	google.friendconnect.container.loadOpenSocialApi({
		    	site: "' . $this->site_id . '",
		    	onload: function(securityToken) {
		    		google.friendconnect.renderSignInButton({ "id": "gfc-button" });
		    		
		    		if (!window.timesloaded) {
    				  window.timesloaded = 1;
    				} else {
    				  window.timesloaded++;
    				}

		    		if (window.timesloaded > 1) {
      					document.getElementById("adduser").submit();
    				}
		    	}
		  	});
		  	
		</script>
		
		<div id="gfc-button"></div>';
		
		return $script;
	
	}

	function check_for_provider_logged_in() {
		return (bool) $this->get_login_cookie();		
	}
	
	function get_login_cookie() {
		
		if( empty( $_COOKIE[ 'fcauth' . $this->site_id ] ) )
			return null;
		
		return $_COOKIE[ 'fcauth' . $this->site_id ];
	}
	
	function perform_wordpress_login_from_provider() {
		
		if( !$this->get_session() )
			return null;
		
		//get the user's details for login etc
		$userinfo = $this->make_request();
		
		if( empty( $userinfo ) )
			return null;
		
		global $wpdb;
		
		$fc_uid = $userinfo->entry->id;
		
		$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_fc_uid' AND meta_value = '{$fc_uid}'" );

		if( !$user_id )
			return null;
		
		wp_set_auth_cookie( $user_id, false );
		set_current_user( $user_id );
		
		do_action( 'tja_log_user_in', $user_id);
		
		return true;
		
	}
	
	function perform_wordpress_register_from_provider() {
		
		parent::perform_wordpress_register_from_provider();
		
		
		//get the user's details for login etc
		$userinfo = $this->make_request();
		
		if( empty( $userinfo ) )
			return null;

		$userdata = array( 
			'user_login'	=> sanitize_title( $userinfo->entry->displayName ),
			'first_name' 	=> reset( explode( ' ', $userinfo->entry->displayName ) ),
			'last_name'		=> end( explode( ' ', $userinfo->entry->displayName ) ),
			'display_name'	=> $userinfo->entry->displayName,
		);
		
		$userdata = apply_filters( 'tja_register_user_data_from_sso', $userdata, $fb_profile_data, &$this );
		
		$userdata['_fc_uid'] = $userinfo->entry->id;
		$userdata['override_nonce'] = true;
		$userdata['do_login'] = true;
		
		//Facebook will not give the email address if the Facebook App has not been approved
		if( empty( $userdata['user_email'] ) )
			$userdata['user_email'] = $userdata['user_login'] . '@no-email.com';
		
		tja_new_user( $userdata );
		
		return true;

	}
	
	function logged_out_js() {
		?>
		
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("friendconnect", "0.8");
		</script>
		<script type="text/javascript">
		  	google.friendconnect.container.loadOpenSocialApi({
		    	site: "<?php echo $this->site_id ?>",
		    	onload: function(securityToken) {
		    		if (!window.timesloaded) {
		    			google.friendconnect.requestSignOut();
		    		}
		    	}
		  	});
		  	
		</script>
		
		<?php
		
	}
	
	function get_session() {
		return $this->get_login_cookie();
	}
	
	function make_request( $params = null ) {
		
		$url = 'http://www.google.com/friendconnect/api/people/@me/@self';
		$url = add_query_arg( 'fcauth', $this->get_session(), $url );
		
		$response = wp_remote_get( $url );
		
		if( is_wp_error( $response ) ) 
			return null;
		
		return json_decode( wp_remote_retrieve_body( $response ) );
	}
	
}
