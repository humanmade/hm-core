<?php

/**
 * Display the login error message
 *
 * @todo duplicate of hma_get_the_message()?
 * @return void
 */
function hma_login_message() {

	if ( empty( $_GET['message'] ) )
		return;

	echo '<p class="message error">' . hma_get_message( (int) $_GET['message'] ) . '</p>' . "\n";
}

/**
 * Display the register error message
 *
 * @todo duplicate of hma_get_the_message()?
 * @return void
 */
function hma_register_message() {

	if ( empty( $_GET['message'] ) )
		return;

	echo '<p class="message error">' . hma_get_message( (int) $_GET['message'] ) . '</p>' . "\n";
}

/**
 * Return the message associated with a message code
 *
 * @param int $code. (default: null)
 * @return string
 */
function hma_get_message( $code = null ) {

	if ( is_null( $code ) )
		$code = (int) $_GET['message'];

	$codes = hma_message_codes();

	return $codes[$code];

}

/**
 * Display the message associated with a message code
 *
 * @param int $code. (default: null)
 * @return string
 */
function hma_get_the_message() {

	if ( empty( $_GET['message'] ) )
		return;

	echo '<p class="message error">' . hma_get_message( (int) $_GET['message'] ) . '</p>' . "\n";
}

/**
 * A list of error code and associated messages
 *
 * @return array
 */
function hma_message_codes() {

	$codes[101] = 'You are already logged in.';
	$codes[102] = 'Please enter a username.';
	$codes[103] = 'The username you entered has not been recognised.';
	$codes[104] = 'The password you entered is incorrect.';
	$codes[105] = 'Successfully logged in';

	$codes[200] = 'Successfully registered';
	$codes[201] = 'You are already logged in.';
	$codes[202] = 'Sorry, that username already exists.';
	$codes[203] = 'The passwords you entered do not match.';
	$codes[204] = 'The email address you entered is not valid';
	$codes[205] = 'The email address you entered is already in use.';
	$codes[206] = 'You have been sent an activation email, please follow the link in the email.';

	$codes[300] = 'You have been emailed a link to reset yoru password, please check your email.';
	$codes[301] = 'The email address you entered was not recognized';
	$codes[302] = 'There was a problem, please contact the site administrator';

	$codes[400] = 'Successfully updated your profile.';

	return apply_filters( 'hma_message_codes', $codes );
}