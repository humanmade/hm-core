<?php
class Admin_Section {
	var $name;
	var $pages;
	var $origin;
	var $args;
	
	/**
	 * __construct function.
	 * 
	 * @param stirng $name - The title of the section
	 * @param string $file - The Base file (use __FILE__ is not known)
	 * @param array $args. (default: array())
	 *		'menu_order' => int - default: last
	 */
	function __construct( $name, $file, $args = array() ) {
		$this->name = $name;
		$this->origin = $file;
		$this->args = wp_parse_args( $args );
		$this->set_menu_order();
	}
	
	/**
	 * Adds a new admin page to the admin section.
	 * 
	 * @param string $type - manage, edit, taxonomy, settings, debug, custom (via filter)
	 * @param string $name - title of the page in the menu
	 * @param bool $parent. (default: false) - if this is the parent page
	 * @param array $args. (default: array()) - args passed to the page
	 */
	function add_page( $type, $name, $parent = false, $args = array() ) {
		switch( $type ) : 
			case 'manage' :
				$page = new CWP_Manage_Page( $this, $name, $parent, $args );
				break;
			case 'edit' :
				$page = new CWP_Edit_Page( $this, $name, $parent, $args );
				break;
			case 'taxonomy' :
				$page = new CWP_Taxonomy_Page( $this, $name, $parent, $args );
				break;
			case 'settings' :
				$page = new CWP_Settings_Page( $this, $name, $parent, $args );
				break;
			case 'debug' :
				$page = new CWP_Debug_Page( $this, $name, $args );
				break;
			default:
				$page = apply_filters( 'cwp_add_page', null, $this, $name, $parent );
				break;
		endswitch;
		
		if( !$page )
			return;
		
				
		remove_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
				
		$this->pages[$page->get_page_id()] = $page;

		//enqueue any scripts here
		if( method_exists( $page, 'enqueue' ) && $_GET['page'] && $_GET['page'] === $page->page_name )
			add_action( 'admin_init', array( $page, 'enqueue' ) );

		return $page;
		
	}
	function get_section_file_name() {
		foreach( $this->pages as $page ) {
			if( $page->is_parent === true ) {
				return str_replace( ABSPATH . 'wp-content/plugins/', '', $this->origin . '/' . sanitize_title($page->name) );
			}
		}
	}
	function add_menu_pages() {
		foreach( $this->pages as $page ) {
			//check if there is a current status that should be different
			if( isset($page->args['active']) && $page->is_current() ) {
				$menu_name = $page->args['active'];
			} else {
				$menu_name = $page->name;
			}
			
			if( $page->is_parent === true ) {
				add_menu_page($this->name, $this->name, 4, $this->origin . '/' . sanitize_title($page->name), array( $page, 'display' ), $this->args['icon'] );
				$parent = $this->origin . '/' . sanitize_title($page->name);
			}
			if( $page->type !== 'settings' ) 
				add_submenu_page( $parent, $menu_name, $page->args['show_in_menu'] === false ? '' : $menu_name, 4, $this->origin . '/' . sanitize_title($page->name), array( $page, 'display' ) );
			else
				add_options_page($page->name, $page->name, 'manage_options', sanitize_title($page->name), array( $page, 'display' ));
		}
	}
	
	function set_menu_order() {
		add_filter( 'custom_menu_order', array($this, 'set_custom_order_true') );
		add_filter( 'menu_order', array($this, 'do_menu_order') );
	}
	
	function do_menu_order( $menu ) {
		$new_menu = array();
		foreach( $menu as $pos => $item ) {
			if( $item == $this->get_section_file_name() )
				continue;
			
			$new_menu[] = $item;
			
			if( $pos == (int) $this->args['menu_order'] )
				$new_menu[] = $this->get_section_file_name();

		}
		return $new_menu;
	
	}
	
	function set_custom_order_true() {
		return true;
	}
	
	function check_for_submitted() {
		foreach( $this->pages as $page ) {
			$page->check_for_submitted();
		}
	}
}

class Admin_Extension {
	function __construct( $name, $top_level_page, $args ) {
		$this->name = $name;
		$this->top_level_page = $top_level_page;
		$this->args = wp_parse_args( $args );
		$this->origin = $top_level_page;
		$this->pages = array();
	}
	function add_page( $type, $name, $args = array() ) {
		switch( $type ) : 
			case 'manage' :
				$page = new CWP_Manage_Page( $this, $name, $parent, $args );
				break;
			case 'edit' :
				$page = new CWP_Edit_Page( $this, $name, $parent, $args );
				break;
			case 'taxonomy' :
				$page = new CWP_Taxonomy_Page( $this, $name, $parent, $args );
				break;
			case 'settings' :
				$page = new CWP_Settings_Page( $this, $name, $parent, $args );
				break;
			default:
				$page = apply_filters( 'cwp_add_page', null, $this, $name, $parent );
				break;
		endswitch;
		if( !$page || is_a( $page, 'Admin_Extension' ) )
			return;
		
		remove_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		$this->pages[] = $page;
		
		//enqueue any scripts here
		add_action( 'admin_init', array( $page, 'enqueue' ) );

		return $page;
		
	}
	
	function add_menu_pages() {
		foreach( $this->pages as $page ) {
			add_submenu_page( $this->top_level_page, $page->name, $page->args['show_in_menu'] === false ? '' : $page->name, 4, $this->origin . '/' . sanitize_title($page->name), array( $page, 'display' ) );
		}
	}
	function check_for_submitted() {
		foreach( $this->pages as $page ) {
			$page->check_for_submitted();
		}
	}
	
}

class CWP_Manage_Page extends CWP_Page {
	var $wp_query;
	var $query;
	var $admin_section;
	var $is_parent;
	var $name;
	var $filters;
	var $table_columns;
	/**
	 * __construct function.
	 * 
	 * @param mixed $admin_section
	 * @param mixed $name
	 * @param mixed $parent
	 * @param mixed $args
	 		'title' => The Page's title
	 		'single' => single item's name
	 		'multiple' => mutliple item varient
	 		'edit' => which page to use as the edit page - 'name' of cwp page - or post.php for wp 
	 * @return void
	 */
	function __construct( $admin_section, $name, $parent, $args ) {
		parent::__construct( $admin_section, $name, $parent, $args );
	}
	/**
	 * Query args to set up the page query
	 * 
	 * @param mixed $args - wp query args
	 */
	function query( $args ) {
		$this->query = wp_parse_args($args);
		
	}
	function run_query( $args ) {
		$defaults = array( 'post_type' => 'post' );
		$args = wp_parse_args( $args, $defaults);
		if( $this->query['post_status'] && !$args['post_status'] ){
			global $cwp_custom_post_status;
			$cwp_custom_post_status = $this->query['post_status'];
		}
		$this->wp_query = new WP_Query($args);
	}
	function display() {
		$page = $this;
		include('pages/cwp.page.manage.php');
	}
	function get_title() {
		return $this->args['title'] ? $this->args['title'] : $this->name . ' ' . $this->admin_section->args['multiple'];
	}
	function enqueue() {
		
	}
	function get_page_id() {
		return 'cwp_manage_' . sanitize_title($this->name);
	}
	
	function set_table_column($key, $value = null, $args = array() ) {
		if( $key === 'cb' && $value === null )
			$value = '<input type="checkbox" />';
		
		$this->table_column_args[$key] = wp_parse_args($args);
		$this->table_columns[$key] = $value;
	}
	function get_table_columns( $columns ) {
		$defaults = wp_manage_posts_columns();
		return $this->table_columns ? $this->table_columns : $defaults ;
	}
	function get_edit_link( $post_id ) {
		if( $this->args['edit'] == 'post.php' )
			return get_bloginfo('wpurl') . '/wp-admin/post.php?action=edit&post=' . $post_id;
			
		$origin_rel = str_replace( ABSPATH . 'wp-content/plugins/', '', $this->admin_section->origin );
		$origin_rel .= '/' . sanitize_title($this->args['edit']);
		$origin_rel = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' . $origin_rel;
		$url = add_query_arg( 'p', $post_id, $origin_rel );
		return $url;
	}
	function add_filter( $args ) {
		$args = wp_parse_args($args);
		$this->filters[] = $args;
	}
	function check_for_submitted() {
		if( !$this->is_current() )
			return;
		// Handle bulk actions
		if ( isset($_GET['delete_all']) || isset($_GET['delete_all2']) ) {
			$post_status = preg_replace('/[^a-z0-9_-]+/i', '', $_GET['post_status']);
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='post' AND post_status = %s", $post_status ) );
			$doaction = 'delete';
		} elseif ( ( $_GET['action'] != -1 || $_GET['action2'] != -1 ) && ( isset($_GET['post']) || isset($_GET['ids']) ) ) {
			$post_ids = isset($_GET['post']) ? array_map( 'intval', (array) $_GET['post'] ) : explode(',', $_GET['ids']);
			$doaction = ($_GET['action'] != -1) ? $_GET['action'] : $_GET['action2'];
		} else {
			return;
		}
		
		$sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
			
		switch ( $doaction ) {
		    case 'trash':
		    	$trashed = 0;
		    	foreach( (array) $post_ids as $post_id ) {
		    		if ( !current_user_can('delete_post', $post_id) )
		    			wp_die( __('You are not allowed to move this post to the trash.') );
		    
		    		if ( !wp_trash_post($post_id) )
		    			wp_die( __('Error in moving to trash...') );
		    
		    		$trashed++;
		    	}
		    	$sendback = add_query_arg( array('trashed' => $trashed, 'ids' => join(',', $post_ids)), $sendback );
		    	break;
		    case 'untrash':
		    	$untrashed = 0;
		    	foreach( (array) $post_ids as $post_id ) {
		    		if ( !current_user_can('delete_post', $post_id) )
		    			wp_die( __('You are not allowed to restore this post from the trash.') );
		    
		    		if ( !wp_untrash_post($post_id) )
		    			wp_die( __('Error in restoring from trash...') );
		    
		    		$untrashed++;
		    	}
		    	$sendback = add_query_arg('untrashed', $untrashed, $sendback);
		    	break;
		
		    case 'delete':
		    	$deleted = 0;
				foreach( (array) $post_ids as $post_id ) {
					$post_del = & get_post($post_id);
				
					if ( !current_user_can('delete_post', $post_id) )
						wp_die( __('You are not allowed to delete this post.') );
				
					if ( $post_del->post_type == 'attachment' ) {
						if ( ! wp_delete_attachment($post_id) )
							wp_die( __('Error in deleting...') );
					} else {
						if ( !wp_delete_post($post_id) )
							wp_die( __('Error in deleting...') );
					}
					$deleted++;
				}
				$sendback = add_query_arg('deleted', $deleted, $sendback);
				break;
		}
		
		if ( strpos($sendback, 'post.php') !== false ) $sendback = admin_url('post-new.php');
		elseif ( strpos($sendback, 'attachments.php') !== false ) $sendback = admin_url('attachments.php');
		
		if ( isset($done) ) {
		    $done['updated'] = count( $done['updated'] );
		    $done['skipped'] = count( $done['skipped'] );
		    $done['locked'] = count( $done['locked'] );
		    $sendback = add_query_arg( $done, $sendback );
		}
		if ( isset($deleted) )
		    $sendback = add_query_arg('deleted', $deleted, $sendback);
		
		if ( isset($_GET['action']) )
		    	$sendback = remove_query_arg( array('action', 'action2', 'cat', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view', 'post_type'), $sendback );
		
		wp_redirect($sendback);
		exit();
		
		if( $_GET['delete'] && !$_GET['action'] && is_numeric( $_GET['delete'] ) ) {
			wp_delete_post($_GET['delete']);
			wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce', 'delete'), stripslashes($_SERVER['REQUEST_URI']) ) );
			exit;
		}
	}
	
	function get_available_post_stati() {
		$stati = (array) wp_count_posts( $this->wp_query->query_vars['post_type'] );

		$total_posts = array_sum( (array) $stati ) - $stati['trash'];
		
		if( array_search( 'All', $this->get_post_stati() ) === false )
			$available[''] = array( 'All', $total_posts );

		foreach ( $this->get_post_stati() as $status => $label ) {
			if ( !in_array( $status, $stati ) )
				continue;
		
			if ( empty( $stati[$status] ) )
				continue;
			$available[$status] = array( $label, $stati[$status] );
		}
		return $available;
	}
	function get_post_stati() {
		return (array) $this->post_stati;
	}
	
	function add_post_stati( $status, $name ) {
		$this->post_stati[$status] = $name;
	}


	function get_edit_term_link( $column_name, $term ) {
		$origin_rel = str_replace( ABSPATH . 'wp-content/plugins/', '', $this->admin_section->origin );
		$origin_rel .= '/' . sanitize_title( $this->table_column_args[$column_name]['edit'] );
		$origin_rel = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' . $origin_rel;
		$url = add_query_arg( 'term', $term->term_id, $origin_rel );
		return $url;
	}
}

class CWP_Edit_Page extends CWP_Page {
	var $wp_query;
	var $query;
	var $admin_section;
	var $is_parent;
	var $name;
	var $meta_boxes;
	var $post_args;
	
	function __construct( $admin_section, $name, $parent, $args ) {
		parent::__construct( $admin_section, $name, $parent, $args );
	}
	function display() {
		$page = $this;
		include('pages/cwp.page.edit.php');
	}
	function get_title() {
		return $this->args['title'] ? $this->args['title'] : $this->name . ' ' . $this->admin_section->args['single'];
	}
	function enqueue() {
		
		foreach( (array) $this->enqueued_scripts as $script ) {
			wp_enqueue_script( $script[0], $script[1], $script[2] );
		}
		
		if ( user_can_richedit() )
		    wp_enqueue_script('editor');
		add_thickbox();
		
		wp_enqueue_script('media-upload');
		wp_enqueue_script('word-count');
		wp_enqueue_script( 'admin-comments' );
		wp_enqueue_script( 'post' );
	}
	
	function enqueue_script( $id, $path, $deps ) {
		parent::enqueue_script( $id, $path, $deps );
	}
	
	function add_meta_box( $id, $title = null, $position = 'normal', $callback = null, $args = array() ) {
		$args = wp_parse_args( $args );
		if( !$this->add_default_meta_box($id, $args) ) {
			$this->meta_boxes[$id] = array( $id, $title, $callback, $this->get_page_id(), $position, $args);
		}
	}
	function remove_meta_box() {
	
	}
	function get_meta_boxes() {
	}
	function get_page_id() {
		return 'cwp_manage_' . sanitize_title($this->name);
	}
	function add_default_meta_box( $id, $args ) {
		include_once( ABSPATH . 'wp-admin/includes/meta-boxes.php' );
		switch( $id ) : 
			case 'publish':
				$this->meta_boxes[$id] = array('submitdiv', __('Publish'), 'post_submit_meta_box', $this->get_page_id(), 'side');
				return true;
			case 'taxonomy' :
				$this->meta_boxes[$id] = array( 'tagsdiv-' . $args['taxonomy'], $args['title'], 'post_tags_meta_box', $this->get_page_id(), 'side' );
				return true;
		endswitch;
	}
	/**
	 * Used to set the default arg for new posts / edited
	 * 
	 * @param string $key - post key, e.g. post_type
	 * @param string $value - value, eg 'post'
	 */
	function add_post_arg( $key, $value ) {
		$this->post_args[$key] = $value;
	}
	function check_for_submitted() {
		if( !$_POST['cwp_submitted_' . $this->get_page_id()] || !$this->is_current() )
			return false;
		do_action( 'cwp_submitted_' . $this->get_page_id() );
		
		if( $_POST['post_ID'] > 0 )
			$mode = 'edit';
		else
			$mode = 'new';
		
		include_once( ABSPATH . '/wp-admin/includes/post.php' );
		
				
		if( $mode === 'edit' )
			$post_id = edit_post($_POST);
		else
			$post_id = wp_write_post($_POST);

		$post = get_post( $post_id );
		foreach( $this->meta_boxes as $box ) {
			if( function_exists( $function = $box[2] . '_submitted' ) ) {
				call_user_func( $function, $post, $box[5] );
			}
		}
		
		$message = $mode === 'edit' ? 1 : 6;
		$message = $_POST['post_status'] === 'pending' ? 7 : $message;
		
		wp_redirect( add_query_arg( 'p', $post_id, add_query_arg( 'message', $message ) ) );
		exit;

	}
}

class CWP_Taxonomy_Page extends CWP_Page {
	var $query;
	var $admin_section;
	var $is_parent;
	var $name;
	
	function __construct( $admin_section, $name, $parent, $args ) {
		parent::__construct( $admin_section, $name, $parent, $args );
	}
	function display() {
		$page = $this;
		if( $_GET['term'] )
			include('pages/cwp.page.taxonomy.edit.php');
		else
			include('pages/cwp.page.taxonomy.manage.php');
	}
	function get_title() {
		return $this->args['title'] ? $this->args['title'] : 'Manage ' . $this->args['multiple'];
	}
	function enqueue() {
	
		$can_manage = current_user_can('manage_categories');
		wp_enqueue_script('admin-tags');
		if ( $can_manage )
			wp_enqueue_script('inline-edit-tax');
		
		foreach( (array) $this->enqueued_scripts as $script ) {
			wp_enqueue_script( $script[0], $script[1], $script[2] );
		}
	
	}
	function enqueue_script( $id, $path, $deps ) {
		parent::enqueue_script( $id, $path, $deps );
	}
	
	function get_page_id() {
		return parent::get_page_id(); 
	}

	function check_for_submitted() {		
		if( !$_REQUEST['cwp_submitted_' . $this->get_page_id() ] )
			return;
			
		$action = $_REQUEST['action'];
		$taxonomy = $_REQUEST['taxonomy'];
		if ( isset( $_GET['action'] ) && isset($_GET['delete_tags']) && ( 'delete' == $_GET['action'] || 'delete' == $_GET['action2'] ) )
			$action = 'bulk-delete';
		
		do_action( 'cwp_taxonomy_page_form_submitted_pre', array( $this ) );
		
		switch($action) {
		
		case 'addtag':
		
			check_admin_referer('add-tag');
		
			if ( !current_user_can('manage_categories') )
				wp_die(__('Cheatin&#8217; uh?'));
			
		
			$ret = wp_insert_term($_POST['name'], $taxonomy, $_POST);
			
			if( !is_wp_error( $ret ) )
				do_action( 'cwp_taxonomy_page_form_submitted_term_added', $this, $ret['term_id'], $taxonomy );
			
			if ( $ret && !is_wp_error( $ret ) ) {
				wp_redirect( add_query_arg( 'message', 1 ) );
			} else {
				wp_redirect( add_query_arg( 'message', 4) );
			}
			exit;
		break;
		
		case 'delete':
			$tag_ID = (int) $_GET['tag_ID'];
			check_admin_referer('delete-tag_' .  $tag_ID);
		
			if ( !current_user_can('manage_categories') )
				wp_die(__('Cheatin&#8217; uh?'));
		
			wp_delete_term( $tag_ID, $taxonomy);
		
			$location = $this->get_page_url();
			if ( $referer = wp_get_referer() ) {
				if ( false !== strpos($referer, 'edit-tags.php') )
					$location = $referer;
			}
		
			$location = add_query_arg('message', 2, $location);
			wp_redirect($location);
			exit;
		
		break;
		
		case 'bulk-delete':
			check_admin_referer('bulk-tags');
		
			if ( !current_user_can('manage_categories') )
				wp_die(__('Cheatin&#8217; uh?'));
		
			$tags = $_GET['delete_tags'];
			foreach( (array) $tags as $tag_ID ) {
				wp_delete_term( $tag_ID, $taxonomy);
			}
		
			$location = $this->get_page_url();
			if ( $referer = wp_get_referer() ) {
				if ( false !== strpos($referer, 'edit-tags.php') )
					$location = $referer;
			}
		
			$location = add_query_arg('message', 6, $location);
			wp_redirect($location);
			exit;
		
		break;
		
		case 'editedtag':
			$tag_ID = (int) $_REQUEST['tag_ID'];
			check_admin_referer('update-tag_' . $tag_ID);
		
			if ( !current_user_can('manage_categories') )
				wp_die(__('Cheatin&#8217; uh?'));
		
			$ret = wp_update_term($tag_ID, $taxonomy, $_POST);
		
			$location = $this->get_page_url();
			if ( $referer = wp_get_original_referer() ) {
				if ( false !== strpos($referer, 'edit-tags.php') )
					$location = $referer;
			}
			
			if ( $ret && !is_wp_error( $ret ) )
				do_action( 'cwp_taxonomy_page_form_submitted_term_updated', $this, $tag_ID, $taxonomy );
			
			if ( $ret && !is_wp_error( $ret ) )
				$location = add_query_arg('message', 3, $location);
			else
				$location = add_query_arg('message', 5, $location);
		
			wp_redirect($location);
			exit;
		break;
		
		default:
			if ( isset($_GET['_wp_http_referer']) && ! empty($_GET['_wp_http_referer']) ) {
				 wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI']) ) );
				 exit;
			}
		}
	}
	
	function get_edit_term_link( $term ) {
		return add_query_arg( 'term', $term->term_id );
	}
	
	function get_page_url() {
		$origin_rel = str_replace( ABSPATH . 'wp-content/plugins/', '', $this->admin_section->origin );
		$origin_rel .= '/' . sanitize_title($this->name);
		return get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' . $origin_rel;
		
	}
}

class CWP_Settings_Page extends CWP_Page {
	var $query;
	var $admin_section;
	var $is_parent;
	var $name;
	
	function __construct( $admin_section, $name, $parent, $args ) {
		parent::__construct( $admin_section, $name, $parent, $args );
		$this->type = 'settings';
	}
	function display() {
		$page = $this;
		include('pages/cwp.pages.settings.php');
	}
	function get_title() {
		return $this->args['title'] ? $this->args['title'] : 'Manage ' . $this->args['multiple'];
	}
	
	function get_page_id() {
		return parent::get_page_id(); 
	}
	
	function register_setting( $settings ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		register_setting( $this->get_page_id(), $settings  );
	}
	function add_settings_section( $id, $title, $text = '' ) {
		include_once( ABSPATH . 'wp-admin/includes/template.php' );
		add_settings_section($id, $title, array($this, 'section_output'), $this->get_page_id());
	}
	function add_settings_field( $id, $title, $text = '', $section, $default = null, $args = array()) {
		$args = wp_parse_args( $args, array( 'type' => 'text' ) );
		$function = create_function('', 'echo \'<input type="' . $args['type'] . '"' . ($args['type'] === 'checkbox' && get_option( $id, $default ) === $default ? ' checked="checked" ' : '' ) . ' name="' . $id . '"' . ($args['type'] !== 'checkbox' ? 'value="' . get_option( $id, $default ) . '"' : '') . ' />\' . \' ' . $text . '\';');
		add_settings_field($id, $title, $function, $this->get_page_id(), $section);
	
	}
	function section_output() {
		
	}
	function check_for_submitted() {		
		
	}
}

class CWP_Debug_Page extends CWP_Page {
	function __construct(  $admin_section, $name, $args ) {
		$defaults = array( 'subject' => 'Debug Email' );
		parent::__construct( $admin_section, $name, null, $args, $defaults );
		$this->args['show_in_menu'] = false;
	}
	function check_for_submitted() {		
		if( !$this->is_current() )
			return false;
		
		if( $_GET['action'] === 'remote_send_report' && wp_verify_nonce( $_GET['_wpnonce'], 'remote_send_report' ) ) {
			$this->email_info();
			wp_redirect( add_query_arg('message', 'sent', $this->get_page_url()) );
			exit;
		}
	}
	function get_remote_send_report_url() {
		return wp_nonce_url( add_query_arg( 'action', 'remote_send_report', $this->get_page_url() ), 'remote_send_report' );
	}
	
	function email_info() {
		$success = wp_mail( $this->args['email'], $this->args['subject'], var_export( $this->debug_info(), true ) );
	}
	
	function display() {
		?>
		<h2 class="title">Debug Info</h2>
		<?php if( $_GET['message'] == 'sent' ) : ?>
			<div class="updated fade"><p>
				Error report successfully sent!
			</p></div>
		<?php endif; ?>
		<p><a class="button" href="<?php echo $this->get_remote_send_report_url() ?>">Send Report</a> | <small><a href="javascript:jQuery('#error-report').slideToggle(); return false;">View Report</a></small></p>
		<pre class="hidden" id="error-report">
		<?php
		var_export( $this->debug_info() );
		?>
		</pre>
		<?php
	}
	
	function add_debug_info($info = array()) {
		$this->extra_info_array = (array) $info;
	} 
	function debug_info() {
		$info = array( 
			'url' => get_bloginfo('url'),
			'wpurl' => get_bloginfo('wpurl'),
			'options' => array(
				'permalink_structure' => get_option('permalink_structure'),
			),
			
		);
		$info = array_merge_recursive( $info, (array) $this->extra_info_array );
		return apply_filters( 'cwp_debug_info_array', $info, $this ); 
	}
}

class CWP_Page {
	function __construct( $admin_section = null, $name = null, $parent = null, $args = null, $defaults = null ) {
		
		$this->admin_section = $admin_section;
		$this->is_parent = $parent;
		$this->name = $name;
		$this->args = wp_parse_args( $args, $defaults );
		
		$origin_rel = str_replace( ABSPATH . 'wp-content/plugins/', '', $this->admin_section->origin );
		$origin_rel .= '/' . sanitize_title($this->name);
		$this->page_name = $origin_rel;
	}
	function enqueue_script( $id, $path, $deps ) {
		$this->enqueued_scripts[$id] = array( $id, $path, $deps );
	}
	function get_page_id() {
		return sanitize_title( get_class($this) . '_' . $this->name);
	}
	function is_current() {
		return $_GET['page'] === $this->get_page_var();
	}
	function get_page_url() {
		return get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' . $this->get_page_var();
	}
	function get_page_var() {
		$origin_rel = str_replace( ABSPATH . 'wp-content/plugins/', '', $this->admin_section->origin );
		$origin_rel .= '/' . sanitize_title($this->name);
		return $origin_rel;
	}
	
	function display() {
	
	}
}

?>