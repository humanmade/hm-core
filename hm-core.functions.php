<?php

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

	if ( $callback )
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

function hm_sort_array_by_object_key( $array, $object_key ) {

	global $hm_sort_array_by_object_key;
	$hm_sort_array_by_object_key = $object_key;
	usort( $array, '_hm_sort_array_by_object_key_cmp' );

	return $array;

}

function _hm_sort_array_by_object_key_cmp( $a, $b ) {
	global $hm_sort_array_by_object_key;

	if( is_object( $a ) ) {

		$valuea = is_numeric( $a->{$hm_sort_array_by_object_key} ) ? (int) $a->{$hm_sort_array_by_object_key} : $a->{$hm_sort_array_by_object_key};
		$valueb = is_numeric( $b->{$hm_sort_array_by_object_key} ) ? (int) $b->{$hm_sort_array_by_object_key} : $b->{$hm_sort_array_by_object_key};

	} elseif( is_array( $a ) ) {

		$valuea = is_numeric( $a[$hm_sort_array_by_object_key] ) ? (int) $a[$hm_sort_array_by_object_key] : $a[$hm_sort_array_by_object_key];
		$valueb = is_numeric( $b[$hm_sort_array_by_object_key] ) ? (int) $b[$hm_sort_array_by_object_key] : $b[$hm_sort_array_by_object_key];

	}

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
    if (func_num_args()>2)
        $args          =    (array) array_shift(array_slice(func_get_args(),2));
    foreach($arr1 as $key=>$value) {
        $temp    =    $args;
        array_unshift($temp,$value);
        if (is_array($value)) {
            $results[$key]    =    call_user_func_array($callback,$temp);
        } else {
            $results[$key]    =    call_user_func_array($callback,$temp);
       }
   }
    return $results;
}

function hm_count( $count, $none, $one, $more = null ) {

	if ( $count > 1 )
		echo str_replace( '%', $count, $more );

	elseif ( $count == 1 )
		echo $one;

	else
		echo $none;

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

function get_metadata_by( $fields, $values, $type = 'post', $col = '*' ) {

	global $wpdb;

	if ( empty( $fields ) || ( !is_array( $fields  ) && empty( $values ) ) )
		return array();

	if ( !is_array( $fields ) && !empty( $fields ) && !is_array( $values ) && !empty( $values ) )
		$fields = array( $fields => $values );

	if ( is_array( $fields ) && is_array( $values ) )
		$fields = array_combine( $fields, $values );

	foreach ( (array) $fields as $field => $value )
		$where[] = "" . $field . " = '" . $value . "'";

	$table = $wpdb->prefix . $type . 'meta';

	return $wpdb->get_results( "SELECT $col FROM $table WHERE " . implode( ' AND ' , (array) $where ) );
}

/**
 * Get array of a terms children (across taxonomy)
 *
 * @param object $parent term object
 * @return array
 */
function hm_get_term_children( $parent, $taxonomy = null ) {

	if ( ! is_numeric( $parent ) )
		return false;

	global $wpdb;

	$where = "WHERE tt.parent = $parent";

	if ( $taxonomy )
		$where .= " AND tt.taxonomy = '$taxonomy'";

	$query = "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id $where ORDER BY t.name ASC";

	$terms = $wpdb->get_results( $query );

	return $terms;
}



function hm_get_post_image( $post = null, $w = 0, $h = 0, $crop = false, $id = null, $default = null ) {

	if ( $post === null ) global $post;

	// stop images with no post_id slipping in
	if ( $post->ID == 0 && !$id )
		return;

	$id = $id ? $id : hm_get_post_image_id( $post );

	if ( $id )
		return wpthumb( get_attached_file( $id ), $w, $h, $crop, true, wpthumb_wm_get_options( $id ) );

	$att_id = hm_get_post_attached_image_id( $post );
	if ( $att_id )
		return wpthumb( get_attached_file( $att_id ), $w, $h, $crop, true, wpthumb_wm_get_options( $id ) );
	//if there is no id, then try search the content for an image
	if ( $return = wpthumb(hm_get_post_internal_image($post), $w, $h, $crop, true, wpthumb_wm_get_options( $id )) )
		return $return;

	if ( $return = hm_get_post_external_image($post) )
		return $return;

	if ( $default ) {
		$file = $default === 'default' ? dirname( __FILE__ ) . '/includes/image-unavailable.png' : $default;
		return wpthumb( $file, $w, $h, $crop, true );
	}
}

function hm_get_post_image_id( $post = null ) {
	if ( $post === null ) global $post;
	if ( $post->ID == 0 )
		return;

	$id = (int) get_post_meta( $post->ID, 'hm_post_image', true );
	if ( $id )
		return $id;
	return hm_get_post_attached_image_id($post);

}

function hm_get_post_attached_image_id( $post = null, $return = 'file' ) {
	if ( $post === null ) global $post;

    $images = array();
    foreach( (array) get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {
    	if ( !wp_attachment_is_image( $attachment->ID ) || !file_exists( get_attached_file( $attachment->ID ) ) )
    		continue;
    	return $attachment->ID;
    }
}

function hm_get_post_attached_images( $post = null ) {
	if ( $post === null ) global $post;

    $images = array();
    foreach( (array) get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {
    	if ( !wp_attachment_is_image( $attachment->ID ) || !file_exists( get_attached_file( $attachment->ID ) ) )
    		continue;
    	$images[] =  $attachment;
    }
    return $images;
}

function hm_get_post_attached_images_id( $post = null ) {

    $images = array();
    foreach( hm_get_post_attached_images( $post ) as $attachment ) {
    	$images[] = $attachment->ID;
    }
    return $images;
}

/**
 * Get the first image from inside the post content.
 *
 * @access public
 * @param int $post_id. (default: null)
 * @return int
 */
function hm_get_post_internal_image( $post_id ) {
	return reset( hm_get_post_internal_images( $post_id ) );
}

/**
 * hm_get_post_internal_images function.
 *
 * @access public
 * @param int $post_id
 * @return Array attachment id's
 */
function hm_get_post_internal_images( $post_id ) {

	$post = get_post( $post_id );

	$images = array();

	if ( empty( $post->post_content ) )
	  return array();

	preg_match_all( '/(img|src)=("|\')[^"\'>]+/i', $post->post_content, $media );

	$data = preg_replace( '/(img|src)("|\'|="|=\')(.*)/i', "$3", reset( $media ) );

	if ( empty( $data ) )
		return array();

	foreach( $data as $url )
		if ( strpos( $url, get_bloginfo( 'url' ) ) === 0 && file_exists( $path = str_ireplace( trailingslashit( get_bloginfo( 'url' ) ), trailingslashit( ABSPATH ), $url ) ) )
			$images[] = $path;

	return $images;

}

/**
 * Strip all images from a string
 *
 * @access public
 * @param mixed $post_content
 * @param mixed $post_id. (default: null)
 * @return null
 */
function strip_images( $content, $post_id = null ) {

	if ( is_null( $post_id ) )
		return preg_replace( '/(<img[\s\S]*?\>)/i', '', $content );

	return preg_replace( '/<img[\s\S]*?wp-image-' . $post_id . '[\s\S]*?\>/i', '', $content );

}

/**
 * Return an array of external images in a post
 *
 * @access public
 * @param mixed $post. (default: null)
 * @return null
 */
function hm_get_post_external_image( $post_id = null ) {

	$post = get_post( $post_id );

	$images = array();

	preg_match_all( '/(img|src)=("|\')[^"\'>]+/i', $post->post_content, $media );
	$data = preg_replace( '/(img|src)("|\'|="|=\')(.*)/i', "$3", reset( $media ) );

	foreach( $data as $url) {

		$ext = end( explode( '.', $url ) );

		if ( strpos( $url, 'http://' ) === 0 && strpos( $url, get_bloginfo( 'url' ) ) === false && $ext == 'png' || $ext == 'jpg' || $ext == 'bmp' || $ext == 'jpeg' || $ext == 'gif' )
			$images[] = $url;

	}

	return reset( $images );
}

function hm_remote_get_file( $url, $cache = true ) {


	//check for stuff
	$upload_dir = wp_upload_dir();
	$dest_folder = $upload_dir['basedir'] . '/remote_files/';
	$dest_file = $dest_folder . sanitize_title($url) . '.' . end(explode('.', $url));

	// cache file 404s in options
	$file_404s = (array) get_option( 'remote_404s' );
	if ( isset( $file_404s[$url] ) && (int) $file_404s[$url] > ( time() - ( 60 * 60 *12 ) ) && $cache === true ) {
		return null;
	}

	if ( !is_dir( $dest_folder ) ) {
		mkdir( $dest_folder );
	}

	if ( file_exists( $dest_file ) && file_get_contents( $dest_file ) && $cache === true ) {
		return $dest_file;
	}

	do_action( 'start_operation', $operation = ( 'Remote get file: ' . $url ) );

	if ( $fp = @fopen($url, 'r') ) {
   		$content = '';
   		// keep reading until there's nothing left
   		while ($line = fread($fp, 1024)) {
   		   $content .= $line;
 		}
   	}

   	if ( empty( $content ) ) {
   		$file_404s[$url] = time();
		update_option( 'remote_404s', $file_404s );
		do_action( 'end_operation', $operation );
   		return null;
   	}

	preg_match('/Content-Length: ([0-9]+)/', $content, $parts);

	$image_data = substr($content, - $parts[1]);

	$ptr = fopen($dest_file, 'wb');

	fwrite($ptr, $image_data);
	fclose($ptr);

	do_action( 'end_operation', $operation );

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

function hm_time_to_local( $time = null ) {

	if ( is_null( $time ) )
		$time = time();

	return $time + ( get_option( 'gmt_offset' ) * 3600 );
}

function hm_local_to_time( $local_time ) {
	return $local_time - ( get_option( 'gmt_offset' ) * 3600 );
}

function hm_time_to_dst_offset( $time ) {

	// Set TZ so localtime works.
	date_default_timezone_set( get_option('timezone_string') );

	if ( date( 'I', $time ) !== date( 'I', time() ) ) {
		if ( date( 'I', $time ) > date( 'I', time() ) ) {

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
	return apply_filters( 'hm_wp_mail_from', str_replace( 'wordpress@', 'noreply@', $mail ) );
}

function hm_array_value( $array, $key ) {
	return $array[$key];
}

function hm_wp_mail_from_name() {
	return apply_filters( 'hm_wp_mail_from_name', get_bloginfo( 'name' ) );
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
 * Take into account draft posts when you specify hide_empty in get_terms()
 * 
 * @param  StdClass[] $terms
 * @param  string $taxonomies
 * @param  array $args
 * @return StdClass[]
 */
function hm_add_exclude_draft_to_get_terms_hide_empty( $terms, $taxonomies, $args ) {

	if ( $args['hide_empty'] == false || ( $args['hide_empty'] == true && empty( $args['hide_empty_exclude_drafts'] ) ) )
		return $terms;

	global $wpdb;

	// Check that each term has posts in it which are published
	foreach( $terms as $key => $term ) {

		$post_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts INNER JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id WHERE post_status != 'draft' AND $wpdb->term_relationships.term_taxonomy_id = $term->term_taxonomy_id LIMIT 0, 1" );

		if ( !$post_id )
			unset( $terms[$key] );

	}

	return $terms;

}
add_filter( 'get_terms', 'hm_add_exclude_draft_to_get_terms_hide_empty', 1, 3 );


/**
 * Retuns pagination html for list pages
 *
 * TODO - this should use a walker?
 *
 * @param object $wp_query. (default: global $wp_query)
 * @param int $current_page. (default: $wp_query['query_vars']['paged'])
 * @param int $ppp. (posts per page) (default: 10)
 * @return  pagination hmtl
 */
function hm_get_pagination( $wp_query = null, $current_page = null, $ppp = null, $args = array() ) {

	global $wp_rewrite;

	if ( is_null( $wp_query ) )
		global $wp_query;

	if ( is_null( $ppp ) )
		$ppp = $wp_query->query_vars['posts_per_page'] ? $wp_query->query_vars['posts_per_page'] : get_option( 'posts_per_page' );

	$number_pages = ceil ( $wp_query->found_posts / $ppp );

	if ( is_null( $current_page ) )
		$current_page = get_query_var( 'paged' );

	$current_page = $current_page ? $current_page : 1;

	$defaults = array(
		'next_text' => 'Next &raquo;',
		'prev_text' => '&laquo; Prev',
		'show_all' => false
	);

	$args = wp_parse_args( $args, $defaults );

	if ( $wp_rewrite->pagination_base )
		$wp_rewrite->pagination_base = trailingslashit( $wp_rewrite->pagination_base );

	$base = str_replace( $wp_rewrite->pagination_base . $current_page . '/', '', $_SERVER['REQUEST_URI'] );

	$base = remove_query_arg( 'paged', $base );

	// Strip any query args, put them on the end (after /page/%number%/
	$bases = explode( '?', $base );
	$base = current( $bases );

	if ( isset( $bases[1] ) )
		$query_params = $bases[1];

	// Mid-size depends on what page you are on, as it applies to each side of current_page
	if ( $current_page > ( $number_pages - 3 ) )
		$mid_size = 5 - ( $number_pages - ( $current_page ) + 1 );

	elseif ( $current_page >= 5 )
		$mid_size = 2;

	elseif ( $current_page == 1 )
		$mid_size = 4;

	else
		$mid_size = 5 - $current_page + 1;

	$page_links = paginate_links( array(
		'base' => trailingslashit( $base ) . ( ( isset( $_GET['s'] ) && $_GET['s'] ) ? '' : $wp_rewrite->pagination_base . '%#%/' ) . ( ( isset( $query_params ) && $query_params ) ? '?' . $query_params : '' ) .  ( ( isset( $_GET['s'] ) && $_GET['s'] ) ? '&paged=%#%' : '' ),
		'format' => '',
		'prev_text' => $args['prev_text'],
		'next_text' => $args['next_text'],
		'total' => $number_pages,
		'current' => $current_page,
		'mid_size' => $mid_size,
		'end_size' => 1,
		'show_all' => $args['show_all'],
		'type' => 'array'
	) );

	if ( ! is_array( $page_links ) || empty( $page_links ) )
		return;

	foreach ( $page_links as &$page_link ) {

		if ( strpos( $page_link, $args['prev_text'] ) )
			$page_link = str_replace( '>', ' rel="prev">', $page_link );

		if ( strpos( $page_link, $args['next_text'] ) )
			$page_link = str_replace( '>', ' rel="next">', $page_link );

	}

	// Loop through the page links, removing any unwanted ones as paginate_links() does not provide such fine control
	$real_counter = 0;
	$output = '';

	foreach( $page_links as $counter => $pagination_item ) :

		if ( ( strpos( $pagination_item, '...' ) && $counter == 2 ) || ( $counter == 1 && strpos( $page_links[2], '...' ) ) || ( $counter == 1 && $current_page == 4 && $args['show_all'] === false ) )
			continue;

		// Strip ..., last page
		if ( strpos( $pagination_item, '...' ) || ( strpos( $page_links[$counter ? $counter - 1 : 0], '...') && $counter == count( $page_links ) - 2 ) || ( $counter == 1 && strpos( $page_links[ 2 ], '...' ) ) || ( $counter == 1 && strpos( $page_links[0], $args['prev_text'] ) && $current_page == 4 ) )
			$real_counter--;

		if ( $real_counter >= 6 && strpos( $pagination_item, $args['next_text'] ) === false && $args['show_all'] === false )
			continue;

		$real_counter++;

		$output .= $pagination_item;

	endforeach;

	// Exception for page 1
	if ( isset( $_GET['s'] ) && $_GET['s'] )
		$output = str_replace( "&#038;paged=1'", "'", $output );

	else
		$output = str_replace( $wp_rewrite->pagination_base . '1/', '', $output );

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
	if ( $post === null ) $number_pages = $numpages;
	if ( $post === null ) global $post;

	if ( !$current_page ) $current_page = ( ( $page = get_query_var( 'page' ) ) ? $page : 1 );

	if ( $number_pages == null ) {
		$pages = explode('<!--nextpage-->', $post->post_content);
		$number_pages = count($pages);
	}

	$defaults = array(
		'next_text' => __('Next &raquo;'),
		'prev_text' => __('&laquo; Prev')
	);
	$args = wp_parse_args( $args, $defaults );

	// mid-size depends on what page you are on, as it applies to each side of current_page
	if ( $current_page > ($number_pages - 3) ) {
		$mid_size = 5 - ($number_pages - ($current_page) + 1 );
	} elseif ( $current_page >= 5 ) {
		$mid_size = 2;
	} elseif ( $current_page == 1 ) {
		$mid_size = 4;
	} else {
		$mid_size = 5 - $current_page + 1;
	}

	if ( is_preview() ) {
		$base = get_permalink( $post->ID ) . '&page=%#%';
	} else {
		$base = trailingslashit( get_permalink( $post->ID ) ) . '%#%/';
	}

	$page_links = paginate_links( array(
		'base' => $base,
		'format' => '',
		'prev_text' => $args['prev_text'],
		'next_text' => $args['next_text'],
		'total' => $number_pages,
		'current' => $current_page,
		'mid_size' => $mid_size,
		'end_size' => 1,
		'type' => 'array'
	));

	if ( !is_array( $page_links ) || empty( $page_links ) )
		return;

	// loop through the page links, removing any unwanted ones as paginate_links() does not provide such fine control
	$real_counter = 0;
	foreach( $page_links as $counter => $pagination_item ) :

		//strip ..., last page
		if ( strpos($pagination_item, '...') || ( strpos($page_links[ $counter - 1 ], '...') && $counter == count( $page_links ) - 2 ) || ( $counter == 1 && strpos($page_links[ 2 ], '...') ) || ( $counter == 1 && strpos($page_links[ 0 ], $args['prev_text']) && $current_page == 4 ) )
			continue;

		if ( $real_counter >= 6 && strpos( $pagination_item, $args['next_text'] ) === false )
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

/**
 * Add a submenu class to parent menus
 *
 * @access public
 * @param array $classes
 * @param object $item
 * @todo not sure this actually works?
 * @return null
 */
function hm_submenu_class( $classes, $item ) {

    if ( get_post_meta_by( array( 'meta_value', 'meta_key' ), array( $item->ID, '_menu_item_menu_item_parent' ) ) )
        $classes[] = 'menu_parent';

    return $classes;
}

add_filter( 'nav_menu_css_class', 'hm_submenu_class', 10, 2 );

/**
 * hm_touch_time function.
 *
 * @access public
 * @param int $timestamp
 * @param string $name. (default: 'hm_time_')
 * @return null
 */
function hm_touch_time( $timestamp, $name = 'hm_time_' ) {

	global $wp_locale, $post, $comment;

	$time_adj = current_time( 'timestamp' );
	$post_date = $post->post_date;

	$jj = date( 'd', $timestamp );
	$mm = date( 'm', $timestamp );
	$aa = date( 'Y', $timestamp );
	$hh = date( 'H', $timestamp );
	$mn = date( 'i', $timestamp );
	$ss = date( 's', $timestamp );

	$month = "<select name=\"{$name}mm\">\n";

	for ( $i = 1; $i < 13; $i = $i +1 ) {

		$month .= "\t\t\t" . '<option value="' . zeroise( $i, 2 ) . '"';

		if ( $i == $mm )
			$month .= ' selected="selected"';

		$month .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";

	}

	$month .= '</select>';

	$day	= '<input style="width: 2.1em;" type="text" name="' . $name . 'jj" value="' . $jj . '" size="2" maxlength="2" autocomplete="off" />';
	$year	= '<input style="width: 3.5em;" type="text" name="' . $name . 'aa" value="' . $aa . '" size="4" maxlength="4" autocomplete="off" />';
	$hour	= '<input style="width: 2.1em;" type="text" name="' . $name . 'hh" value="' . $hh . '" size="2" maxlength="2" autocomplete="off" />';
	$minute = '<input style="width: 2.1em;" type="text" name="' . $name . 'mn" value="' . $mn . '" size="2" maxlength="2" autocomplete="off" />';

	echo '<div class="timestamp-wrap">';

	/* translators: 1: month input, 2: day input, 3: year input, 4: hour input, 5: minute input */
	printf(__('%1$s%2$s, %3$s @ %4$s : %5$s'), $month, $day, $year, $hour, $minute);

	echo '</div><input type="hidden" id="ss" name="' . $name . 'ss" value="' . $ss . '" />';

}

/**
 * hm_touch_time_get_time_from_data function.
 *
 * @access public
 * @param mixed $name
 * @param mixed $data
 * @return string
 */
function hm_touch_time_get_time_from_data( $name, $data ) {

	$string = $data[ $name . 'aa' ] . '-' . $data[ $name . 'mm' ] . '-' . $data[ $name . 'jj' ] . ' ' . $data[ $name . 'hh' ] . ':' . $data[ $name . 'mn' ] . ':' . $data[ $name . 'ss' ];
	return strtotime( $string );

}

/**
 * Disable the admin bar by role.
 *
 * add them support for hm_disable_admin_bar passing an array of roles as the second param.
 *
 * @return [type]
 */
function hm_disable_admin_bar() {

	if( is_admin() || ! is_user_logged_in() )
		return;

	global $_wp_theme_features;

	$maybe_remove = array_intersect( (array) wp_get_current_user()->roles, isset( $_wp_theme_features['hm_disable_admin_bar'][0] ) ? (array) $_wp_theme_features['hm_disable_admin_bar'][0] : array() );

	if ( is_user_logged_in() && current_theme_supports( 'hm_disable_admin_bar' ) && ! empty( $maybe_remove ) ) :
		show_admin_bar( false );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
		wp_dequeue_script( 'admin-bar' );
		wp_dequeue_style( 'admin-bar' );
	endif;

}
add_action( 'init', 'hm_disable_admin_bar' );


/**
 * Disable the admin bar and admin bar prefs for subscribers
 *
 * @access public
 * @return null
 */
function hm_disable_admin_bar_for_subscribers() {

	if( current_theme_supports( 'hm_disable_admin_bar_for_subscribers' ) )
		add_theme_support( 'hm_disable_admin_bar', array( 'subscriber' ) );

}
add_action( 'init', 'hm_disable_admin_bar_for_subscribers', 1 );



/**
 * Gets an array of a specified property from an array of objects. Eg, returns all IDs of $wp_query->posts when passed 'ID'.
 *
 * @param array $array
 * @param string $property
 * @return array
 */
function hm_get_object_properties_from_array( $array, $property ) {

	if ( !is_array( $array ) )
		return array();

	$properties = array();

	foreach ( $array as $value ) {

		$value = (object) $value;

		if ( isset( $value->$property ) )
			$properties[] = $value->$property;

	}

	return $properties;

}


/**
 * Automatically pluralize a string. Adds "es" to nouns ending in "s", "ies" for "y", etc
 *
 * @param string $str
 * @return string
 */
function hm_pluralize_string( $str ) {

	$endings = array(
		's' => 'ses',
		'y' => 'ies'
	);

	$ending = substr( $str, strlen($str)-1, 1 );

	if( array_key_exists( $ending, $endings ) )
		$str = substr( $str, 0, strlen( $str ) - 1 ) . $endings[$ending];

	else
		$str = $str . 's';

	return $str;

}

/**
 * Echo html class attribute with <code>$classes</code> if <code>$bool</code> is true
 *
 * @param string $classes
 * @param bool $bool
 * @return null
 */
function hma_class( $classes, $bool ) {

	if ( $bool ) { ?>

	 class="<?php echo $classes; ?>"

	<?php }

}


if ( ! function_exists( 'unregister_post_type' ) ) :

function unregister_post_type( $post_type ) {

	global $wp_post_types;

	if ( isset( $wp_post_types[ $post_type ] ) ) {
		unset( $wp_post_types[ $post_type ] );
		return true;
	}

	return false;

}

endif;

/**
 * Removed all referenced to the WordPress links functioanlity - this is off by default, but generaly who wants Links?
 *
 * @access public
 * @return null
 */
function hm_remove_wp_links() {

	add_action( 'admin_menu', '_hm_remove_wp_link_callback' );

}

/**
 * Inermal callback function used in hm-remove_wp_link
 *
 * @access private
 */
function _hm_remove_wp_link_callback() {

	remove_menu_page( 'link-manager.php' );

}

if ( ! function_exists( 'is_login' ) ) :

/**
 * Simple way to check whether you are on the login page
 *
 * @return bool
 */
function is_login() {
    return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
}

endif;


/**
 * Like get_template_part() put lets you pass args to the template file
 * Args are available in the tempalte as $template_args array
 * @param string filepart
 * @param mixed wp_args style argument list
 */
function hm_get_template_part( $file, $template_args = array(), $cache_args = array() ) {

	$template_args = wp_parse_args( $template_args );
	$cache_args = wp_parse_args( $cache_args );

	if ( $cache_args ) {

		foreach ( $template_args as $key => $value ) {
			if ( is_scalar( $value ) || is_array( $value ) ) {
				$cache_args[$key] = $value;
			} else if ( is_object( $value ) && method_exists( $value, 'get_id' ) ) {
				$cache_args[$key] = call_user_method( 'get_id', $value );
			}
		}

		if ( ( $cache = wp_cache_get( $file, serialize( $cache_args ) ) ) !== false ) {

			if ( ! empty( $template_args['return'] ) )
				return $cache;

			echo $cache;
			return;
		}

	}

	do_action( 'start_operation', 'hm_template_part::' . $file );

	if ( file_exists( get_stylesheet_directory() . '/' . $file . '.php' ) )
		$file_path = get_stylesheet_directory() . '/' . $file . '.php';

	elseif ( file_exists( get_template_directory() . '/' . $file . '.php' ) )
		$file_path = get_template_directory() . '/' . $file . '.php';

	ob_start();
	$return = require( $file_path );
	$data = ob_get_clean();

	do_action( 'end_operation', 'hm_template_part::' . $file );

	if ( $cache_args ) {
		wp_cache_set( $file, $data, serialize( $cache_args ), 3600 );
	}

	if ( ! empty( $template_args['return'] ) )
		if ( $return === false )
			return false;
		else
			return $data;

	echo $data;
}

/**
 * Checks where a given term of term from a given taxonomy was queried by in global $wp_query
 *
 * @param string filepart
 * @param mixed wp_args style argument list
 */
function hm_is_queried_object( $term_or_taxonomy ) {

	global $wp_query;

	// tax
	if ( is_string( $term_or_taxonomy ) ) {

		if ( $wp_query->tax_query ) {
			foreach ( $wp_query->tax_query->queries as $query ) {

				if ( $query['taxonomy'] == $term_or_taxonomy )
					return true;

			}
		}

		if ( ! empty( $wp_query->_post_parent_query ) ) {
			foreach ( $wp_query->_post_parent_query->tax_query->queries as $query ) {

				if ( $query['taxonomy'] == $term_or_taxonomy )
					return true;

			}
		}

	} else if ( is_object( $term_or_taxonomy ) ) {

		foreach ( $wp_query->tax_query->queries as $query ) {

			if ( $query['field'] == 'slug' && in_array( $term_or_taxonomy->slug, $query['terms'] ) )
				return true;

			if ( in_array( $term_or_taxonomy->term_id, $query['terms'] ) )
				return true;
		}
	}
}