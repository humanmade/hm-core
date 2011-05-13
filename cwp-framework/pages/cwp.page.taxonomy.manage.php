<?php

$taxonomy = $page->args['taxonomy'];

if ( !is_taxonomy($taxonomy) )
	wp_die(__('Invalid taxonomy'));

$title = $page->get_title();
$can_manage = current_user_can('manage_categories');

$messages[1] = __( $page->args['single'] . ' added.');
$messages[2] = __( $page->args['single'] . ' deleted.');
$messages[3] = __( $page->args['single'] . ' updated.');
$messages[4] = __( $page->args['single'] . ' not added.');
$messages[5] = __( $page->args['single'] . ' not updated.');
$messages[6] = __( $page->args['multiple'] . ' deleted.'); 

?>
<div class="wrap nosubsub">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title );
	if ( isset($_GET['s']) && $_GET['s'] )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( stripslashes($_GET['s']) ) ); ?>
	</h2>
	
	<?php if ( isset($_GET['message']) && ( $msg = (int) $_GET['message'] ) ) : ?>
		<div id="message" class="updated fade"><p><?php echo $messages[$msg]; ?></p></div>
		<?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
	endif; ?>
	
	<form class="search-form" action="" method="get">
		<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
		<input type="hidden" name="page" value="<?php echo $_GET["page"] ?>" />
		<input type="hidden" name="cwp_submitted_<?php echo $page->get_page_id() ?>" value="taxonomy-add" />
		<p class="search-box">
			<label class="screen-reader-text" for="tag-search-input"><?php _e( 'Search ' . $page->args['multiple'] ); ?>:</label>
			<input type="text" id="tag-search-input" name="s" value="<?php _admin_search_query(); ?>" />
			<input type="submit" value="<?php esc_attr_e( 'Search ' . $page->args['multiple'] ); ?>" class="button" />
		</p>
		</form>
		<br class="clear" />
		
		<div id="col-container">
		
		<div id="col-right">
		<div class="col-wrap">
		<form id="posts-filter" action="" method="get">
		<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
		<input type="hidden" name="cwp_submitted_<?php echo $page->get_page_id() ?>" value="taxonomy-add" />
		<input type="hidden" name="page" value="<?php echo $_GET['page'] ?>" />
		<div class="tablenav">
		<?php
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 0;
		if ( empty($pagenum) )
			$pagenum = 1;
		
		$tags_per_page = get_user_option('edit_tags_per_page');
		if ( empty($tags_per_page) )
			$tags_per_page = 20;
		
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => ceil(wp_count_terms($taxonomy) / $tags_per_page),
			'current' => $pagenum
		));
		
		if ( $page_links )
			echo "<div class='tablenav-pages'>$page_links</div>";
		?>
		
		<div class="alignleft actions">
		<select name="action">
		<option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
		<option value="delete"><?php _e('Delete'); ?></option>
		</select>
		<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
		<?php wp_nonce_field('bulk-tags'); ?>
		</div>
		
		<br class="clear" />
		</div>
		
		<div class="clear"></div>
		
		<table class="widefat tag fixed" cellspacing="0">
			<thead>
			<tr>
				<?php print_column_headers('edit-tags'); ?>
			</tr>
			</thead>
		
			<tfoot>
			<tr>
				<?php print_column_headers('edit-tags', false); ?>
			</tr>
			</tfoot>
		
			<tbody id="the-list" class="list:tag">
		<?php
		
		$searchterms = isset( $_GET['s'] ) ? trim( $_GET['s'] ) : '';
		
		$count = cwp_tag_rows( $pagenum, $tags_per_page, $searchterms, $taxonomy, $page );
		?>
			</tbody>
		</table>
		
		<div class="tablenav">
		<?php
		if ( $page_links )
			echo "<div class='tablenav-pages'>$page_links</div>";
		?>
		
		<div class="alignleft actions">
		<select name="action2">
		<option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
		<option value="delete"><?php _e('Delete'); ?></option>
		</select>
		<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
		</div>
		
		<br class="clear" />
		</div>
		
		<br class="clear" />
	</form>
	</div>
	</div><!-- /col-right -->
	
	<div id="col-left">
	<div class="col-wrap">
	<?php if ( $can_manage ) {
		do_action('add_tag_form_pre'); ?>
	
	<?php include( 'cwp-taxonomy-form.php' ) ?>  
	<?php } ?>
	
	</div>
	</div><!-- /col-left -->
	
	</div><!-- /col-container -->
</div><!-- /wrap -->
<?php inline_edit_term_row('edit-tags'); 



// Tag stuff

// Returns a single tag row (see tag_rows below)
// Note: this is also used in admin-ajax.php!
/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @param unknown_type $tag
 * @param unknown_type $class
 * @return unknown
 */
function cwp_tag_row( $tag, $class = '', $taxonomy = 'post_tag', $page ) {
		$count = number_format_i18n( $tag->count );
		$tagsel = ($taxonomy == 'post_tag' ? 'tag' : $taxonomy);
		$count = ( $count > 0 ) ? "<a href='edit.php?$tagsel=$tag->slug'>$count</a>" : $count;

		$name = apply_filters( 'term_name', $tag->name );
		$qe_data = get_term($tag->term_id, $taxonomy, object, 'edit');
		$edit_link = $page->get_edit_term_link( $tag );
		$out = '';
		$out .= '<tr id="tag-' . $tag->term_id . '"' . $class . '>';
		$columns = get_column_headers('edit-tags');
		$hidden = get_hidden_columns('edit-tags');
		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"$column_name column-$column_name\"";

			$style = '';
			if ( in_array($column_name, $hidden) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			switch ($column_name) {
				case 'cb':
					$out .= '<th scope="row" class="check-column"> <input type="checkbox" name="delete_tags[]" value="' . $tag->term_id . '" /></th>';
					break;
				case 'name':
					$out .= '<td ' . $attributes . '><strong><a class="row-title" href="' . $edit_link . '" title="' . esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $name)) . '">' . $name . '</a></strong><br />';
					$actions = array();
					$actions['inline hide-if-no-js'] = '<a href="#" class="editinline">' . __('Edit') . '</a>';
					$delete_url = wp_nonce_url( $page->get_page_url() . "&amp;action=delete&amp;taxonomy=$taxonomy&amp;tag_ID=$tag->term_id&cwp_submitted_" . $page->get_page_id() . "=taxonomy-add", 'delete-tag_' . $tag->term_id);

					$actions['delete'] = "<a class='delete:the-list:tag-$tag->term_id submitdelete' href='" . $delete_url . "'>" . __('Delete') . "</a>";
					$actions = apply_filters('tag_row_actions', $actions, $tag);
					$action_count = count($actions);
					$i = 0;
					$out .= '<div class="row-actions">';
					foreach ( $actions as $action => $link ) {
						++$i;
						( $i == $action_count ) ? $sep = '' : $sep = ' | ';
						$out .= "<span class='$action'>$link$sep</span>";
					}
					$out .= '</div>';
					$out .= '<div class="hidden" id="inline_' . $qe_data->term_id . '">';
					$out .= '<div class="name">' . $qe_data->name . '</div>';
					$out .= '<div class="slug">' . $qe_data->slug . '</div></div></td>';
					break;
				case 'description':
					$out .= "<td $attributes>$tag->description</td>";
					break;
				case 'slug':
					$out .= "<td $attributes>$tag->slug</td>";
					break;
				case 'posts':
					$attributes = 'class="posts column-posts num"' . $style;
					$out .= "<td $attributes>$count</td>";
					break;
				default:
					$out .= "<td $attributes>";
					$out .= apply_filters("manage_${taxonomy}_custom_column", '', $column_name, $tag->term_id);
					$out .= "</td>";
			}
		}

		$out .= '</tr>';

		return $out;
}

// Outputs appropriate rows for the Nth page of the Tag Management screen,
// assuming M tags displayed at a time on the page
// Returns the number of tags displayed
/**
 * {@internal Missing Short Description}}
 *
 * @since unknown
 *
 * @param unknown_type $page
 * @param unknown_type $pagesize
 * @param unknown_type $searchterms
 * @return unknown
 */
function cwp_tag_rows( $page = 1, $pagesize = 20, $searchterms = '', $taxonomy = 'post_tag', $page ) {

	// Get a page worth of tags
	$start = ($page - 1) * $pagesize;
	$args = array('offset' => $start, 'number' => $pagesize, 'hide_empty' => 0);

	if ( !empty( $searchterms ) ) {
		$args['search'] = $searchterms;
	}

	$tags = get_terms( $taxonomy, $args );

	// convert it to table rows
	$out = '';
	$count = 0;
	foreach( $tags as $tag )
		$out .= cwp_tag_row( $tag, ++$count % 2 ? ' class="iedit alternate"' : ' class="iedit"', $taxonomy, $page );

	// filter and send to screen
	echo $out;
	return $count;
}



?>