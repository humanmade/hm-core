<?php

/*  PHP Paypal IPN Integration Class Demonstration File
 *  4.16.2005 - Micah Carrick, email@micahcarrick.com
 *
 *  This file demonstrates the usage of paypal.class.php, a class designed  
 *  to aid in the interfacing between your website, paypal, and the instant
 *  payment notification (IPN) interface.  This single file serves as 4 
 *  virtual pages depending on the "action" varialble passed in the URL. It's
 *  the processing page which processes form data being submitted to paypal, it
 *  is the page paypal returns a user to upon success, it's the page paypal
 *  returns a user to upon canceling an order, and finally, it's the page that
 *  handles the IPN request from Paypal.
 *
 *  I tried to comment this file and the actual class file, as well as
 *  I possibly could.  Please email me with questions, comments, and suggestions.
 *  See the header of paypal.class.php for additional resources and information.
*/

function tw_paypal_express_checkout( $action = 'process', $product = null, $price = null, $quantity = 1, $additional_fields = array(), $query_args = array() ) {

	require_once( HELPERPATH . 'paypal/paypal.class.php' );  // include the class file
	
	$p = new paypal_class;             // initiate an instance of the class
	$p->paypal_url = get_option( 'paypal_url' );
	//$p->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url
	            
	// setup a variable for this script (ie: 'http://www.micahcarrick.com/paypal.php')
	if ( defined( 'PAYPAL_ORDER_URL' ) )
		$paypal_action_url = PAYPAL_ORDER_URL;
		
	else
		$paypal_action_url = HELPERPATH . 'paypal/process.order.php';
	
	if ( $query_args )
		$paypal_action_url = add_query_arg( $query_args, $paypal_action_url );
	
	if ( !is_string( $action ) )
		$action = 'process';

	switch ( $action ) :
	    
	case 'process':      // Process and order...
	
		// There should be no output at this point.  To process the POST data,
		// the submit_paypal_post() function will output all the HTML tags which
		// contains a FORM which is submited instantaneously using the BODY onload
		// attribute.  In other words, don't echo or printf anything when you're
		// going to be calling the submit_paypal_post() function.
		
		// This is where you would have your form validation  and all that jazz.
		// You would take your POST vars and load them into the class like below,
		// only using the POST values instead of constant string expressions.
		
		// For example, after ensureing all the POST variables from your custom
		// order form are valid, you might have:
		//
		// $p->add_field('first_name', $_POST['first_name']);
		// $p->add_field('last_name', $_POST['last_name']);
		
		$p->add_field( 'business', get_option( 'paypal_business_email') );
		$p->add_field( 'return', add_query_arg( 'action', 'success', $paypal_action_url ) );
		$p->add_field( 'cancel_return', add_query_arg( 'action', 'cancel', $paypal_action_url ) );
		$p->add_field( 'notify_url', add_query_arg( 'action', 'ipn', $paypal_action_url ) );
		$p->add_field( 'item_name', $product );
		$p->add_field( 'amount', $price );
		$p->add_field( 'quantity', $quantity );
		$p->add_field( 'currency_code', 'GBP' );
		
		foreach ( $additional_fields as $key => $value )
			$p->add_field( $key, $value );
		
		$p->submit_paypal_post(); // submit the fields to paypal
		//$p->dump_fields();      // for debugging, output a table of all the fields
		break;
	   
	case 'success':      // Order was successful...
	
		// This is where you would probably want to thank the user for their order
		// or what have you.  The order information at this point is in POST 
		// variables.  However, you don't want to "process" the order until you
		// get validation from the IPN.  That's where you would have the code to
		// email an admin, update the database with payment status, activate a
		// membership, etc.  
		
		return do_action( 'hm_payment_completed', 'unverified'  );
		
		// You could also simply re-direct them to another page, or your own 
		// order status page which presents the user with the status of their
		// order based on a database (which can be modified with the IPN code 
		// below).
		
		break;
	   
	case 'cancel':       // Order was canceled...
	
		// The order was canceled before being completed.
		return do_action( 'hm_payment_canceled', 'canceled' );
	   
		break;
	   
	case 'ipn':          // Paypal is calling page for IPN validation...
	
		// It's important to remember that paypal calling this script.  There
		// is no output here.  This is where you validate the IPN data and if it's
		// valid, update your database to signify that the user has payed.  If
		// you try and use an echo or printf function here it's not going to do you
		// a bit of good.  This is on the "backend".  That is why, by default, the
		// class logs all IPN data to a text file.
		
		if ( $p->validate_ipn() ) :
		    
			// Payment has been recieved and IPN is verified.  This is where you
			// update your database to activate or process the order, or setup
			// the database with the user's order details, email an administrator,
			// etc.  You can access a slew of information via the ipn_data() array.
			
			// Check the paypal documentation for specifics on what information
			// is available in the IPN POST variables.  Basically, all the POST vars
			// which paypal sends, which we send back for validation, are now stored
			// in the ipn_data() array.
			
			// Does nothing by default, hook in to the filter to run functions.
			
			do_action( 'hm_paypal_ipn_verified', $p->ipn_data );
			
		else :
		
			// Payment failed for some reason.
			
			// Again we don't do anything by default apart from run a filter onto 
			// which you can hook your functions.
			
			do_action( 'hm_paypal_ipn_failed', $p->ipn_data );
			
		endif;
			 
		break;
	endswitch;
}
?>