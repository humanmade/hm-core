<?php
global $wpdb, $wp_locale;

// setup query
$query = $page->query;

if( $_GET['cat'] ) {
    $query['cat'] = $_GET['cat'];
    $query['category__in'] = array( $_GET['cat'] );
}

// custom taxonomies
foreach( (array) $page->filters as $filter ) {
	if( $_GET[ $filter['taxonomy']] && $filter['type'] === 'taxonomy' ) {
		$query['taxonomy'] = $filter['taxonomy'];
		$term = get_term( (int) $_GET[ $filter['taxonomy']], $filter['taxonomy'] );
		$query['term'] = $term->slug;
	}
}

//post status
if( $_GET['post_status'] )
	$query['post_status'] = (string) $_GET['post_status'];
	
if( $_GET['s'] )
    $query['s'] = $_GET['s'];

$_GET['paged'] = $_GET['paged'] ? $_GET['paged'] : 1;
$query['paged'] = $_GET['paged'];

$page->run_query( $query );

?>

<div class="wrap">
    <h2><?php echo $this->get_title();
    if ( isset($_GET['s']) && $_GET['s'] )
    	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( $page->wp_query->get('s') ) ); ?>
    </h2>
    
    <?php
    //messages
    if ( isset($_GET['locked']) || isset($_GET['skipped']) || isset($_GET['updated']) || isset($_GET['deleted']) || isset($_GET['trashed']) || isset($_GET['untrashed']) ) { ?>
    	<div id="message" class="updated fade"><p>
    	<?php if ( isset($_GET['updated']) && (int) $_GET['updated'] ) {
    		printf( _n( '%s ' . strtolower( $page->args['single'] ) . ' updated.', '%s ' . strtolower($cwp->args['single_item']) . 's updated.', $_GET['updated'] ), number_format_i18n( $_GET['updated'] ) );
    		unset($_GET['updated']);
    	}
    	
    	if ( isset($_GET['skipped']) && (int) $_GET['skipped'] )
    		unset($_GET['skipped']);
    	
    	if ( isset($_GET['locked']) && (int) $_GET['locked'] ) {
    		printf( _n( '%s ' . strtolower($page->args['single'] ) . ' not updated, somebody is editing it.', '%s ' . strtolower( $cwp_args['single_name'] ) . 's not updated, somebody is editing them.', $_GET['locked'] ), number_format_i18n( $_GET['locked'] ) );
    		unset($_GET['locked']);
    	}
    	
    	if ( isset($_GET['deleted']) && (int) $_GET['deleted'] ) {
    		printf( _n( $page->args['single'] . ' deleted.', '%s ' . strtolower($page->args['multiple']) . ' deleted.', $_GET['deleted'] ), number_format_i18n( $_GET['deleted'] ) );
    		unset($_GET['deleted']);
    	}
    	
    	if ( isset($_GET['trashed']) && (int) $_GET['trashed'] ) {
			printf( _n( $page->args['single'] . ' moved to the trash.', '%s ' . strtolower($page->args['multiple']) . ' moved to the trash.', $_GET['trashed'] ), number_format_i18n( $_GET['trashed'] ) );
			$ids = isset($_GET['ids']) ? $_GET['ids'] : 0;
			echo ' <a href="' . esc_url( wp_nonce_url( $page->get_page_url() . "?doaction=undo&action=untrash&ids=$ids", "bulk-posts" ) ) . '">' . __('Undo') . '</a><br />';
			unset($_GET['trashed']);
		}
		
		if ( isset($_GET['untrashed']) && (int) $_GET['untrashed'] ) {
			printf( _n( $page->args['single'] . ' restored from the trash.', '%s ' . strtolower($page->args['multiple']) . ' restored from the trash.', $_GET['untrashed'] ), number_format_i18n( $_GET['untrashed'] ) );
			unset($_GET['undeleted']);
		}
    	
    	$_SERVER['REQUEST_URI'] = remove_query_arg( array('locked', 'skipped', 'updated', 'deleted'), $_SERVER['REQUEST_URI'] );
    	?>
    	</p></div>
    <?php } ?>
    
    
    <form id="posts-filter" action="" method="get">

    	<p class="search-box">
    		<label class="screen-reader-text" for="post-search-input"><?php _e( 'Search ' . $page->args['multiple'] ); ?>:</label>
    		<input type="text" id="post-search-input" name="s" value="<?php the_search_query(); ?>" />
    		<input type="submit" value="<?php esc_attr_e( 'Search ' . $page->args['multiple'] ); ?>" class="button" />
    	</p>
    	
    	<?php if ( isset($_GET['post_status'] ) ) : ?>
    	<input type="hidden" name="post_status" value="<?php echo esc_attr($_GET['post_status']) ?>" />
    	<?php endif; ?>
    	<input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>" />
    	<input type="hidden" id="cwp_submitted" name="cwp_submitted_<?php echo $page->get_page_id() ?>" value="edit" />
    	<input type="hidden" name="page" value="<?php echo $_GET['page'] ?>" />
    	
    	<ul class="subsubsub">
		<?php
		$post_stati = $page->get_available_post_stati();
		$ccount = 0;
		foreach ( $post_stati as $status => $arr ) { $ccount++;
			$label = $arr[0];
			$count = $arr[1];
			$class = '';
		
			if ( isset($_GET['post_status']) && $status == $_GET['post_status'] )
				$class = ' class="current"';
		
			?>
			<li><a href="<?php echo add_query_arg('post_status', $status) ?>" <?php echo $class ?>><?php echo $label ?></a> (<?php echo $count ?>)</li> <?php echo $ccount != count( $post_stati ) ? '|' : '' ; ?> 
		<?php
		}
		?>
		</ul>


    	<?php do_action( 'cwp_manage_page_hidden_input' ) ?>
    	
    	
    	
    		<div class="tablenav">
    		<?php
    		$page_links = paginate_links( array(
    			'base' => add_query_arg( 'paged', '%#%' ),
    			'format' => '',
    			'prev_text' => __('&laquo;'),
    			'next_text' => __('&raquo;'),
    			'total' => $page->wp_query->max_num_pages,
    			'current' => $_GET['paged']
    		));
    		
    		$is_trash = isset($_GET['post_status']) && $_GET['post_status'] == 'trash';
    		?>
    		
    		<div class="alignleft actions">
    		<select name="action">
				<option value="-1" selected="selected"><?php _e('Bulk Actions'); ?></option>
				<?php if ( $is_trash ) { ?>
				<option value="untrash"><?php _e('Restore'); ?></option>
				<?php } else { ?>
				<option value="edit"><?php _e('Edit'); ?></option>
				<?php } if ( $is_trash || !EMPTY_TRASH_DAYS ) { ?>
				<option value="delete"><?php _e('Delete Permanently'); ?></option>
				<?php } else { ?>
				<option value="trash"><?php _e('Move to Trash'); ?></option>
				<?php } ?>
			</select>
			    		<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
    		<?php wp_nonce_field('bulk-posts'); ?>
    		
    		<?php
    		foreach( (array) $page->filters as $filter ) {
    			switch( $filter['type'] ) :
    				
    				case 'taxonomy' :
    					if( $filter['taxonomy'] == 'category' ) {
    					    $dropdown_options = array('show_option_all' => __('View all categories'), 'hide_empty' => 0, 'hierarchical' => 1,
    					    	'show_count' => 0, 'orderby' => 'name', 'selected' => $_GET['cat']);
    					    
    					    $dropdown_options['child_of'] = $filter['child_of'] ? $filter['child_of'] : null;
    					    
    					    wp_dropdown_categories($dropdown_options);
    					} else {
    					    $dropdown_options = array('show_option_all' => __('View all ' . $filter['multiple'] ), 'hide_empty' => 0, 'hierarchical' => 1,
    					    	'show_count' => 0, 'orderby' => 'name', 'selected' => $_GET[$filter['taxonomy']], 'name' => $filter['taxonomy'] );
    					    cwp_dropdown_taxonomy( $filter['taxonomy'], $dropdown_options);
    							
    					}
    					break;
    				case 'custom' :
    					call_user_func_array( $filter['callback'], array() );
    				default :
    					break;
    			endswitch;
    		}
    		
    		?>
    		<?php

    		do_action('restrict_manage_posts');
    		?>
    		<input type="submit" id="post-query-submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />
    		
    		</div>
    		
    		<?php if ( $page->wp_query->have_posts() ) { ?>
    		
    		<?php if ( $page_links ) { ?>
    		<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
    			number_format_i18n( ( $_GET['paged'] - 1 ) * $page->wp_query->query_vars['posts_per_page'] + 1 ),
    			number_format_i18n( min( $_GET['paged'] * $page->wp_query->query_vars['posts_per_page'], $page->wp_query->found_posts ) ),
    			number_format_i18n( $page->wp_query->found_posts ),
    			$page_links
    		); echo $page_links_text; ?></div>
    		<?php } ?>
    		
    		<div class="clear"></div>
    		</div>
    		
    		<div class="clear"></div>
    		
    		<?php 
    		add_filter( 'manage_cwp_manage_columns', array( $page, 'get_table_columns' ) );
    		cwp_edit_post_rows( $page ); ?>
    		
    		<div class="tablenav">
    		
    			<?php
    			if ( $page_links )
    				echo "<div class='tablenav-pages'>$page_links_text</div>";
    			?>
    			
    			<div class="alignleft actions">
    				<select name="action2">
    				<option value="-1" selected="selected"><?php _e('Bulk Actions'); ?></option>
    				<option value="delete"><?php _e('Delete'); ?></option>
    				</select>
    				<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
    				<br class="clear" />
    			</div>
    			<br class="clear" />
    		</div>
    	
    	<?php } else { // have_posts() ?>
    		<div class="clear"></div>
    		<p><?php _e('No ' . strtolower( $page->args['multiple'] ) . ' found') ?></p>
    	<?php } ?>
    
    </form>


</div>
<?php

function cwp_edit_post_rows( $page ) {
	?>
	<table class="widefat post fixed" cellspacing="0">
	    <thead>
	    <tr>
			<?php print_column_headers('cwp_manage'); ?>
	    </tr>
	    </thead>
	
	    <tfoot>
	    <tr>
			<?php print_column_headers('cwp_manage', false); ?>
	    </tr>
	    </tfoot>
	
	    <tbody>
			<?php cwp_post_rows( $page->wp_query->posts, $page ); ?>
	    </tbody>
	</table>
	<?php
}




function cwp_post_rows( $posts = array(), $page ) {
	global $post, $mode;

	add_filter('the_title','esc_html');

	// Create array of post IDs.
	$post_ids = array();

	foreach ( $posts as $a_post )
		$post_ids[] = $a_post->ID;

	$comment_pending_count = get_pending_comments_num($post_ids);
	if ( empty($comment_pending_count) )
		$comment_pending_count = array();

	foreach ( $posts as $post ) {
	    if ( empty($comment_pending_count[$post->ID]) )
	    	$comment_pending_count[$post->ID] = 0;
		cwp_post_row($post, $comment_pending_count[$post->ID], $mode, $page);
	}
}

function cwp_post_row($a_post, $pending_comments, $mode, $page) {
	global $post;
	
	static $rowclass;

	$global_post = $post;
	$post = $a_post;
	setup_postdata($post);

	$rowclass = 'alternate' == $rowclass ? '' : 'alternate';
	$rowclass = apply_filters( 'cwp_post_row_class', $rowclass, $post, $page );
	
	global $current_user;
	$post_owner = ( $current_user->ID == $post->post_author ? 'self' : 'other' );
	$edit_link = $page->get_edit_link( $post->ID );
	$delete_link = add_query_arg( 'post',  $post->ID, add_query_arg( 'action', 'delete', $page->get_page_url() ) );
	$trash_link = wp_nonce_url( add_query_arg( 'post', $post->ID, add_query_arg( 'action', 'trash', $page->get_page_url()) ), "trash-post_" . $post->ID );
	$untrash_link = wp_nonce_url( add_query_arg( 'post', $post->ID, add_query_arg( 'action', 'untrash', $page->get_page_url()) ), "untrash-post_" . $post->ID );
	$title = _draft_or_post_title();
	?>
	<tr id='post-<?php echo $post->ID; ?>' class='<?php echo trim( $rowclass . ' author-' . $post_owner . ' status-' . $post->post_status ); ?> iedit' valign="top">
	<?php
	$posts_columns = get_column_headers('cwp_manage');
	$hidden = get_hidden_columns('edit');
	foreach ( $posts_columns as $column_name=>$column_display_name ) {
		
		$class = "class=\"$column_name column-$column_name\"";

		$style = '';
		if ( in_array($column_name, $hidden) )
			$style = ' style="display:none;"';

		$attributes = "$class$style";
		
		
		switch ($column_name) {

		case 'cb':
		?>
		<th scope="row" class="check-column"><?php if ( current_user_can( 'edit_post', $post->ID ) ) { ?><input type="checkbox" name="post[]" value="<?php the_ID(); ?>" /><?php } ?></th>
		<?php
		break;

		case 'date':
			if ( '0000-00-00 00:00:00' == $post->post_date && 'date' == $column_name ) {
				$t_time = $h_time = __('Unpublished');
				$time_diff = 0;
			} else {
				$t_time = get_the_time(__('Y/m/d g:i:s A'));
				$m_time = $post->post_date;
				$time = get_post_time('G', true, $post);

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
				else
					$h_time = mysql2date(__('Y/m/d'), $m_time);
			}

			echo '<td ' . $attributes . '>';
			if ( 'excerpt' == $mode )
				echo apply_filters('post_date_column_time', $t_time, $post, $column_name, $mode);
			else
				echo '<abbr title="' . $t_time . '">' . apply_filters('post_date_column_time', $h_time, $post, $column_name, $mode) . '</abbr>';
			echo '<br />';
			if ( 'publish' == $post->post_status ) {
				_e('Published');
			} elseif ( 'future' == $post->post_status ) {
				if ( $time_diff > 0 )
					echo '<strong class="attention">' . __('Missed schedule') . '</strong>';
				else
					_e('Scheduled');
			} else {
				_e('Last Modified');
			}
			echo '</td>';
		break;

		case 'title':
			$attributes = 'class="post-title column-title"' . $style;
		?>
		<td <?php echo $attributes ?>><strong><?php if ( current_user_can( 'edit_post', $post->ID ) ) { ?><a class="row-title" href="<?php echo $edit_link; ?>" title="<?php echo esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $title)); ?>"><?php echo $title ?></a><?php } else { echo $title; }; _post_states($post); ?></strong>
		<?php
			if ( 'excerpt' == $mode )
				the_excerpt();

			$actions = array();
			if ( current_user_can('edit_post', $post->ID) ) {
				$actions['edit'] = '<a href="' . $edit_link . '" title="' . esc_attr(__('Edit this post')) . '">' . __('Edit') . '</a>';
			}
			if ( current_user_can('delete_post', $post->ID) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . esc_attr(__('Restore this post from the Trash')) . "' href='" . $untrash_link . "'>" . __('Restore') . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . esc_attr(__('Move this post to the Trash')) . "' href='" . $trash_link . "'>" . __('Trash') . "</a>";
				if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . esc_attr(__('Delete this post permanently')) . "' href='" . wp_nonce_url($delete_link, 'delete-post_' . $post->ID) . "'>" . __('Delete Permanently') . "</a>";
			}
			/*
			if ( current_user_can('delete_post', $post->ID) ) {
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr(__('Delete this post')) . "' href='" . wp_nonce_url($delete_link, 'delete-post_' . $post->ID) . "' onclick=\"if ( confirm('" . esc_js(sprintf( ('draft' == $post->post_status) ? __("You are about to delete this draft '%s'\n 'Cancel' to stop, 'OK' to delete.") : __("You are about to delete this post '%s'\n 'Cancel' to stop, 'OK' to delete."), $post->post_title )) . "') ) { return true;}return false;\">" . __('Delete') . "</a>";
			}
			*/
			if ( in_array($post->post_status, array('pending', 'draft')) ) {
				if ( current_user_can('edit_post', $post->ID) )
					$actions['view'] = '<a href="' . get_permalink($post->ID) . '" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $title)) . '" rel="permalink">' . __('Preview') . '</a>';
			} else {
				$actions['view'] = '<a href="' . get_permalink($post->ID) . '" title="' . esc_attr(sprintf(__('View &#8220;%s&#8221;'), $title)) . '" rel="permalink">' . __('View') . '</a>';
			}
			$actions = apply_filters('post_row_actions', $actions, $post);
			$action_count = count($actions);
			$i = 0;
			echo '<div class="row-actions">';
			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				echo "<span class='$action'>$link$sep</span>";
			}
			echo '</div>';

			get_inline_data($post);
		?>
		</td>
		<?php
		break;

		case 'categories':
		?>
		<td <?php echo $attributes ?>><?php
			$categories = get_the_category();
			if ( !empty( $categories ) ) {
				$out = array();
				foreach ( $categories as $c )
					$out[] = "<a href='edit.php?category_name=$c->slug'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "</a>";
					echo join( ', ', $out );
			} else {
				_e('Uncategorized');
			}
		?></td>
		<?php
		break;

		case 'tags':
		?>
		<td <?php echo $attributes ?>><?php
			$tags = get_the_tags($post->ID);
			if ( !empty( $tags ) ) {
				$out = array();
				foreach ( $tags as $c )
					$out[] = "<a href='edit.php?tag=$c->slug'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'post_tag', 'display')) . "</a>";
				echo join( ', ', $out );
			} else {
				_e('No Tags');
			}
		?></td>
		<?php
		break;

		case 'comments':
		?>
		<td <?php echo $attributes ?>><div class="post-com-count-wrapper">
		<?php
			$pending_phrase = sprintf( __('%s pending'), number_format( $pending_comments ) );
			if ( $pending_comments )
				echo '<strong>';
				comments_number("<a href='edit-comments.php?p=$post->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link */ _x('0', 'comment count') . '</span></a>', "<a href='edit-comments.php?p=$post->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link */ _x('1', 'comment count') . '</span></a>', "<a href='edit-comments.php?p=$post->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link: % will be substituted by comment count */ _x('%', 'comment count') . '</span></a>');
				if ( $pending_comments )
				echo '</strong>';
		?>
		</div></td>
		<?php
		break;

		case 'author':
		?>
		<td <?php echo $attributes ?>><a href="edit.php?author=<?php the_author_meta('ID'); ?>"><?php the_author() ?></a></td>
		<?php
		break;

		case 'control_view':
		?>
		<td><a href="<?php the_permalink(); ?>" rel="permalink" class="view"><?php _e('View'); ?></a></td>
		<?php
		break;

		case 'control_edit':
		?>
		<td><?php if ( current_user_can('edit_post', $post->ID) ) { echo "<a href='$edit_link' class='edit'>" . __('Edit') . "</a>"; } ?></td>
		<?php
		break;

		case 'control_delete':
		?>
		<td><?php if ( current_user_can('delete_post', $post->ID) ) { echo "<a href='" . wp_nonce_url("post.php?action=delete&amp;post=$id", 'delete-post_' . $post->ID) . "' class='delete'>" . __('Delete') . "</a>"; } ?></td>
		<?php
		break;
		
		case is_taxonomy( $column_name ):
			?>
			<td <?php echo $attributes ?>><?php
			$tags = wp_get_object_terms($post->ID, $column_name);
			if ( !empty( $tags ) ) {
				$out = array();
				foreach ( $tags as $c )
					$out[] = "<a href=\"" . $page->get_edit_term_link( $column_name, $c ) . '">' . $c->name . "</a>";
				echo join( ', ', $out );
			} else {
				_e('No ' . $column_display_name);
			}
			?></td>
			<?php
			break;
		
		case function_exists( $column_name ): ?>
			
			<td>
			<?php echo call_user_func_array( $column_name, array($post) ); ?>
			</td>
			<?php
			break;
		
		default:
		?>
		<td <?php echo $attributes ?>><?php do_action('manage_posts_custom_column', $column_name, $post->ID); ?></td>
		<?php
		break;
	}
	}
	?>
	</tr>
	<?php
	$post = $global_post;
}


/**
 * Display or retrieve the HTML dropdown list of terms in a taxonomy.
 *
 * The list of arguments is below:
 *     'show_option_all' (string) - Text to display for showing all categories.
 *     'show_option_none' (string) - Text to display for showing no categories.
 *     'orderby' (string) default is 'ID' - What column to use for ordering the
 * categories.
 *     'order' (string) default is 'ASC' - What direction to order categories.
 *     'show_last_update' (bool|int) default is 0 - See {@link get_categories()}
 *     'show_count' (bool|int) default is 0 - Whether to show how many posts are
 * in the category.
 *     'hide_empty' (bool|int) default is 1 - Whether to hide categories that
 * don't have any posts attached to them.
 *     'child_of' (int) default is 0 - See {@link get_categories()}.
 *     'exclude' (string) - See {@link get_categories()}.
 *     'echo' (bool|int) default is 1 - Whether to display or retrieve content.
 *     'depth' (int) - The max depth.
 *     'tab_index' (int) - Tab index for select element.
 *     'name' (string) - The name attribute value for selected element.
 *     'class' (string) - The class attribute value for selected element.
 *     'selected' (int) - Which category ID is selected.
 *
 * The 'hierarchical' argument, which is disabled by default, will override the
 * depth argument, unless it is true. When the argument is false, it will
 * display all of the categories. When it is enabled it will use the value in
 * the 'depth' argument.
 *
 * @since 2.1.0
 *
 * @param string|array $args Optional. Override default arguments.
 * @return string HTML content only if 'echo' argument is 0.
 */
function cwp_dropdown_taxonomy( $taxonomy, $args = '' ) {
	$defaults = array(
		'show_option_all' => '', 'show_option_none' => '',
		'orderby' => 'id', 'order' => 'ASC',
		'show_last_update' => 0, 'show_count' => 0,
		'hide_empty' => 1, 'child_of' => 0,
		'exclude' => '', 'echo' => 1,
		'selected' => 0, 'hierarchical' => 0,
		'name' => 'cat', 'class' => 'postform',
		'depth' => 0, 'tab_index' => 0
	);

	$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

	$r = wp_parse_args( $args, $defaults );
	$r['include_last_update_time'] = $r['show_last_update'];
	extract( $r );

	$tab_index_attribute = '';
	if ( (int) $tab_index > 0 )
		$tab_index_attribute = " tabindex=\"$tab_index\"";

	$categories = get_terms( $taxonomy, $r );
		
	$output = '';
	if ( ! empty( $categories ) ) {
		$output = "<select name='$name' id='$name' class='$class' $tab_index_attribute>\n";

		if ( $show_option_all ) {
			$show_option_all = apply_filters( 'list_cats', $show_option_all );
			$selected = ( '0' === strval($r['selected']) ) ? " selected='selected'" : '';
			$output .= "\t<option value='0'$selected>$show_option_all</option>\n";
		}

		if ( $show_option_none ) {
			$show_option_none = apply_filters( 'list_cats', $show_option_none );
			$selected = ( '-1' === strval($r['selected']) ) ? " selected='selected'" : '';
			$output .= "\t<option value='-1'$selected>$show_option_none</option>\n";
		}

		if ( $hierarchical )
			$depth = $r['depth'];  // Walk the full depth.
		else
			$depth = -1; // Flat.
		$output .= walk_category_dropdown_tree( $categories, $depth, $r );
		$output .= "</select>\n";
	}

	$output = apply_filters( 'wp_dropdown_cats', $output );

	if ( $echo )
		echo $output;

	return $output;
}
?>