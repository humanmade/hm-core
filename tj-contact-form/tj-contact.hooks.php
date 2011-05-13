<?php

add_action( 'hm_contact_form', 'hm_contact_form_field' );
function hm_contact_form_field() {
	
	wp_nonce_field( 'hm_contact_form_submit' );
	echo '<input type="hidden" name="hm_contact_submitted" value="submitted" />' . "\n";
	
}

add_action( 'init', 'hm_contact_check_for_submitted' );
function hm_contact_check_for_submitted() {
	if( isset( $_POST['hm_contact_submitted'] ) && $_POST['hm_contact_submitted'] == 'submitted' && check_admin_referer( 'hm_contact_form_submit' ) ) {
		$success = hm_contact_send_email();
		if( !is_wp_error($success) )
			wp_redirect( add_query_arg( 'contact', 'success', wp_get_referer() ) );
		else
			wp_redirect( add_query_arg( 'contact', 'failed', wp_get_referer() ) );
		exit;
	}
}