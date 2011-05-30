jQuery(document).ready(function($) {
	$("a.delete_custom_image").live( 'click', function(e) {
		e.preventDefault();
		delete_custom_image(this);
	});

	$('.image-wrapper:first').addClass('first');
	
	if ( typeof( $.fn.sortable ) != 'undefined' ) {
   		$( '.sortable' ).sortable( {
		containment : 'parent',
   			update: function( event, ui ) {
   				write_image_ids_to_input( jQuery(this).attr( 'rel' ) );
				$('.image-wrapper').removeClass('first');
 				$('.image-wrapper:first').addClass('first');
   			}
		} );
	}

});


// Part of media-uploader extensions, inserts the img into html on save
function save_custom_image( button_id, id, src, is_multiple ) {

	// Close the thickbox only if it was for a single image
	if ( is_multiple !== 'yes' )
		tb_remove();

	// Add the id to the hidden field
	if ( is_multiple == 'yes' )
		jQuery( '#' + button_id ).attr( "value", jQuery( '#' + button_id ).attr( "value" ) + ',' + id );
	else
		jQuery( '#' + button_id ).attr( "value", id );

	// If there is already an image remove it
	if ( is_multiple !== 'yes' )
		jQuery( '#' + button_id + '_container' ).html( '' );

	// Create the image
	jQuery( '<span class="image-wrapper" id="' + id + '"><img src="' + src + '" /><a class="delete_custom_image" rel="' + button_id + ':' + id + '">Remove</a> </span>' ).appendTo( '#' + button_id + '_container' );

	// Finally show the containing div
	jQuery( '#' + button_id + '_container' ).show( 'fast' );

	// if there is an empty message, hide it
	if ( jQuery( '#' + button_id + '_container' ).find( '.empty-message' ).length > 0 )
		jQuery( '#' + button_id + '_container' ).find( '.empty-message' ).hide();

}

function delete_custom_image( element ) {

	args = jQuery(element).attr('rel').split(':');

	// Remove the image and the delete link from the form
	jQuery( '#' + args[0] + '_container' ).find( "#" + args[1] ).hide( 0, function() {
		jQuery(this).remove()
	} );

	// Remove the hidden input's value
	jQuery( '#' + args[0] ).val( jQuery( '#' + args[0] ).val().replace( String( args[1] ), '' ) );
}

function insert_custom_image( button_id, id, src ) {

	var ed;

	var h = '<img src="' + src + '" />';

	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.getInstanceById( button_id ) ) ) {

		ed.focus();

		if ( tinymce.isIE )
			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

		ed.execCommand('mceInsertContent', false, h);

	}

	tb_remove();

}

function insert_custom_file( button_id, id, src, is_multiple, title ) {

	// Close the thickbox only if it was for a single image
	if ( is_multiple !== 'yes' )
		tb_remove();

	// Add the id to the hidden field
	if ( is_multiple == 'yes' )
		jQuery( '#' + button_id ).val( jQuery( '#' + button_id ).val() + ',' + id );
	else
		jQuery( '#' + button_id ).val( id );

	// If there is already an image remove it
	if ( is_multiple !== 'yes' )
		jQuery( '#' + button_id + '_container' ).html( '' );

	// Create the image
	jQuery( '<span class="file-wrapper" id="' + id + '"><strong href="' + src + '">' + title + '</strong><a class="delete_custom_image" rel="' + button_id + ':' + id + '">Remove</a></span>' ).appendTo( '#' + button_id + '_container' );

	// Finally show the containing div
	jQuery( '#' + button_id + '_container' ).show( 'fast' );

	// If there is an empty message, hide it
	if ( jQuery( '#' + button_id + '_container' ).find( '.empty-message' ).size() )
		jQuery( '#' + button_id + '_container' ).find( '.empty-message' ).hide();

}

function write_image_ids_to_input( button_id ) {

	jQuery( '#' + button_id ).attr( "value", '' );

	jQuery( '#' + button_id + '_container' ).find( '.image-wrapper' ).each( function() {
		jQuery( '#' + button_id ).attr( "value", jQuery( '#' + button_id ).attr( "value" ) + ',' + jQuery(this).attr("id") );
	} );

	jQuery( '#' + button_id ).attr( "value", ltrim( jQuery( '#' + button_id ).attr( "value" ), ',' ) );
}

function ltrim(str, chars) {
	chars = chars || "\\s";
	return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
}