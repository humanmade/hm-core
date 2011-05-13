<?php

/**
 * hm_debug function.
 *
 * @access public
 * @param mixed $code
 * @param bool $output. (default: true)
 * @return void
 */
function hm( $code, $output = true ) {

	if ( $output ) : ?>

		<style>
			.hm_debug { word-wrap: break-word; white-space: pre; text-align: left; position: relative; background-color: rgba(0, 0, 0, 0.8); font-size: 11px; color: #a1a1a1; margin: 10px; padding: 10px; margin: 0 auto; width: 80%; overflow: auto;  -moz-border-radius: 5px; -webkit-border-radius: 5px; text-shadow: none; }
		</style>
		<br /><pre class="hm_debug">
	<?php endif;
	if ( is_null( $code ) || is_string($code) || is_int( $code ) || is_bool($code) || is_float( $code ) ) :
		if ( $output )
			var_dump( $code );
		else
			var_export( $code, true );
	else :
		if ( $output )
			print_r( $code );
		else
			print_r( $code, true );
	endif;

	if ( $output )
		echo '</pre><br />';

}

function hm_log( $code ) {

	$output = false;

	if ( is_null( $code ) || is_string($code) || is_int( $code ) || is_bool($code) || is_float( $code ) ) :
		if ( $output )
			var_dump( $code );
		else
			$code = var_export( $code, true );
	else :
		if ( $output )
			print_r( $code );
		else
			$code = print_r( $code, true );
	endif;

	error_log( $code );

}

/**
 * hm_alert function.
 *
 * @access public
 * @param mixed $code
 * @return void
 */
function hm_alert( $code ) {
	echo '<script type="text/javascript"> alert("';
	hm_debug( $code );
	echo '")</script>';
}

/**
 * hm_human_post_time function.
 *
 * @access public
 * @param string $timestamp. (default: 'current')
 * @return void
 */
function hm_human_post_time( $timestamp = 'current' ) {

	if ( empty( $timestamp ) ) return false;
	if ( $timestamp === 'current' ) $timestamp = time();

	if ( abs( time() - date( 'G', $timestamp ) ) < 86400 )
		return human_time_diff( date( 'G', $timestamp ) );

	else return date( 'Y/m/d g:i:s A', $timestamp );
}

/**
 * hm_parse_user function.
 *
 * @access public
 * @param mixed $user. (default: null)
 * @return void
 */
function hm_parse_user( $user = null ) {
	if ( is_object( $user ) && is_numeric( $user->ID ) ) return get_userdata( $user->ID );
	if ( is_object( $user ) && is_numeric( $user->user_id ) ) return get_userdata( $user->user_id );
	if ( is_array( $user ) && is_numeric( $user['ID'] ) ) return get_userdata( $user['ID'] );
	if ( is_numeric( $user ) ) return get_userdata( $user );
	if ( is_string( $user ) ) return get_userdatabylogin( $user );
	if ( is_null( $user ) ) :
		global $current_user;
		return get_userdata( $current_user->ID );
	endif;
}

/**
 * hm_parse_post function.
 *
 * @access public
 * @param mixed $post. (default: null)
 * @return void
 */
function hm_parse_post( $post = null ) {

	if ( is_object( $post ) || is_array( $post ) )
		return (object) $post;

	if ( is_numeric( $post ) )
		return get_post( $post );

	if ( is_null( $post ) ) :
		global $post;
		return $post;
	endif;

	if ( is_string( $post ) && get_page_by_title( $post ) )
		return get_page_by_title( $post );

	if ( is_string( $post ) && get_page_by_path( $post ) )
		return get_page_by_path( $post );

	if ( is_string( $post ) ) :
		global $wpdb;
		return get_post( $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '$post'" ) );
	endif;
}

/**
 * recursive_in_array function.
 *
 * @access public
 * @param mixed $needle
 * @param mixed $haystack
 * @return void
 */
function recursive_in_array($needle, $haystack) {
    foreach ($haystack as $stalk) {
        if ($needle == $stalk || (is_array($stalk) && recursive_in_array($needle, $stalk))) {
            return true;
        }
    }
    return false;
}

/**
 * Teh same as array_filter(), but will recursilvy traverse arrays.
 * 
 * @param array $input
 * @param function $callback. (default: null)
 * @return array
 */
function hm_array_filter_recursive($input, $callback = null) { 
	foreach ($input as &$value) { 
		if (is_array($value)) { 
			$value = hm_array_filter_recursive($value, $callback); 
		} 
	} 
	
	if( $callback )
		return array_filter($input, $callback); 
		
	return array_filter($input);
}

/**
 * hm_get_permalink function.
 *
 * @access public
 * @param mixed $post
 * @return void
 */
function hm_get_permalink( $post ) {

	$post = hm_parse_post( $post );

	return get_permalink( $post->ID );
}

/**
 * hm_shorten_string function.
 *
 * @access public
 * @param mixed $string
 * @param mixed $length
 * @return void
 */
function hm_shorten_string( $string, $length ) {

	if ( strlen( $string ) > (int)$length )
		return substr( $string, 0, $length - 3 ) . '...';

	return $string;
}

/**
 * Sorts a 2 dimentional array by a given 2nd level array key.
 *
 * @param array &$data
 * @param string $sortby - array key
 * @return array
 */
function masort($array, $id, $sort_ascending = true) {

   	$temp_array = array();

   	$original_array = $array;
   	foreach( $array as $key => $value ) {
   		$original_keys[] = $key;
   	}

   	$array = array_values( $array );

	while(count($array)>0) {
	    $lowest_id = 0;
	    $index=0;
	    $index_to_keys = array();
	    foreach ($array as $key => $item) {
	        if ( isset($item[$id]) ) {
				if ($array[$lowest_id][$id]) {

					if( is_numeric( $item[$id] ) ) {
						if( $item[$id] < $array[$lowest_id][$id] ) {
							$lowest_id = $index;
							$index_to_keys[$index] = $key;
						}
					} else {
	           			if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
	                		$lowest_id = $index;
	                		$index_to_keys[$index] = $key;
	            		}
	            	}
	            }
        	}
	        $index++;
	    }

       	$temp_array[ array_search( $array[$lowest_id], $original_array ) ] = $array[$lowest_id];
	    $array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
       }

	if ($sort_ascending) {
		return $temp_array;
   } else {
		return array_reverse($temp_array);
   }
}

function hm_sort_array_by_object_key( $array, $object_key ) {

	global $hm_sort_array_by_object_key;
	$hm_sort_array_by_object_key = $object_key;
	usort( $array, '_hm_sort_array_by_object_key_cmp' );

	return $array;

}

function _hm_sort_array_by_object_key_cmp( $a, $b ) {
	global $hm_sort_array_by_object_key;

	$valuea = is_numeric( $a->{$hm_sort_array_by_object_key} ) ? (int) $a->{$hm_sort_array_by_object_key} : $a->{$hm_sort_array_by_object_key};
	$valueb = is_numeric( $b->{$hm_sort_array_by_object_key} ) ? (int) $b->{$hm_sort_array_by_object_key} : $b->{$hm_sort_array_by_object_key};

	return $valuea > $valueb;
}

/**
 * array_map_preserve_keys function. Customized array_map function which preserves keys/associate array indexes. Note that this costs a descent amount more memory (eg. 1.5k per call)
 *
 * @access public
 * @param callback $callback Callback function to run for each element in each array.
 * @param mixed $arr1 An array to run through the callback function.
 * @param array $array Variable list of array arugments to run through the callback function.
 * @return array Array containing all the elements of $arr1 after applying the callback function to each one, recursively, maintain keys.
 */
function array_map_preserve_keys($callback,$arr1) {
    $results       =    array();
    $args          =    array();
    if(func_num_args()>2)
        $args          =    (array) array_shift(array_slice(func_get_args(),2));
    foreach($arr1 as $key=>$value) {
        $temp    =    $args;
        array_unshift($temp,$value);
        if(is_array($value)) {
            $results[$key]    =    call_user_func_array($callback,$temp);
        } else {
            $results[$key]    =    call_user_func_array($callback,$temp);
       }
   }
    return $results;
}

/**
 * is_odd function.
 *
 * @param int
 * @return bool
 */
function is_odd( $int ) {
	return  $int & 1;
}

/**
 * in_array_multi function.
 *
 * @param mixed $needle array value
 * @param array $haystack
 * @return array - found results
 */
function in_array_multi( $needle, $haystack ) {
	
	foreach( (array) $haystack as $key => $stack ) {
		
		if( is_array( $stack ) && in_array_multi( $needle, $stack ) )
			return true;
		
		if( $stack === $needle )
			return true;
	}
	
	return false;
}

/**
 * multi_array_key_exists function.
 *
 * @param mixed $needle The key you want to check for
 * @param mixed $haystack The array you want to search
 * @return bool
 */
function multi_array_key_exists( $needle, $haystack ) {
	
	foreach ( $haystack as $key => $value ) :

		if ( $needle === $key ) {
			return true;
		}

		if ( is_array( $value ) ) :
		 	if ( multi_array_key_exists( $needle, $value ) == true )
				return true;
		 	else
		 		continue;
		endif;

	endforeach;

	return false;
}

function hm_count( $count, $none, $one, $more = null ) {

	if ( $count > 1 )
		echo str_replace( '%', $count, $more );

	elseif( $count == 1 )
		echo $one;

	else
		echo $none;

}

function hm_error_message( $message, $context = '' ) {
	hm_add_message( $message, $context, 'error' );
}

function hm_success_message ( $message, $context = '' ) {
	hm_add_message( $message, $context, 'success' );;
}

function hm_add_message( $message, $context, $type ) {

	if( !$context ) {
		$context = 'all';
	}

	if( defined( 'hm_USE_COOKIES_FOR_MESSAGES') && hm_USE_COOKIES_FOR_MESSAGES ) {
		$cookie = ( $_COOKIE['hm_messages'] ) ? $_COOKIE['hm_messages'] : "";
		$messages = array_filter( (array) unserialize( base64_decode( $cookie ) ) );
	} else {
		if( isset( $_SESSION['hm_messages'] ) )
			$messages = array_filter( (array) $_SESSION['hm_messages'] );
		else
			$messages = array();
	}

	$messages[$context][] = array( 'message' => $message, 'type' => $type );

	if( defined( 'hm_USE_COOKIES_FOR_MESSAGES') && hm_USE_COOKIES_FOR_MESSAGES ) {
		$cookie = base64_encode( serialize( $messages ) );
		$_COOKIE['hm_messages'] = $cookie;
		@setcookie("hm_messages", $cookie, 0, COOKIEPATH );
	} else {
		if( !isset( $_SESSION ) )
			session_start();

		$_SESSION['hm_messages'] = $messages;
	}
}

function hm_get_messages( $context = null, $clear_cookie = true ) {

	if( defined( 'hm_USE_COOKIES_FOR_MESSAGES') && hm_USE_COOKIES_FOR_MESSAGES ) {
		$cookie = ( $_COOKIE['hm_messages'] ) ? $_COOKIE['hm_messages'] : "";
		$messages = array_filter( (array) unserialize( base64_decode( $cookie ) ) );
	} else {
		if( isset( $_SESSION['hm_messages'] ) )
			$messages = array_filter( (array) $_SESSION['hm_messages'] );
		else
			$messages = array();
	}

	if ( !empty( $context ) && !empty( $messages[$context] ) ) {

		$all_messages = $messages[$context];

		unset( $messages[$context] );

	} else {

		$all_messages = array();
		foreach( $messages as $context_messages ) {
			$all_messages = array_merge( (array) $all_messages, (array) $context_messages );
		}

		$messages = "";
	}

	if( $clear_cookie && defined( 'hm_USE_COOKIES_FOR_MESSAGES') && hm_USE_COOKIES_FOR_MESSAGES ) {

		$cookie = base64_encode( serialize( $messages ) );
		$_COOKIE['hm_messages'] = $cookie;
		@setcookie("hm_messages", $cookie, 0, COOKIEPATH );
	} elseif( $clear_cookie ) {
		$_SESSION['hm_messages'] = $messages;
	}

	return $all_messages;
}

function hm_get_the_messages( $context = null, $classes = null ) {

	$messages = hm_get_messages( $context );
	
	$output = '';

	if ( is_array( $messages ) ) {
		foreach( $messages as $message )
			$output .= '<div id="message" class="message ' . $message['type'] . ' ' . $classes . ' updated"><p>' . $message['message'] . '</p></div>';

		return $output;
	}

}

function hm_the_messages( $context = null, $classes = null ) {

	echo hm_get_the_messages( $context, $classes );

}

function hm_unsanitize_title( $title ) {
	return ucwords( str_replace( '_', ' ', $title ) );
}

function get_post_meta_by( $field = 'post_id', $value ) {

	return get_metadata_by( $field, $value, 'post' );

}

function get_term_meta_by( $field = 'term_id', $value ) {

	return get_metadata_by( $field, $value, 'term' );

}

function get_metadata_by( $field = 'post', $value, $type ) {

	global $wpdb;

	if ( $field === 'object_id' || $field === $type . '_id' ) {
		$get_type_custom = 'get_' . $type . '_custom';
		return $get_type_custom( $value );

	}

	$table = $wpdb->prefix . $type . 'meta';

	if ( $field === 'key' )
		return $wpdb->get_results( "SELECT DISTINCT meta_value FROM $table WHERE $table.meta_key = '$value'" );

	if ( $field === 'value' )
		return $wpdb->get_results( "SELECT DISTINCT meta_value FROM $table WHERE $table.meta_value = '$value'" );
}

/**
 * Get array of a terms children (across taxonomy)
 *
 * @param object $parent term object
 * @return array
 */
function hm_get_term_children( $parent, $taxonomy = null ) {

	if ( !is_numeric( $parent ) )
		return false;

	global $wpdb;

	$where = "WHERE tt.parent = $parent";

	if ( $taxonomy )
		$where .= " AND tt.taxonomy = '$taxonomy'";

	$query = "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id $where ORDER BY t.name ASC";

	$terms = $wpdb->get_results( $query );

	return $terms;
}


// term meta functions
//

function hm_add_term_meta_table() {
	global $wpdb;

	if ( !current_theme_supports( 'term-meta' ) )
		return false;

	//only creates if needed
	hm_create_term_meta_table();

	$wpdb->tables[] = 'termmeta';
	$wpdb->termmeta = $wpdb->prefix . 'termmeta';

}
add_action( 'init', 'hm_add_term_meta_table' );

/**
 * Creates the termmeta table if it deos not exist
 *
 */
function hm_create_term_meta_table() {
	global $wpdb;
	// check if the table is already exists

	if ( get_option( 'hm_created_term_meta_table' ) )
		return;

	$wpdb->query( "
		CREATE TABLE `{$wpdb->prefix}termmeta` (
		  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
		  `meta_key` varchar(255) DEFAULT NULL,
		  `meta_value` longtext,
		  PRIMARY KEY (`meta_id`),
		  KEY `term_id` (`term_id`),
		  KEY `meta_key` (`meta_key`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;" );

	update_option( 'hm_created_term_meta_table', true );

	return true;
}

if( !function_exists( 'add_term_meta' ) ) :
/**
 * Add meta data field to a term.
 *
 * @param int $term_id term ID.
 * @param string $key Metadata name.
 * @param mixed $value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function add_term_meta($term_id, $meta_key, $meta_value, $unique = false) {
    return add_metadata('term', $term_id, $meta_key, $meta_value, $unique);
}
endif;

if( !function_exists( 'delete_term_meta' ) ) :
/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int $term_id term ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function delete_term_meta($term_id, $meta_key, $meta_value = '') {
    return delete_metadata('term', $term_id, $meta_key, $meta_value);
}
endif;

if( !function_exists( 'get_term_meta' ) ) :
/**
 * Retrieve term meta field for a term.
 *
 * @param int $term_id term ID.
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function get_term_meta($term_id, $key, $single = false) {
    return get_metadata('term', $term_id, $key, $single);
}
endif;

if( !function_exists( 'update_term_meta' ) ) :
/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate betjeen meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @param int $term_id term ID.
 * @param string $key Metadata key.
 * @param mixed $value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
    return update_metadata('term', $term_id, $meta_key, $meta_value, $prev_value);
}
endif;

if( !function_exists( 'get_term_custom' ) ) :
/**
 * Retrieve term meta fields, based on post ID.
 *
 * The term meta fields are retrieved from the cache, so the function is
 * optimized to be called more than once. It also applies to the functions, that
 * use this function.
 *
 * @param int $term_id term ID
 * @return array
 */
 function get_term_custom($term_id = 0) {

    $term_id = (int) $term_id;

    if ( ! wp_cache_get($term_id, 'term_meta') )
        update_termmeta_cache($term_id);

    return wp_cache_get($term_id, 'term_meta');
}
endif;

if( !function_exists( 'update_termmeta_cache' ) ) :
/**
* Updates metadata cache for list of term_ids.
*
* Performs SQL query to retrieve the metadata for the term_idss and updates the
* metadata cache for the terms. Therefore, the functions, which call this
* function, do not need to perform SQL queries on their own.
*
* @param array $term_ids List of term_idss.
* @return bool|array Returns false if there is nothing to update or an array of metadata.
*/
function update_termmeta_cache($term_ids) {
    return update_meta_cache('term', $term_ids);
}
endif;

function hm_get_post_image( $post = null, $w = 0, $h = 0, $crop = false, $id = null, $default = null ) {

	if( $post === null ) global $post;

	// stop images with no post_id slipping in
	if( $post->ID == 0 && !$id )
		return;

	$id = $id ? $id : hm_get_post_image_id( $post );

	if( $id )
		return hm_phpthumb_it( get_attached_file( $id ), $w, $h, $crop, true, wm_get_options( $id ) );

	$att_id = hm_get_post_attached_image_id( $post );
	if( $att_id )
		return hm_phpthumb_it( get_attached_file( $att_id ), $w, $h, $crop, true, wm_get_options( $id ) );
	//if there is no id, then try search the content for an image
	if( $return = hm_phpthumb_it(hm_get_post_internal_image($post), $w, $h, $crop, true, wm_get_options( $id )) )
		return $return;

	if( $reutrn = hm_get_post_external_image($post) )
		return $return;

	if( $default ) {
		$file = $default === 'default' ? dirname( __FILE__ ) . '/includes/image-unavailable.png' : $default;
		return hm_phpthumb_it( $file, $w, $h, $crop, true );
	}
}

function hm_get_post_image_id( $post = null ) {
	if( $post === null ) global $post;
	if( $post->ID == 0 )
		return;

	$id = (int) get_post_meta( $post->ID, 'hm_post_image', true );
	if( $id )
		return $id;
	return hm_get_post_attached_image_id($post);

}

function hm_get_post_attached_image_id( $post = null, $return = 'file' ) {
	if ( $post === null ) global $post;

    $images = array();
    foreach( (array) get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {
    	if( !wp_attachment_is_image( $attachment->ID ) || !file_exists( get_attached_file( $attachment->ID ) ) )
    		continue;
    	return $attachment->ID;
    }
}

function hm_get_post_attached_images( $post = null ) {
	if ( $post === null ) global $post;

    $images = array();
    foreach( (array) get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {
    	if( !wp_attachment_is_image( $attachment->ID ) || !file_exists( get_attached_file( $attachment->ID ) ) )
    		continue;
    	$images =  $attachment;
    }
    return $images;
}

function hm_get_post_attached_images_id( $post = null ) {

    $images = array();
    foreach( hm_get_post_attached_images($post) as $attachment ) {
    	$images = $attachment->ID;
    }
    return $images;
}

function hm_get_post_internal_image( $post = null ) {
	$images = hm_get_post_internal_images( $post );
	return $images[0];
}

function hm_get_post_internal_images( $post = null ) {
	if( $post === null ) global $post;
	$images = array();
	preg_match_all('/(img|src)=("|\')[^"\'>]+/i', $post->post_content, $media);
	$data=preg_replace('/(img|src)("|\'|="|=\')(.*)/i',"$3",$media[0]);
	foreach($data as $url) {
		if( strpos( $url, get_bloginfo('url') ) === 0 && file_exists( $path = str_ireplace( trailingslashit(get_bloginfo('url')), trailingslashit(ABSPATH), $url ) ) )
			$images[] = $path;
	}
	return $images;
}


function strip_images( $post_content, $id = null ) {
	if( $id === null )
		$content = preg_replace('/(<img[\s\S]*?\>)/i', '', $post_content);
	else
		$content = preg_replace('/<img[\s\S]*?wp-image-' . $id . '[\s\S]*?\>/i', '', $post_content);
	return $content;
}

function hm_get_post_external_image( $post = null ) {
	if( $post === null ) global $post;

	$images = array();
	preg_match_all('/(img|src)=("|\')[^"\'>]+/i', $post->post_content, $media);
	$data=preg_replace('/(img|src)("|\'|="|=\')(.*)/i',"$3",$media[0]);

	foreach($data as $url) {
		$ext = end(explode('.', $url));
		if( strpos( $url, 'http://' ) === 0 && strpos( $url, get_bloginfo('url') ) === false && $ext == 'png' || $ext == 'jpg' || $ext == 'bmp' || $ext == 'jpeg' || $ext == 'gif' )
			$images[] = $url;
	}
	return $images[0];
}

function hm_remote_get_file( $url, $cache = true ) {
		
	//check for stuff
	$upload_dir = wp_upload_dir();
	$dest_folder = $upload_dir['basedir'] . '/remote_files/';
	$dest_file = $dest_folder . sanitize_title($url) . '.' . end(explode('.', $url));

	// cache file 404s in options
	$file_404s = (array) get_option( 'remote_404s' );
	if( isset( $file_404s[$url] ) && (int) $file_404s[$url] > ( time() - ( 60 * 60 *12 ) ) && $cache === true ) {
		return null;
	}

	if( !is_dir( $dest_folder ) ) {
		mkdir( $dest_folder );
	}

	if( file_exists( $dest_file ) && file_get_contents( $dest_file ) && $cache === true )
		return $dest_file;


	if ( $fp = @fopen($url, 'r') ) {
   		$content = '';
   		// keep reading until there's nothing left
   		while ($line = fread($fp, 1024)) {
   		   $content .= $line;
 		}
   	} 
   	
   	if( empty( $content ) ) {
   		$file_404s[$url] = time();
		update_option( 'remote_404s', $file_404s );
   		return null;
   	}

	preg_match('/Content-Length: ([0-9]+)/', $content, $parts);
	
	$image_data = substr($content, - $parts[1]);
	

	
	$ptr = fopen($dest_file, 'wb');

	fwrite($ptr, $image_data);
	fclose($ptr);

	return $dest_file;
}

function hm_get_countries( $popular = null ) {

	$countries = array(
		'Afghanistan',
		'Albania',
		'Algeria',
		'Andorra',
		'Angola',
		'Antigua and Barbuda',
		'Argentina',
		'Armenia',
		'Australia',
		'Austria',
		'Azerbaijan',
		'Bahamas',
		'Bahrain',
		'Bangladesh',
		'Barbados',
		'Belarus',
		'Belgium',
		'Belize',
		'Benin',
		'Bhutan',
		'Bolivia',
		'Bosnia and Herzegovina',
		'Botswana',
		'Brazil',
		'Brunei',
		'Bulgaria',
		'Burkina Faso',
		'Burundi',
		'Cambodia',
		'Cameroon',
		'Canada',
		'Cape Verde',
		'Central African Republic',
		'Chad',
		'Chile',
		'China',
		'Colombi',
		'Comoros',
		'Congo (Brazzaville)',
		'Congo',
		'Costa Rica',
		'Cote d\'Ivoire',
		'Croatia',
		'Cuba',
		'Cyprus',
		'Czech Republic',
		'Denmark',
		'Djibouti',
		'Dominica',
		'Dominican Republic',
		'East Timor (Timor Timur)',
		'Ecuador',
		'Egypt',
		'El Salvador',
		'Equatorial Guinea',
		'Eritrea',
		'Estonia',
		'Ethiopia',
		'Fiji',
		'Finland',
		'France',
		'Gabon',
		'Gambia, The',
		'Georgia',
		'Germany',
		'Ghana',
		'Greece',
		'Grenada',
		'Guatemala',
		'Guinea',
		'Guinea-Bissau',
		'Guyana',
		'Haiti',
		'Honduras',
		'Hungary',
		'Iceland',
		'India',
		'Indonesia',
		'Iran',
		'Iraq',
		'Ireland',
		'Israel',
		'Italy',
		'Jamaica',
		'Japan',
		'Jordan',
		'Kazakhstan',
		'Kenya',
		'Kiribati',
		'Korea, North',
		'Korea, South',
		'Kuwait',
		'Kyrgyzstan',
		'Laos',
		'Latvia',
		'Lebanon',
		'Lesotho',
		'Liberia',
		'Libya',
		'Liechtenstein',
		'Lithuania',
		'Luxembourg',
		'Macedonia',
		'Madagascar',
		'Malawi',
		'Malaysia',
		'Maldives',
		'Mali',
		'Malta',
		'Marshall Islands',
		'Mauritania',
		'Mauritius',
		'Mexico',
		'Micronesia',
		'Moldova',
		'Monaco',
		'Mongolia',
		'Morocco',
		'Mozambique',
		'Myanmar',
		'Namibia',
		'Nauru',
		'Nepa',
		'Netherlands',
		'New Zealand',
		'Nicaragua',
		'Niger',
		'Nigeria',
		'Norway',
		'Oman',
		'Pakistan',
		'Palau',
		'Panama',
		'Papua New Guinea',
		'Paraguay',
		'Peru',
		'Philippines',
		'Poland',
		'Portugal',
		'Qatar',
		'Romania',
		'Russia',
		'Rwanda',
		'Saint Kitts and Nevis',
		'Saint Lucia',
		'Saint Vincent',
		'Samoa',
		'San Marino',
		'Sao Tome and Principe',
		'Saudi Arabia',
		'Senegal',
		'Serbia and Montenegro',
		'Seychelles',
		'Sierra Leone',
		'Singapore',
		'Slovakia',
		'Slovenia',
		'Solomon Islands',
		'Somalia',
		'South Africa',
		'Spain',
		'Sri Lanka',
		'Sudan',
		'Suriname',
		'Swaziland',
		'Sweden',
		'Switzerland',
		'Syria',
		'Taiwan',
		'Tajikistan',
		'Tanzania',
		'Thailand',
		'Togo',
		'Tonga',
		'Trinidad and Tobago',
		'Tunisia',
		'Turkey',
		'Turkmenistan',
		'Tuvalu',
		'Uganda',
		'Ukraine',
		'United Arab Emirates',
		'United Kingdom',
		'United States',
		'Uruguay',
		'Uzbekistan',
		'Vanuatu',
		'Vatican City',
		'Venezuela',
		'Vietnam',
		'Yemen',
		'Zambia',
		'Zimbabwe'
	);

	if ( !is_null( $popular ) )
		$countries = $popular + $countries;

	return $countries;

}

function hm_get_breadcrumb_tree() {

}

function hm_breadcrumbs() {



}

// Multiple meta value hackkks
add_filter( 'posts_join_paged', 'hm_add_multiple_meta_joins_to_wp_query', 10, 2 );
function hm_add_multiple_meta_joins_to_wp_query( $join, $wp_query ) {

	global $wpdb;

	if ( !is_array( $wp_query->query_vars['meta_key'] ) || count( $wp_query->query_vars['meta_key'] ) == 1 || empty( $wp_query->query_vars['meta_value'] ) )
		return $join;

	$meta_value_count = count( $wp_query->query_vars['meta_key'] ) - 1;

	for( $i = 0; $i < $meta_value_count; $i++ ) {
		$join .= " JOIN $wpdb->postmeta as hm_extra_meta_{$i} ON $wpdb->posts.ID = hm_extra_meta_{$i}.post_id";
	}

	return $join;
}


function hm_add_multiple_meta_to_wp_query( $where, $wp_query ) {

	global $wpdb;

	if( !is_array( $wp_query->query_vars['meta_key'] ) )
		return $where;

	$meta_value_count = count( $wp_query->query_vars['meta_key'] ) - 1;

	$compare = $wp_query->query['meta_compare'][0];
	$conjuction = $wp_query->query_vars['meta_conjunction'] ? $wp_query->query_vars['meta_conjunction'] : 'AND';

	// Replace the first meta_compare in the normal where because wordpress cant handle an array
	$where = str_replace( " $wpdb->postmeta.meta_value = ", " $wpdb->postmeta.meta_value $compare ", $where );

	if ( $wp_query->query_vars['meta_key'] && empty( $wp_query->query_vars['meta_value'] ) ) {
		$where = str_replace( " AND $wpdb->postmeta.meta_key = '" . reset( $wp_query->query_vars['meta_key'] ) . "'", "", $where );
		return $where .= " AND meta_key IN ( '" . implode( '\' , \'', $wp_query->query_vars['meta_key'] ) . "' )";
	}

	for( $i = 0; $i < $meta_value_count; $i++ ) {
		$table = "hm_extra_meta_{$i}";
		$key = $wp_query->query_vars['meta_key'][$i + 1];
		$value = $wp_query->query_vars['meta_value'][$i + 1];
		$compare = $wp_query->query['meta_compare'][$i + 1];

		if ( $key && $value && $compare )
			$where .= "AND ( $table.meta_key = '$key' $conjuction $table.meta_value" . ( is_numeric( $value ) ? " + 0" : "" ) . " $compare " . ( is_numeric( $value ) ? "$value" : "'$value'" ) . " )";
	}

	return $where;

}

add_filter( 'posts_where', 'hm_add_multiple_meta_to_wp_query', 10, 2 );

function hm_add_multiple_taxonomy_to_parse_query() {

	global $wp_query;

	$custom_taxonomies = get_taxonomies( array( '_builtin' => false ) );
	$queried_taxonomies = array();

	foreach ( $custom_taxonomies as $taxonomy )
		if ( array_key_exists( $taxonomy, $wp_query->query_vars ) )
			$queried_taxonomies[] = $taxonomy;

	if ( count( $queried_taxonomies ) === 1 ) :
		$wp_query->query_vars['taxonomy'] = reset( $queried_taxonomies );
		$wp_query->query_vars['term'] = get_term( $wp_query->query_vars[reset( $queried_taxonomies )], reset( $queried_taxonomies ) )->slug;
		unset( $wp_query->query_vars[reset( $queried_taxonomies )] );

	elseif ( count( $queried_taxonomies ) ) :


		foreach( $queried_taxonomies as $taxonomy ) :

			$wp_query->query_vars['hm_taxonomy_' . $taxonomy . '__in'] = $wp_query->query_vars[$taxonomy];
			unset( $wp_query->query_vars[$taxonomy] );

		endforeach;

		unset( $wp_query->query_vars['taxonomy'] );
		unset( $wp_query->query_vars['term'] );

	endif;

}
add_action( 'parse_query', 'hm_add_multiple_taxonomy_to_parse_query' );

function hm_add_multiple_taxonomy_to_where( $where, $wp_query ) {

	global $wpdb;

	$taxonomy__in = $taxonomy__and = $taxonomy__and_in = array();

	foreach ( $wp_query->query_vars as $var => $value ) {
		if ( preg_match( '|^hm_taxonomy_([\s\S]*?)__([\s\S]*?)$|', $var, $matches ) ) {

			if ( $matches[2] == 'in' || $matches[2] == 'and' || $matches[2] == 'and_in' ) {
				$query_var = 'taxonomy__' . $matches[2];
				$$query_var = array_merge( (array)$$query_var, array( $matches[1] => $value ) );
			}
		}

	}

	$taxonomy__in = array_filter( (array) $taxonomy__in );
	$taxonomy__and = array_filter( (array) $taxonomy__and );
	$taxonomy__and_in = array_filter( (array) $taxonomy__and_in );
	
	if ( !empty( $taxonomy__in ) ) {

		$where .= " AND ( 1 = 1 ";

		foreach( $taxonomy__in as $taxonomy => $terms ) {

			// Allow for comma sepped lists and arrays
			if ( is_string( $terms ) )
				$terms = explode( ',', $terms );

			foreach( $terms as $key => $term )
				$where .= " AND $wpdb->posts.ID IN ( SELECT hm_{$taxonomy}_tr.object_id FROM $wpdb->term_relationships AS hm_{$taxonomy}_tr INNER JOIN $wpdb->term_taxonomy AS hm_{$taxonomy}_tt ON hm_{$taxonomy}_tr.term_taxonomy_id = hm_{$taxonomy}_tt.term_taxonomy_id WHERE hm_{$taxonomy}_tt.taxonomy = '$taxonomy' AND hm_{$taxonomy}_tt.term_id = $term )";

		}

		$where .= " ) ";

	}
	
	if ( !empty( $taxonomy__and ) ) {

		$where .= " AND ( 1 = 1 ";

		foreach ( $taxonomy__and as $taxonomy => $terms ) {

			// Allow for comma sepped lists and arrays
			if ( is_string( $terms ) )
				$terms = explode( ',', $terms );
			
			$where__and = array();
			
			foreach( $terms as $key => $term )
				$where__and[] = " $wpdb->posts.ID IN ( SELECT hm_{$taxonomy}_tr.object_id FROM $wpdb->term_relationships AS hm_{$taxonomy}_tr INNER JOIN $wpdb->term_taxonomy AS hm_{$taxonomy}_tt ON hm_{$taxonomy}_tr.term_taxonomy_id = hm_{$taxonomy}_tt.term_taxonomy_id WHERE hm_{$taxonomy}_tt.taxonomy = '$taxonomy' AND hm_{$taxonomy}_tt.term_id = $term )";

			if ( !empty( $where__and ) )
				$where .= ' AND ' . implode( ' OR ', $where__and );

		}

		$where .= " ) ";

	}
	
	if ( !empty( $taxonomy__and_in ) ) {

		$where .= " AND ( 1 = 1 ";

		foreach ( $taxonomy__and_in as $taxonomy => $taxonomy_groups ) {
			foreach( $taxonomy_groups as $terms ) {
				
				$where__and_in = array();
				
				foreach( $terms as $key => $term )
					$where__and_in[] = " $wpdb->posts.ID IN ( SELECT hm_{$taxonomy}_tr.object_id FROM $wpdb->term_relationships AS hm_{$taxonomy}_tr INNER JOIN $wpdb->term_taxonomy AS hm_{$taxonomy}_tt ON hm_{$taxonomy}_tr.term_taxonomy_id = hm_{$taxonomy}_tt.term_taxonomy_id WHERE hm_{$taxonomy}_tt.taxonomy = '$taxonomy' AND hm_{$taxonomy}_tt.term_id = $term )";
				
				if ( !empty( $where__and_in ) )
					$where .= ' AND ' . implode( ' OR ', $where__and_in );
			}	
		}

		$where .= " ) ";

	}
	return $where;

}
add_filter( 'posts_where', 'hm_add_multiple_taxonomy_to_where', 10, 2 );

function hm_allow_any_orderby_to_wp_query( $orderby, $wp_query ) {

	global $wpdb;
	$query = wp_parse_args( $wp_query->query );
	$orders = explode( ' ', isset( $query['orderby'] ) ? $query['orderby'] : '' );

	if( count( $orders ) <= 1  )
		return $orderby;

	// Some internal WordPress queries incorrectly add DESC or ASC to the orderby instead of order
	foreach( $orders as $key => $order ) :
		if ( in_array( strtoupper( $order ), array( 'DESC', 'ASC' ) ) ) :
			$orders[$key - 1] .= ' ' . strtoupper( $order );
			unset( $orders[$key] );
		endif;
	endforeach;

	$one_before = '';

	foreach( $orders as $key => $order ) {

		$order = str_replace( $wpdb->posts . '.post_', '', $order );

		$table_column = in_array( $order, array( 'menu_order' ) ) ? $order : 'post_' . $order;

		if(  strpos( $orderby, $wpdb->posts . '.post_' . $order ) !== false ) {
			$one_before = $order;
			continue;
		}

		if( strpos( $orderby, $wpdb->posts . '.post_' . $order ) === false ) {
			if( $one_before )
				$orderby = str_replace( $wpdb->posts . '.' . $one_before,  $wpdb->posts . '.post_' . $one_before . ', ' . $wpdb->posts . '.' . $table_column, $orderby );
			else
				$orderby =  $wpdb->posts . '.' . $table_column . ', ' . $orderby;
		}
	}

	$orderby = str_replace( ', ,', ', ', $orderby );

	return $orderby;
}
add_filter( 'posts_orderby_request', 'hm_allow_any_orderby_to_wp_query', 10, 2 );

function hm_time_to_local( $time = null ) {

	if( is_null( $time ) )
		$time = time();

	return $time + ( get_option( 'gmt_offset' ) * 3600 );
}

function hm_local_to_time( $local_time ) {
	return $local_time - ( get_option( 'gmt_offset' ) * 3600 );
}

function hm_time_to_dst_offset( $time ) {
	
	// Set TZ so localtime works.
	date_default_timezone_set( get_option('timezone_string') );
	
	if( date( 'I', $time ) !== date( 'I', time() ) ) {
		if( date( 'I', $time ) > date( 'I', time() ) ) {
			
			//post was created in DST (+1 hour)
			$time += 3600;
		
		} else {
			
			//post was created in standard time (-1 Hour)
			$time -= 3600;
		}
	}
	
	// Set back to UTC.
	date_default_timezone_set('UTC');
	
	return $time;
}

// This forces the inbuilt mail function to send html emails instead of plain text emails.
function wp_mail_content_type_html( $content_type ) {
	return 'text/html';
}

function hm_wp_mail_from( $mail ) {
	return str_replace( 'wordpress@', 'noreply@', $mail );
}

function hm_array_value( $array, $key ) {
	return $array[$key];
}

function hm_wp_mail_from_name() {
	return get_bloginfo();
}
/**
 * Insert a variable into an array at a givven position, shunting keys down.
 *
 * @param array $array
 * @param int $pos
 * @param mixed $val
 * @return array
 */
function hm_array_insert( $array, $pos, $val, $key = null) {

    $array2 = array_splice($array,$pos);

    if ( $key )
	    $array[$key] = $val;

	else
		$array[] = $val;

    $array = array_merge($array,$array2);

    return $array;
}

/**
 * Implode an array into a list of items separated by $separator.
 * Use $last_separator for the last list item.
 *
 * Useful for natural language lists (e.g first, second & third).
 *
 * @param array $array
 * @param string $separator. (default: ', ')
 * @param string $last_separator. (default: ' &amp; ')
 * @return string a list of array values
 */
function hm_multi_implode( $array, $separator = ', ', $last_separator = ' &amp; ' ) {

	if ( count( $array ) == 1 )
		return reset( $array );

	$end_value = array_pop( $array );

	$list = implode( $separator, $array );

	return $list . $last_separator . $end_value;

}

/**
 * r_implode function. a recursive version of implode
 * 
 * @access public
 * @param string $glue
 * @param array $pieces
 * @return string
 */
function r_implode( $glue, $pieces ) { 
	
	foreach ( $pieces as $piece ) 
	    if ( is_array( $piece ) ) 
			$return[] = r_implode( $glue, $piece ); 
    	else 
			$return[] = $piece; 
  
	return implode( $glue, $return ); 

}

function hm_add_exclude_draft_to_get_terms_hide_empty( $terms, $taxonomies, $args ) {

	if( $args['hide_empty'] == false || ( $args['hide_empty'] == true && empty( $args['hide_empty_exclude_drafts'] ) ) )
		return $terms;

	global $wpdb;

	// Check that each term has posts in it which are published
	foreach( $terms as $key => $term ) {

		$post_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts INNER JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id WHERE post_status != 'draft' AND $wpdb->term_relationships.term_taxonomy_id = $term->term_taxonomy_id LIMIT 0, 1" );

		if( !$post_id )
			unset( $terms[$key] );

	}

	return $terms;

}
add_filter( 'get_terms', 'hm_add_exclude_draft_to_get_terms_hide_empty', 1, 3 );



/**
 * Retuns pagination html for list pages
 *
 * @param object $wp_query. (default: global $wp_query)
 * @param int $current_page. (default: $wp_query['query_vars']['paged'])
 * @param int $ppp. (posts per page) (default: 10)
 * @return  pagination hmtl
 */
function hm_get_pagination( $wp_query = null, $current_page = null, $ppp = null ) {
	if( $wp_query === null ) global $wp_query;
	if( $ppp === null ) $ppp = $wp_query->query_vars['posts_per_page'] ? $wp_query->query_vars['posts_per_page'] : 10;
	$number_pages = ceil($wp_query->found_posts / $ppp);
	if( $current_page === null ) $current_page = get_query_var('paged');

	$current_page = $current_page ? $current_page : 1;
	$base = str_replace( '/page/' . $current_page . '', '', $_SERVER['REQUEST_URI'] );

	$base = remove_query_arg( 'paged', $base );

	// strip any query args, put them on the end (after /page/%number%/
	$bases = explode( '?', $base );
	$base = current( $bases );

	if ( isset( $bases[1] ) )
		$query_params = $bases[1];

	//set the next / prev text
	$next_text = __('Next &raquo;');
	$prev_text = __('&laquo; Prev');


	// mid-size depends on what page you are on, as it applies to each side of current_page
	if( $current_page > ($number_pages - 3) ) {
		$mid_size = 5 - ($number_pages - ($current_page) + 1 );
	} elseif( $current_page >= 5 ) {
		$mid_size = 2;
	} elseif( $current_page == 1 ) {
		$mid_size = 4;
	} else {
		$mid_size = 5 - $current_page + 1;
	}

	$page_links = paginate_links( array(
		'base' => trailingslashit( $base ) . ( ( isset( $_GET['s'] ) && $_GET['s'] ) ? '' : 'page/%#%/' ) . ( ( isset( $query_params ) && $query_params ) ? '?' . $query_params : '' ) .  ( ( isset( $_GET['s'] ) && $_GET['s'] ) ? '&paged=%#%' : '' ),
		'format' => '',
		'prev_text' => $prev_text,
		'next_text' => $next_text,
		'total' => $number_pages,
		'current' => $current_page,
		'mid_size' => $mid_size,
		'end_size' => 1,
		'type' => 'array'
	));

	if( !is_array( $page_links ) || empty( $page_links ) )
		return;

	// loop through the page links, removing any unwanted ones as paginate_links() does not provide such fine control
	$real_counter = 0;
	$output = '';
	foreach( $page_links as $counter => $pagination_item ) :

		if ( ( strpos($pagination_item, '...') && $counter == 2) || ( $counter == 1 && strpos($page_links[2], '...' ) ) || ( $counter == 1 && $current_page == 4 ) )
			continue;

		//strip ..., last page
		if ( strpos( $pagination_item, '...') || ( strpos( $page_links[$counter ? $counter - 1 : 0], '...') && $counter == count( $page_links ) - 2 ) || ( $counter == 1 && strpos( $page_links[ 2 ], '...' ) ) || ( $counter == 1 && strpos( $page_links[0], $prev_text ) && $current_page == 4 ) )
			$real_counter--;

		if( $real_counter >= 6 && strpos( $pagination_item, $next_text ) === false )
			continue;

		$real_counter++;

		$output .= $pagination_item;

	endforeach;

	// exception for page 1
	if ( isset( $_GET['s'] ) && $_GET['s'] )
		$output = str_replace( "&#038;paged=1'", "'", $output );

	else
		$output = str_replace( '/page/1/', '/', $output );

	return '<div class="pagination">' . $output . '</div>';
}

/**
 * Echoes hm_get_pagination
 *
 */
function hm_pagination() {
	echo hm_get_pagination();
}

/**
 * Returns post pagination html
 *
 * @param object $post. (default: global $post)
 * @param int $current_page. (default: global $numposts)
 * @return string - pagination html
 */
function hm_get_post_pagination( $post = null, $current_page = null ) {
	global $numpages;

	// set number_pages to the global if we are using global $post
	if( $post === null ) $number_pages = $numpages;
	if( $post === null ) global $post;

	if( !$current_page ) $current_page = ( ( $page = get_query_var( 'page' ) ) ? $page : 1 );

	if( $number_pages == null ) {
		$pages = explode('<!--nextpage-->', $post->post_content);
		$number_pages = count($pages);
	}

	//set the next / prev text
	$next_text = __('Next &raquo;');
	$prev_text = __('&laquo; Prev');


	// mid-size depends on what page you are on, as it applies to each side of current_page
	if( $current_page > ($number_pages - 3) ) {
		$mid_size = 5 - ($number_pages - ($current_page) + 1 );
	} elseif( $current_page >= 5 ) {
		$mid_size = 2;
	} elseif( $current_page == 1 ) {
		$mid_size = 4;
	} else {
		$mid_size = 5 - $current_page + 1;
	}

	if( is_preview() ) {
		$base = get_permalink( $post->ID ) . '&page=%#%';
	} else {
		$base = trailingslashit( get_permalink( $post->ID ) ) . '%#%/';
	}

	$page_links = paginate_links( array(
		'base' => $base,
		'format' => '',
		'prev_text' => $prev_text,
		'next_text' => $next_text,
		'total' => $number_pages,
		'current' => $current_page,
		'mid_size' => $mid_size,
		'end_size' => 1,
		'type' => 'array'
	));

	if( !is_array( $page_links ) || empty( $page_links ) )
		return;

	// loop through the page links, removing any unwanted ones as paginate_links() does not provide such fine control
	$real_counter = 0;
	foreach( $page_links as $counter => $pagination_item ) :

		//strip ..., last page
		if( strpos($pagination_item, '...') || ( strpos($page_links[ $counter - 1 ], '...') && $counter == count( $page_links ) - 2 ) || ( $counter == 1 && strpos($page_links[ 2 ], '...') ) || ( $counter == 1 && strpos($page_links[ 0 ], $prev_text) && $current_page == 4 ) )
			continue;

		if( $real_counter >= 6 && strpos( $pagination_item, $next_text ) === false )
			continue;

		$real_counter++;

		$output .= $pagination_item;

	endforeach;

	// exception for page 1
	$output = str_replace( '/1/', '/', $output );

	return '<div class="post-pagination">' . $output . '</div>';
}

/**
 * Echoes hm_get_post_pagination
 *
 */
function hm_post_pagination() {
	echo hm_get_post_pagination();
}

