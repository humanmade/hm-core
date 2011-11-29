<?php

/**
 * We use the blogfilter function to define all the page urls
 *
 * @params the name of the page
 * @params 'display'
 * @return string url
 *
 */
function hma_blogfilter( $arg, $arg2 ) {

	switch( $arg2 ) :

		case 'login_url' :

			return apply_filters( 'hma_login_url', home_url( trailingslashit( hma_get_login_rewrite_slug() ) ) );

		break;

		case 'login_inline_url' :

			return apply_filters( 'hma_login_inline_url',  home_url( trailingslashit( hma_get_login_inline_rewrite_slug() ) ) );

		break;

		case 'register_url' :

			return apply_filters( 'hma_register_url',  home_url( trailingslashit( hma_get_register_rewrite_slug() ) ) );

		break;

		case 'register_inline_url' :

			return apply_filters( 'hma_register_inline_url',  home_url( trailingslashit( hma_get_lost_password_inline_rewrite_slug() ) ) );

		break;

		case 'lost_password_url' :

			return apply_filters( 'hma_lost_password_url', home_url( trailingslashit( hma_get_lost_password_rewrite_slug() ) ) );

		break;

		case 'lost_password_inline_url' :

			return apply_filters( 'hma_lot_password_inline_url', home_url( trailingslashit( hma_get_lost_password_inline_rewrite_slug() ) ) );

		break;

		case 'my_profile_url' :

			_deprecated_argument( __FUNCTION__, '2.0', 'Use edit_profile_url instead of my_profile_url' );
			
			return apply_filters( 'hma_my_profile_url', home_url( trailingslashit( hma_get_edit_profile_rewrite_slug() ) ) );
			
		break;

		case 'edit_profile_url' :

			return apply_filters( 'hma_edit_profile_url', home_url( trailingslashit( hma_get_edit_profile_rewrite_slug() ) ) );

		break;

		case 'logout_url' :

			// TODO couldn't this just add action = logout to the current url?
			return add_query_arg( 'action', 'logout', hma_get_login_url() );

		break;

	endswitch;

	return $arg;
}
add_filter( 'bloginfo_url', 'hma_blogfilter', 10, 2 );
add_filter( 'bloginfo', 'hma_blogfilter', 10, 2 );