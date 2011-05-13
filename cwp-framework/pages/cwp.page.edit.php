<?php

wp_tiny_mce();
global $user_ID, $post_ID, $action, $post, $temp_ID;

$action = $_GET['p'] ? 'edit' : '';

if( $post_ID = $_GET['p'] ) {
	$mode = 'edit';
	$post = get_post_to_edit($post_ID);
}
else { 
	$post = get_default_post_to_edit();
	$mode = 'new';
}

//messages
$messages[1] = sprintf(__( $page->args['single']. ' updated. <a href="%s">View ' . strtolower( $page->args['single'] ) . '</a>'), get_permalink($post_ID));
$messages[2] = __('Custom field updated.');
$messages[3] = __('Custom field deleted.');
$messages[4] = __($page->args['single'] . ' updated.');
$messages[6] = sprintf(__($page->args['single'] . ' published. <a href="%s">View ' . strtolower( $page->args['single'] ) . '</a>'), get_permalink($post_ID));
$messages[7] = __($cwp->args['single_item'] . ' saved.');
$messages[8] = sprintf(__($page->args['single'] . ' submitted. <a href="%s">Preview post</a>'), add_query_arg( 'preview', 'true', get_permalink($post_ID) ) );

$notice = false;
if ( 0 == $post_ID ) {
	$form_action = 'post';
	$temp_ID = -1 * time(); // don't change this formula without looking at wp_write_post()
	$form_extra = "<input type='hidden' id='post_ID' name='temp_ID' value='" . esc_attr($temp_ID) . "' />";
	$autosave = false;
} else {
	$form_action = 'editpost';
	$form_extra = "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />";
	//$autosave = wp_get_post_autosave( $post_ID );

	// Detect if there exists an autosave newer than the post and if that autosave is different than the post
	/*
	if ( $autosave && mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
		foreach ( _wp_post_revision_fields() as $autosave_field => $_autosave_field ) {
			if ( normalize_whitespace( $autosave->$autosave_field ) != normalize_whitespace( $post->$autosave_field ) ) {
				$notice = sprintf( __( 'There is an autosave of this post that is more recent than the version below.  <a href="%s">View the autosave</a>.' ), get_edit_post_link( $autosave->ID ) );
				break;
			}
		}
		unset($autosave_field, $_autosave_field);
	}
	*/
}

//add meta boxes
foreach( (array) $page->meta_boxes as $meta_box ) {
	add_meta_box( $meta_box[0], $meta_box[1], $meta_box[2], $meta_box[3], $meta_box[4] );
}

?>
<style>
	#post-body .wp_themeSkin .mceStatusbar a.mceResize { top: 0; }
</style>
<div class="wrap">
	<h2><?php echo $page->get_title() ?></h2>
	<?php if ( $notice ) : ?>
		<div id="notice" class="error"><p><?php echo $notice ?></p></div>
	<?php endif; ?>
	<?php if (isset($_GET['message'])) : ?>
		<div id="message" class="updated fade"><p><?php echo $messages[$_GET['message']]; ?></p></div>
	<?php endif; ?>
	<form name="post" action="" method="post" id="post">
		<?php
		
		if ( 0 == $post_ID)
			wp_nonce_field('add-post');
		else
			wp_nonce_field('update-post_' .  $post_ID);
		
		?>
		
		<?php do_action( 'cwp_add_page_hidden_input' ) ?>
		<input type="hidden" id="cwp_submitted" name="cwp_submitted_<?php echo $page->get_page_id() ?>" value="add" />
		<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
		<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr($form_action) ?>" />
		<input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr($form_action) ?>" />
		<input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
		<input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $page->post_args['post_type'] ? $page->post_args['post_type'] : $post->post_type) ?>" />
		<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr($post->post_status) ?>" />
		<input type="hidden" name="parent_id" value="<?php echo (int) ($page->post_args['post_parent']) ? $page->post_args['post_parent'] : $post->post_parent ?>" />
    	<input type="hidden" name="comment_status" value="<?php echo ($page->post_args['comment_status']) ? $page->post_args['comment_status'] : $post->comment_status ?>" />
		<input name="referredby" type="hidden" id="referredby" value="<?php echo esc_url(stripslashes(wp_get_referer())); ?>" />
		<input type="hidden" id="post_name" name="post_name" value="<?php echo $post->post_name ?>" />
		<?php
		if ( 'draft' != $post->post_status )
			wp_original_referer_field(true, 'previous');
		
		echo $form_extra ?>
		
		<div id="poststuff" class="metabox-holder<?php echo 1 == $screen_layout_columns ? '' : ' has-right-sidebar'; ?>">
		<div id="side-info-column" class="inner-sidebar">
		
			<?php do_action('submitpost_box'); ?>
			<?php $side_meta_boxes = do_meta_boxes( $page->get_page_id() , 'side', $post);  ?>
			
		</div>
		<input type="hidden" id="cwp_panel_id" name="cwp_panel_id" value="<?php echo $page->get_page_id() ?>" />
		<div id="post-body">
		<div id="post-body-content">
			<div id="titlediv">
				<div id="titlewrap">
					<label class="screen-reader-text" for="title"><?php _e('Title') ?></label>
					<input type="text" name="post_title" size="30" tabindex="1" value="<?php echo esc_attr( htmlspecialchars( $post->post_title ) ); ?>" id="title" autocomplete="off" />
					
					<div class="inside" style="">
					<?php
						$sample_permalink_html = get_sample_permalink_html($post->ID);
											
						if ( !( 'pending' == $post->post_status && !current_user_can( 'publish_posts' ) ) ) { ?>
						    <div id="edit-slug-box">
						<?php
						    if ( ! empty($post->ID) && ! empty($sample_permalink_html) ) :
						    	echo $sample_permalink_html;
						endif; ?>
						    </div>
						<?php
						} ?>
					</div>
	
				</div>
			
			</div>
			
			<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
			
				<?php do_action( 'cwp_add_page_before_editor' ) ?>
				<?php the_editor($post->post_content); ?>
				
				<?php 
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'getpermalink', 'getpermalinknonce', false );
				wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
			</div>
			
			<?php
			
			do_meta_boxes($page->get_page_id(), 'normal', $post);
			do_action('edit_form_advanced');
			do_meta_boxes($page->get_page_id(), 'advanced', $post);
			do_action('dbx_post_sidebar'); ?>
			
		</div>
		</div>
		<br class="clear" />
		</div><!-- /poststuff -->
	</form>
</div>

<?php wp_comment_reply(); ?>

<?php if ((isset($post->post_title) && '' == $post->post_title) || (isset($_GET['message']) && 2 > $_GET['message'])) : ?>
<script type="text/javascript">
try{document.post.title.focus();}catch(e){}
</script>
<?php endif; ?>
<?php

?>