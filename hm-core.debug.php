<?php

/**
 * Intelligently replacement for print_r & var_dump.
 *
 * @access public
 * @param mixed $code
 * @param bool $output. (default: true)
 * @return $code
 */
function hm( $code, $output = true ) {

	if ( $output ) : ?>

		<style>
			.hm_debug { word-wrap: break-word; white-space: pre; text-align: left; position: relative; background-color: rgba(0, 0, 0, 0.8); font-size: 11px; color: #a1a1a1; margin: 10px; padding: 10px; margin: 0 auto; width: 80%; overflow: auto;  -moz-border-radius: 5px; -webkit-border-radius: 5px; text-shadow: none; }
		</style>

		<br />

		<pre class="hm_debug">

	<?php endif;

	// var_dump everything except arrays and objects
	if ( !is_array( $code ) && !is_object( $code ) ) :

		if ( $output )
			var_dump( $code );

		else
			var_export( $code, true );

	else :

		if ( $output )
			print_r( $code );

		else
			print_r( $code, true );
	endif;

	if ( $output )
		echo '</pre><br />';

	return $code;

}

function hm_backtrace( $limit = 0 ) {

	$new = array();
	$backtrace = debug_backtrace();
	
	array_shift( $backtrace );
	
	foreach( $backtrace as $num => $val ) {
		
		if( $val['function'] == 'do_action' )
			$new[$num] = reset( $val['args'] ) . ' (via do_action)';
		
		else
			$new[$num] = array( 'function' => $val['function'] );
	
		if( !empty( $val['line'] ) )
			$new[$num]['line'] = $val['line'];
	
		if( !empty( $val['file'] ) )
			$new[$num]['file'] = $val['file'];
			
		if( !empty( $val['class'] ) )
			$new[$num]['class'] = $val['class'];
	}
	
	hm($new);
}

/**
 * Intelligently error_log the passed var.
 *
 * @access public
 * @param mixed $code
 * @return null
 */
function hm_log( $code ) {
	error_log( hm( $code, false ) );
}

/**
 * Javascript alert.
 *
 * @access public
 * @param mixed $code
 * @return void
 */
function hm_alert( $code ) {
	echo '<script type="text/javascript"> alert("';
	hm_debug( $code );
	echo '")</script>';
}