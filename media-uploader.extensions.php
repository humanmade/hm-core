<?php
/**
 * register_custom_media_button function.
 *
 * Wrapper function for easily added new add media buttons
 *
 * @param int $id
 * @param string $button_text (optional)
 * @param bool $hide_other_options (optional) hide the default send to editor button and the other
 * @param bool $mutliple (optional) if the uploader and js lets you add more than one image
 */
function hm_register_custom_media_button( $id, $button_text = null, $hide_other_options = true, $multiple = false, $width = 50, $height = 50, $crop = true, $type = 'thumbnail', $insert_into_post = false ) {

	if ( empty( $id ) || !is_string( $id ) )
		return false;

	$id = sanitize_title( $id );

	if ( is_null( $button_text ) )
		$button_text = 'Use as ' . ucwords( preg_replace( '#(-|_)#', ' ', $id ) );

	$buttons = get_option( 'custom_media_buttons' );

	$button = array( 'id' => $id, 'button_text' => $button_text, 'hide_other_options' => (bool) $hide_other_options, 'multiple' => ( $multiple ? 'yes' : '' ), 'width' => $width, 'height' => $height, 'crop' => $crop, 'insert_into_post' => (bool) $insert_into_post, 'type' => $type );

	$buttons[$id] = $button;

	update_option( 'custom_media_buttons', $buttons );

	// Include the js if it hasnt already
	global $has_included_custom_media_button_js;

	if ( !$has_included_custom_media_button_js )
		add_action( 'admin_footer', 'hm_add_custom_media_button_js');

}

function hm_remove_from_url_from_media_upload( $tabs ) {

	unset( $tabs['type_url'] );

	return $tabs;

}

function hm_add_custom_media_button_js() {

	add_filter( 'media_upload_tabs', 'hm_remove_from_url_from_media_upload' );

	global $has_included_custom_media_button_js;
	$has_included_custom_media_button_js = true;

	echo '<script type="text/javascript">';
	include( HELPERPATH . 'scripts/media-uploader.extensions.js' );
	echo '</script>';

}

/**
 * add_extra_media_buttons function.
 *
 * Adds the "Use as Post Thumbnail" button to the add media thickbox.
 *
 * @param array $form_fields
 * @param object $media
 * @return array $form_fields
 */
function hm_add_extra_media_buttons( $form_fields, $media ) {

	$buttons = get_option( 'custom_media_buttons' );

	if ( !empty( $_GET['button'] ) ) :
		$button_id = $_GET['button'];

	else :
		preg_match( '/button=([A-z0-9_][^&]*)/', $_SERVER['HTTP_REFERER'], $matches );
		if ( isset( $matches[1] ) )
			$button_id = $matches[1];

	endif;

	if ( !isset( $button_id ) || !$button_id )
		return $form_fields;

	if ( isset( $button_id ) && ($button = $buttons[$button_id]) ) {

		$crop = $button['crop'] == true ? 1 : 0;

		$attach_thumb_url = wp_get_attachment_image_src( $media->ID, "width={$button['width']}&height={$button['height']}&crop=$crop" );

		$onclick = "var win = window.dialogArguments || opener || parent || top;";

		if ( $button['type'] == 'file' ) :
			$onclick .= "win.insert_custom_file( '" . $button_id . "', " . $media->ID . ", '" . wp_get_attachment_url() . "', '" . $button['multiple'] . "', '" . $media->post_title . "' );";
			$onclick .= "jQuery(this).replaceWith('<span style=\'color: #07AA00; font-weight:bold;\'>File Added</span>');";

		elseif ( $button['insert_into_post'] ) :
			$onclick .= "win.insert_custom_image( '" . $button_id . "', " . $media->ID . ", '" . $attach_thumb_url[0] . "' );";
			$onclick .= "jQuery(this).replaceWith('<span style=\'color: #07AA00; font-weight:bold;\'>Image Inserted</span>');";

		else :
			$onclick .= "win.save_custom_image( '" . $button_id . "', " . $media->ID . ", '" . $attach_thumb_url[0] . "', '" . $button['multiple'] . "' );";
			$onclick .= "jQuery(this).replaceWith('<span style=\'color: #07AA00; font-weight:bold;\'>Image Added</span>');";

		endif;

		$onclick .= "return false;";

		$buttons_html = '<a class="button-primary" onclick="' . $onclick . '" href="">' .  esc_attr( $button['button_text'] ) . '</a>';
	}


	if ( !$button['hide_other_options'] ) :
		$send = '<input type="submit" class="button" name="send[' . $media->ID . ']" value="' . __( 'Insert into Post' ) . '" />';

	else : ?>

		<style type="text/css">
			.slidetoggle tr.post_title, .slidetoggle tr.image_alt, .slidetoggle tr.post_excerpt, .slidetoggle tr.post_content, .slidetoggle tr.url, .slidetoggle tr.align, .slidetoggle tr.image-size, .media-upload-form p.savebutton.ml-submit { display: none !important; }
		</style>

<?php endif;

	if ( !empty( $send ) )
		$send = '<input type="submit" class="button" name="send[' . $media->ID . ']" value="' . __( 'Insert into Post' ) . '" />';

	else
		$send = false;

	if ( current_user_can( 'delete_post', $media->ID ) ) {
		if ( !EMPTY_TRASH_DAYS ) {
			$delete = '<a href=""' . wp_nonce_url( 'post.php?action=delete&amp;post=' . $media->ID , 'delete-post_' . $media->ID ) . '" id="del[' . $media->ID . ']" class="delete">' . __('Delete Permanently') . '</a>';
		} elseif ( !MEDIA_TRASH ) {
			$delete = '<a href="#" class="del-link" onclick="document.getElementById( \'del_attachment_' . $media->ID . '\' ).style.display=\'block\'; return false;">' . __('Delete') . '</a> <div id="del_attachment_' . $media->ID . '" class="del-attachment" style="display:none;">' . sprintf( __( 'You are about to delete <strong>%s</strong>.' ), $media->post_title ) . ' <a href="' . wp_nonce_url( 'post.php?action=delete&amp;post=' . $media->ID, 'delete-post_' . $media->ID ) . '" id="del[' . $media->ID . ']" class="button">' . __( 'Continue' ) . '</a> <a href="#" class="button" onclick="this.parentNode.style.display=\'none\';return false;">' . __( 'Cancel' ) . '</a></div>';
		} else {
			$delete = '<a href="' . wp_nonce_url( 'post.php?action=trash&amp;post=' . $media->ID, 'trash-post_' . $media->ID ) . '" id="del[' . $media->ID . ']" class="delete">' . __( 'Move to Trash' ) . '</a> <a href="' . wp_nonce_url( 'post.php?action=untrash&amp;post=' . $media->ID, 'untrash-post_' . $media->ID ) . '" id="undo[' . $media->ID . ']" class="undo hidden">' . __( 'Undo' ) . '</a>';
		}
	} else {
		$delete = '';
	}

	$thumbnail = '';

	if ( isset( $type ) && 'image' == $type && current_theme_supports( 'post-thumbnails' ) && get_post_image_id( $_GET['post_id'] ) != $media->ID )
		$thumbnail = "<a class='wp-post-thumbnail' href='#' onclick='WPSetAsThumbnail(\"$media->ID\");return false;'>" . esc_html__( "Use as thumbnail" ) . "</a>";

	// Create the buttons array
	$form_fields['buttons'] = array( 'tr' => "\t\t<tr class='submit'><td></td><td class='savesend'>$send $thumbnail $buttons_html $delete</td></tr>\n" );

	return $form_fields;

}
add_filter( 'attachment_fields_to_edit', 'hm_add_extra_media_buttons', 99, 2 );

/** add_button_to_upload_form function
 *
 * Adds the button variable to the GET params of the media buttons thickbox link
 *
 */
function hm_add_button_to_upload_form() {

	if ( !isset( $_GET['button'] ) )
		return; ?>

	<script type="text/javascript">

		jQuery( document ).ready( function() {
			jQuery( '#image-form' ).attr( 'action', jQuery( '#image-form' ).attr( 'action' ) + '&amp;button=<?php echo $_GET['button']; ?>');
			jQuery( '#filter' ).append( '<input type="hidden" name="button" value="<?php echo $_GET['button'] ?>" />' );
			jQuery( '#library-form' ).attr( 'action', jQuery( '#library-form' ).attr( 'action' ) + '&amp;button=<?php echo $_GET['button']; ?>');
		} );

	</script>

<?php }
add_action( 'admin_head', 'hm_add_button_to_upload_form' );

function hm_add_image_html( $button_id, $post = null, $classes = null, $size = 'thumbnail' ) {

	if ( is_null( $post ) )
		global $post;

	if ( $post->term_id )
		$post->ID = $post->term_id;

	$type = ( $post->term_id ) ? 'term' : 'post'; ?>

	<span id="<?php echo $button_id; ?>_container" class="<?php echo $classes; ?>">

	<?php if ( $image_id = get_metadata( $type, $post->ID, $button_id, true  ) ) : ?>

		<span class="image-wrapper" id="<?php echo $post->ID; ?>">

			<?php echo wp_get_attachment_image( $image_id, $size ); ?>

			<a class="delete_custom_image" rel="<?php echo $button_id; ?>:<?php echo $post->ID; ?>">Remove</a> |

		</span>

	<?php endif; ?>

	</span>

	<a class="add-image button thickbox" onclick="return false;" title="Add an Image" href="media-upload.php?post=<?php echo $post->ID; ?>&amp;button=<?php echo $button_id; ?>&amp;type=image&amp;TB_iframe=true&amp;width=640&amp;height=197">
	    <img alt="Add an Image" src="<?php bloginfo( 'url' ); ?>/wp-admin/images/media-button-image.gif" /> Upload / Insert
	</a>

	<input type="hidden" name="<?php echo $button_id; ?>" id="<?php echo $button_id; ?>" value="<?php echo $image_id; ?>" />

<?php }

/**
 * Adds the necessary html for showing images (container, images, delete links etc).
 *
 * @param string 	$button_id
 * @param string 	$title (title of the "Add Images" button
 * @param int	 	$post_id
 * @param array 	$image_ids (array of image id's to be shown)
 * @param string 	$classes
 * @param string	$size (eg. 'width=15=&height=100&crop=1'
 * @param string 	$non_attached_text - Text to be shown when there are no images
 */
function hm_add_image_html_custom( $button_id, $title, $post_id, $image_ids, $classes, $size, $non_attached_text, $args = array() ) {

	$image_ids = array_filter( (array) $image_ids );

	$buttons = get_option( 'custom_media_buttons' );
	$button = $buttons[$button_id]; 
	$attachments = get_posts("post_type=attachment&post_parent=$post_id");
	
	$default_args = array( 'default_tab' => 'gallery' );
	
	$args = wp_parse_args( $args, $default_args );
	
	?>

	<style>
		#additional-images .inside { position: relative; }
		#hmp_gallery_images_container { clear: both; }
		.image-wrapper { text-align: center; display: block; padding: 5px; border: 1px solid #DFDFDF; float: left; margin-right: 7px; margin-bottom: 7px; background-color: #F1F1F1; -moz-border-radius: 4px; border-radius: 4px; }
		.sortable .image-wrapper { cursor: move; }
		.sortable .image-wrapper:hover { border-style: dashed; }
		.ui-sortable-placeholder { visibility: visible !important; background-color: transparent; border-style: dashed; }
		.image-wrapper img { display: block; }
		#side-sortables  .image-wrapper { padding: 4px; margin-right: 3px; margin-left: 3px;  }
		#side-sortables  .image-wrapper img { width: 113px; height: auto; }
		.image-wrapper a { display: block; cursor: pointer; margin: 10px; }
		#<?php echo $button_id; ?>_container { display: block; overflow: hidden; }
		#normal-sortables .postbox .<?php echo $button_id; ?>_submit { padding: 0; margin: 9px 6px 12px; display: block; }
	</style>

	<p class="submit <?php echo $button_id; ?>_submit">
		<a class="add-image button thickbox" onclick="return false;" title="Add Image" href="media-upload.php?button=<?php echo $button_id ?>&amp;post_id=<?php echo $post_id ?><?php echo $post_id > 0 && $attachments && $args['default_tab'] == 'gallery' ? "&amp;tab=gallery" : '' ?>&amp;multiple=<?php echo $button['multiple'] == true ? 'yes' : 'no' ?>&amp;type=image&amp;TB_iframe=true&amp;width=640&amp;height=197">
			<?php echo $title ?>
		</a>

		<input type="hidden" name="<?php echo $button_id ?>" id="<?php echo $button_id ?>" value="<?php echo implode( ',', $image_ids ) ?>" />
	</p>

	<span id="<?php echo $button_id; ?>_container" rel="<?php echo $button_id ?>" class="<?php echo $classes; ?>">

	    <?php foreach( $image_ids as $image_id ) : ?>
	    	 <span class="image-wrapper" id="<?php echo $image_id ?>"><?php echo wp_get_attachment_image( $image_id, $size ); ?>
	    	 <a class="delete_custom_image" rel="<?php echo $button_id ?>:<?php echo $image_id ?>">Remove</a></span>
	    <?php endforeach; ?>

	    <?php if( !$image_ids ) : ?>
	    	<?php if( $non_attached_text === null ) : ?>
	    		<p class="empty-message">No <?php echo $button['text'] ?> Added</p>
	    	<?php else : ?>
	    		<p class="empty-message"><?php echo $non_attached_text ?></p>
	    	<?php endif; ?>
	    <?php endif; ?>

	</span>

	<div style="clear: both;"></div>

	<?php
}