<?php

add_filter( 'bloginfo_url', 'hma_blogfilter', 10, 2 ); 
add_filter( 'bloginfo', 'hma_blogfilter', 10, 2 ); 

/**
 * We use the blogfilter function to define all the page urls and category mappings
 *
 * @Params the name of the page
 * @Params 'display'
 * @return string - the url or category name
 *
 **/
function hma_blogfilter( $arg, $arg2 ) {

	global $current_user;
	
	switch( $arg2 ) :
							
		case 'login_url' :
			return apply_filters( 'hma_login_url', get_bloginfo( 'url' ) . '/login/' );
			break;
				
		case 'register_url' :
			return get_bloginfo( 'url' ) . '/register/';
			break;
			
		case 'register_inline_url' :
			return get_bloginfo( 'url' ) . '/register-inline/';
			break;

		case 'login_inline_url' :
			return get_bloginfo( 'url' ) . '/login-inline/';
			break;

		case 'lost_password_url' :
			return apply_filters( 'hma_lost_password_url', get_bloginfo( 'url' ) . '/login/lost-password/' );
			break;

		case 'lost_password_inline_url' :
			return get_bloginfo( 'url' ) . '/login/lost-password-inline/';
			break;

		case 'my_profile_url' :
			return apply_filters( 'hma_my_profile_url', get_bloginfo( 'url' ) . '/profile/' );
			break;
		case 'logout_url' :
			return add_query_arg( 'action', 'logout', get_bloginfo('url') );
			
	endswitch;
	
	return $arg;
}
