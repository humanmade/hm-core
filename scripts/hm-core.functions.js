/*
@param: string - message to be shown in the error box
@param: object - the input in question
*/
alreadyErrors = false;
function showValidation( message, field ) {
	
    form = jQuery(field).closest('form');
        
    if( jQuery(form).find('.validation-messages').length > 0 )
    	dest = jQuery(form).find('.validation-messages');
    else if( jQuery(form).closest('.validation-messages').length > 0 )
    	dest = jQuery(form).closest('.validation-messages');
    else if( jQuery('.validation-messages').length > 0 )
    	dest = jQuery('.validation-messages');
    else
    	dest = form;
        	
    if( jQuery(dest).find(".message.error").hasClass('error') )
    	alreadyErrors = true;
	
	if( alreadyErrors == false )
    	jQuery(dest).append( '<p class="message error" style="display: none;">' + message + '</div>' );
    else
    	jQuery(dest).find(".message.error").html(message);

    //only slide down if an erro has not already been shown
    if( alreadyErrors == false )
    	jQuery(dest).find(".message.error").slideDown();
    else
    	jQuery(dest).find(".message.error").show();
    jQuery(field).focus();
}

/*
Loop the forms to validate any with "validate" class
*/

jQuery(document).ready( function() {
	jQuery('form.validate').submit( function(e) {
		return validateForm(this);
	});
} );
/*
Validates a form, will call showVlidation() on fail
Uses .required as flag, checks against blank and input's alt attr

@param: object - the form in question
@return: bool
*/
function validateForm( form ) {
	success = true;
	
	jQuery( form ).find('input, textarea').filter( function() { return jQuery(this).attr('class').search('required-group-') === 0 ? true : false; }).each( function() {
		if( success == true ) {
			reqNum = jQuery(this).attr('class').replace('required-group-', '');
			success = false;
			jQuery(form).find(".required-group-" + reqNum).filter( function() { return validate_field(jQuery(this)) } ).each( function() {
				success = true;
			});
			
		}
	});

	jQuery(form).find(".required").each( function() {
		if( success == true ) {
			success = validate_field(jQuery(this));
		}
	});
		
	return success;
}

function validate_field( input ) {
	success = true;
	
	if( jQuery(input).attr("id") == 'spambot' ) {
	    if( jQuery(input).val() > '' ) {
	    	success = false;
	    	showValidation( 'Spambot detected, please contact us directly via email' );
	    }
	}
	else if ( jQuery(input).val() == '' && jQuery(input).attr('type') === 'file' ) {
	    success = false;
	    field = jQuery(input).attr("title");
	    showValidation( 'Please upload a file into the ' + field + ' field.', jQuery(input) );		
	}
	else if( jQuery(input).val() == '' || jQuery(input).val() == jQuery(input).attr("alt") ) {
	    success = false;
	    				
	    if( jQuery("label[for=" + jQuery(input).attr("id") + "]").text() > '' && jQuery(input).attr("id") )
	    	field = jQuery("label[for=" + jQuery(input).attr("id") + "]").text().replace(':', '');
	    else
	    	field = jQuery(input).attr("title");
	    					
	    showValidation( 'Please enter text into the ' + field + ' field.', jQuery(input) );			
	}
	
	if( jQuery(input).attr("id") == 'email' ||  jQuery(input).hasClass("email") ) {
	    if( !isValidEmail( jQuery(input).val() ) ) {
	    	success = false;
	    	showValidation( 'The email address you entered is invalid', jQuery(input) );
	    }
	}
	
	if( jQuery(input).hasClass("domain") ) {
	    jQuery(input).val( fillUrl( jQuery(input).val() ) );
	    if( !isValidUrl( jQuery(input).val() ) ) {
	    	success = false;
	    	showValidation( 'The URL you entered is invalid', jQuery(input) );
	    	
	    	
	    }
	}
	
	if( jQuery(input).hasClass('confirm-password') && jQuery(input).val() > '' && jQuery(input).val() != jQuery(form).find(".password").val() ) {
	    success = false;
	    showValidation( 'The Confirm Password and Password fields do not match', jQuery(input) );
	}
	
	return success;
}

/*
@param: string - email address
@return: bool
*/
function isValidEmail (email) {
	return /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(email);
}

/*
@param: string - domain / url
@return: bool
*/
function isValidUrl (url) {
	return /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i.test(url);
}

function fillUrl( url ) {
	if( url == '' )	
		return url;
		
	if( ! /^(https?):\/\//i.test(url) )
		url = 'http://' + url;
	
	return url;
}

function showLoading( obj ) {
	jQuery(obj).addClass("loading");
}
function hideLoading( obj ) {
	jQuery(obj).removeClass("loading");
}

//extends jquery's :contains to case insensitive (you must use ":Contains" for insensitive)
jQuery.expr[':'].Contains = function(a,i,m){
    return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase())>=0;
};