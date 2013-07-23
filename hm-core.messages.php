<?php

/**
 * Add a new error message
 * 
 * @param string $message
 * @param string $context. (default: '')
 * @return null
 */
function hm_error_message( $message, $context = '' ) {
	hm_add_message( $message, $context, 'error' );
}

/**
 * Add a new success message
 * 
 * @param string $message
 * @param string $context. (default: '')
 * @return null
 */
function hm_success_message ( $message, $context = '' ) {
	hm_add_message( $message, $context, 'success' );;
}

/**
 * Add a new message to the message stack
 *
 * Stores the messages in a cookie and in a global array so they
 * are available in scope and also on subsequent page loads. The act of showing
 * the message removes it.
 * 
 * @param string $message
 * @param string $context. (default: 'all')
 * @param string $type. (default: 'success')
 * @return null
 */
function hm_add_message( $message, $context = null, $type = 'success' ) {

	global $hm_messages;
	
	$hm_messages = hm_get_message_stack();
	
	$hm_messages[$context][] = array( 'message' => $message, 'type' => $type );

	do_action( 'hm_message_added', $message, $type );

	setcookie( 'hm_messages', json_encode( $hm_messages ), 0, COOKIEPATH );

}

/**
 * Get the current stack of messages
 * 
 * @param string $context. (default: null)
 * @param bool $clear_message. (default: true) whether to clear the on display
 * @return null
 */
function hm_get_messages( $context = null, $clear_message = true ) {

	global $hm_messages;
	
	$hm_messages = hm_get_message_stack();

	$all_messages = array();
	
	// Show messages for a specific context
	if ( ! empty( $context ) ) {
		
		if ( isset( $hm_messages[$context] ) )
			$all_messages = $hm_messages[$context];

		if ( $clear_message )
			unset( $hm_messages[$context] );
	
	// Show all messages
	} else {
		
		foreach( $hm_messages as $context_messages )
			$all_messages = array_merge( (array) $all_messages, (array) $context_messages );
		
		if ( $clear_message )
			$hm_messages = '';
	}
	
	if ( $clear_message )
		add_action( 'wp_footer', 'hm_setcookie_js' );

	return $all_messages;
}

/**
 * Get the messages as a html string
 * 
 * @param string $context. (default: null)
 * @param string $classes. (default: null)
 * @return string html
 */
function hm_get_the_messages( $context = null, $classes = null ) {

	$messages = hm_get_messages( $context );

	$output = '';

	if ( is_array( $messages ) )
	  foreach( $messages as $message )
			$output .= '<div id="message" class="message ' . $message['type'] . ' ' . $classes . ' updated"><p>' . $message['message'] . '</p></div>';

	return $output;

}

/**
 * Empty the message stack
 * 
 * @access public
 * @param string $context. (default: null)
 * @return null
 */
function hm_clear_messages( $context = null ) {
	hm_get_messages( $context, true );
}

/**
 * Display the current messages
 * 
 * @param string $context. (default: null)
 * @param string $classes. (default: null)
 * @return null
 */
function hm_the_messages( $context = null, $classes = null ) {
	echo hm_get_the_messages( $context, $classes );
}

/**
 * Get the current message stack, attempts to fetch them from
 * the $hm_messages global first, falls back to cookie if thats null
 * 
 * @return array $hm_messages;
 */
function hm_get_message_stack() {

	global $hm_messages;

	if ( is_null( $hm_messages ) ) {
		$cookie = ( !empty( $_COOKIE['hm_messages'] ) ) ? $_COOKIE['hm_messages'] : '';
		$hm_messages = array_filter( (array) json_decode( stripslashes( $cookie ), true ) );
	}
	
	return (array)$hm_messages;

}

function hm_setcookie_js( $hm_messages ) {

	global $hm_messages;
	
	$cookie = json_encode( $hm_messages ); ?>
	
	<script type="text/javascript">
		document.cookie = 'hm_messages=<?php echo $cookie; ?>; path=<?php echo COOKIEPATH; ?>';
	</script>
	
<?php }