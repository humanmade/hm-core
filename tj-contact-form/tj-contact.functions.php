<?php


function hm_contact_send_email() {
	
	extract( $_POST );
	
	$to = get_option('admin_email');
	$subject = 'New message from ' . get_bloginfo();
	$subject = apply_filters( 'hm_contact_email_subject', $subject );
	
	// server-side error checking
	if( !$to )
		return new WP_Error( 'no-admin-email', 'You must set the Admin Email option in the Settings page' );
	
	ob_start();
	if(  file_exists( $file = get_template_directory() . '/email.contact.php' ) ) {
		include( $file );
	} else {
		include( 'email.default.php' );
	}
	$message = ob_get_contents();
	ob_end_clean();
	
	$headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
	
	return wp_mail( $to, $subject, $message, $headers );
}