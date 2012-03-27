<?php

/**
 * Match a rewrite rule to a set of query vars
 * and a template file
 *
 * @param string $rule can contain regex
 * @param string $query
 * @param string $template. (default: null)
 * @param array $args. (default: array())
 * @return null
 */
function hm_add_rewrite_rule( $rule, $query = '', $template = null, $args = array() ) {

	global $hm_rewrite_rules;

	if ( is_array( $rule ) ) {

		$arr 	= $rule;
		$rule 	= isset( $arr['rewrite'] ) ? $arr['rewrite'] : null;
		$template = isset( $arr['template'] ) ? $arr['template'] : null;
		$query 	= isset( $arr['query'] ) ? $arr['query'] : null;
		$args 	= $arr;
	}

	// default to template_directory as base
	if ( $template && strpos( $template, ABSPATH ) !== 0 )
		$template = get_template_directory() . '/' . $template;

	$hm_rewrite_rules[ $rule ] = array( $rule, $query, $template, wp_parse_args( $args ) );

}

/**
 * Remove an existing rewrite rule
 *
 * @param string $rule
 * @return null
 */
function hm_remove_rewrite_rule( $rule ) {

	global $hm_rewrite_rules;

	if ( isset( $hm_rewrite_rules[$rule] ) )
		unset( $hm_rewrite_rules[$rule] );

}

/**
 * Add the custom rewrite rules to the main
 * rewrite rules array
 *
 * @param array $rules
 * @return array $rules
 */
function hm_create_custom_rewrite_rules( $rules ) {

 	// Define the custom permalink structure
 	global $hm_rewrite_rules;

 	$new_rules = array();

 	foreach( (array) $hm_rewrite_rules as $rule )
 		$new_rules[ $rule[0] ] = $rule[1];

 	$rules = array_merge( (array) $new_rules, $rules );

	return $rules;
}
add_filter( 'rewrite_rules_array', 'hm_create_custom_rewrite_rules' );

/**
 * Add the custom public query vars
 *
 * @param string $public_query_vars
 * @return null
 */
function hm_add_custom_page_variables( $public_query_vars ) {

	global $hm_rewrite_rules;

	if ( !isset( $hm_rewrite_rules ) )
		return $public_query_vars;

	// Make any query vars public
	foreach( (array) $hm_rewrite_rules as $rule ) {

		$args = wp_parse_args( $rule[1] );

		foreach( $args as $arg => $val )
			if ( !in_array( $arg, $public_query_vars ) )
				$public_query_vars[] = $arg;

	}

	return $public_query_vars;
}
add_filter( 'query_vars', 'hm_add_custom_page_variables' );

/**
 * Set the current rewrite rule
 *
 * @param object $request
 * @return null
 */
function hm_set_custom_rewrite_rule_current_page( $request ) {

	global $hm_rewrite_rules, $hm_current_rewrite_rule, $wp_rewrite;

	if ( isset( $hm_rewrite_rules ) && array_key_exists( $request->matched_rule, (array) $hm_rewrite_rules ) ) {

		$hm_current_rewrite_rule = $hm_rewrite_rules[$request->matched_rule];
		
		if ( ! empty( $hm_current_rewrite_rule[3]['request_callback'] ) && is_callable( $hm_current_rewrite_rule[3]['request_callback'] ) )
			call_user_func( $hm_current_rewrite_rule[3]['request_callback'], $request );
		
		do_action_ref_array( 'hm_parse_request_' . $request->matched_rule, array( &$request ) );

		$hm_current_rewrite_rule[4] = $request->query_vars;

	}

	if ( isset( $hm_current_rewrite_rule[4] ) && $hm_current_rewrite_rule[4] === $request ) {

		$hm_current_rewrite_rule[3]['parse_query_properties'] = wp_parse_args( ( isset( $hm_current_rewrite_rule[3]['parse_query_properties'] ) ? $hm_current_rewrite_rule[3]['parse_query_properties'] : '' ), array( 'is_home' => false ) );

		// Apply some post query stuff to wp_query
		if ( isset( $hm_current_rewrite_rule[3]['parse_query_properties'] ) ) {

			// $post_query
			foreach( wp_parse_args( $hm_current_rewrite_rule[3]['parse_query_properties'] ) as $property => $value )
				$wp_query->$property = $value;
		}
	}

}
add_filter( 'parse_request', 'hm_set_custom_rewrite_rule_current_page' );

/**
 * Hooks into parse_query to modify any is_* etc properties of WP_Query, specify as $args['parse_query_properties'] in hm_add_rewrite_rule.
 *
 * @return null
 */
function hm_modify_parse_query( $wp_query ) {

	global $hm_rewrite_rules, $hm_current_rewrite_rule, $wp_the_query;

	if ( $wp_the_query != $wp_query )
		return;

	if ( isset( $hm_current_rewrite_rule ) && $hm_current_rewrite_rule[4] === $wp_query->query ) {

		$hm_current_rewrite_rule[3]['parse_query_properties'] = wp_parse_args( ( isset( $hm_current_rewrite_rule[3]['parse_query_properties'] ) ? $hm_current_rewrite_rule[3]['parse_query_properties'] : '' ), array( 'is_home' => false ) );

		// Apply some post query stuff to wp_query
		if ( isset( $hm_current_rewrite_rule[3]['parse_query_properties'] ) ) {

			// $post_query
			foreach( wp_parse_args( $hm_current_rewrite_rule[3]['parse_query_properties'] ) as $property => $value )
				$wp_query->$property = $value;

		}
	}

}
add_filter( 'parse_query', 'hm_modify_parse_query', 9 );

/**
 * Load the template file of the matched rule
 *
 * @param string $template
 * @return string
 */
function hm_load_custom_templates( $template ) {

	global $wp_query, $hm_rewrite_rules, $hm_current_rewrite_rule;

	// Skip 404 template includes
	if ( is_404() && !isset( $hm_current_rewrite_rule[3]['post_query_properties']['is_404'] ) )
		return;

	// Allow 404's to be overridden
	if ( is_404() && isset( $hm_current_rewrite_rule[3]['post_query_properties']['is_404'] ) && $hm_current_rewrite_rule[3]['post_query_properties']['is_404'] == false )
		status_header('200');

	// Show the correct template for the query
	if ( isset( $hm_current_rewrite_rule ) && $hm_current_rewrite_rule[4] === $wp_query->query ) {

		// Apply some post query stuff to wp_query
		if ( isset( $hm_current_rewrite_rule[3]['post_query_properties'] ) )

			// $post_query
			foreach( wp_parse_args( $hm_current_rewrite_rule[3]['post_query_properties'] ) as $property => $value )
				$wp_query->$property = $value;

		if ( !empty( $hm_current_rewrite_rule[2] ) ) {

			do_action( 'hm_load_custom_template', $hm_current_rewrite_rule[2], $hm_current_rewrite_rule );

			if ( empty( $hm_current_rewrite_rule[3]['disable_canonical'] ) && $hm_current_rewrite_rule[1] )
				redirect_canonical();

			include( $hm_current_rewrite_rule[2] );
			exit;

		// Allow redirect_canonical to be disabled
		} else if ( !empty( $hm_current_rewrite_rule[3]['disable_canonical'] ) ) {
			remove_action( 'template_redirect', 'redirect_canonical', 10 );
		}

	}

	return $template;

}
add_action( 'template_redirect', 'hm_load_custom_templates', 1 );

/**
 * Add the custom template filename as a body class
 *
 * @access public
 * @param mixed $classes
 * @return null
 */
function hm_custom_rewrite_rule_body_class( $classes ) {

	global $hm_current_rewrite_rule;

	if ( !empty( $hm_current_rewrite_rule[2] ) )
		$classes[] = sanitize_html_class( end( explode( '/', str_replace( '.php', '', $hm_current_rewrite_rule[2] ) ) ) );

	return $classes;

}
add_filter( 'body_class', 'hm_custom_rewrite_rule_body_class' );

/**
 * TODO Docblock
 *
 * @param array $args
 * @return null
 */
function hm_add_args_to_current_rule( $args ) {

	global $hm_current_rewrite_rule;

	$hm_current_rewrite_rule[3] = array_merge_recursive( $hm_current_rewrite_rule[3], $args );

}

/**
 * Check the permissions for the current rule and redirect as needed
 *
 * Supported permission values are
 *
 *	logged_out_only
 * 	logged_in_only
 *	displayed_user_only => relies on get_query_var( 'author' )
 *
 * @param string $template
 * @param string $rule
 * @return null
 */
function hm_restrict_access_to_rule( $template, $rule ) {

	if ( empty( $rule[3]['permission'] ) )
		return;

	$permission = $rule[3]['permission'];

	$redirect = false;

	switch ( $permission ) {

		case 'logged_out_only' :

			$redirect = is_user_logged_in();

		break;

		case 'logged_in_only' :

			$redirect = ! is_user_logged_in();

		break;

		case 'displayed_user_only' :
			$redirect = ! is_user_logged_in() || get_query_var( 'author' ) != get_current_user_id();

		break;
	}

	if ( ! $redirect )
		return;

    $redirect = home_url( '/' );

	// If there is a "redirect_to" redirect there
	if ( ! empty( $_REQUEST['redirect_to'] ) )
	    $redirect = hm_parse_redirect( urldecode( esc_url( $_REQUEST['redirect_to'] ) ) );

	wp_redirect( $redirect );

	exit;
}
add_action( 'hm_load_custom_template', 'hm_restrict_access_to_rule', 10, 2 );