<?php
/**
 * Edit tag form for inclusion in administration panels.
 *
 * @package WordPress
 * @subpackage Administration
 */
 
 //vars $taxonomy

$tax_title = $page->args['single'];
$taxonomy = $page->args['taxonomy'];

$tag_ID = (int) $_GET['term'];
$tag = get_term( $tag_ID, $taxonomy );


// don't load directly
if ( !defined('ABSPATH') )
	die('-1');

if ( !current_user_can('manage_categories') )
	wp_die(__('You do not have sufficient permissions to edit tags for this blog.'));

if ( empty($tag_ID) ) { ?>
	<div id="message" class="updated fade"><p><strong><?php _e('A ' . $page->args['single'] . ' was not selected for editing.'); ?></strong></p></div>
<?php
	return;
}

do_action('edit_tag_form_pre', $tag); ?>

<?php include( 'cwp-taxonomy-form.php' ) ?>  