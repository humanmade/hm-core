<?php

/**
 * Advanced parent querying with WP_Query
 *
 * I.e. get all attachments that have post_parents that are prodicts in a given taxonomy.
 * Basically, nested queries for post_parent
 *
 * Below is the hook. Work in progress :)
 */

add_filter( 'parse_query', function( WP_Query $wp_query ) {

	if ( empty( $wp_query->query_vars['post_parent'] ) || ! is_array( $wp_query->query_vars['post_parent'] ) )
		return;

	$wp_query->query_vars['post_parent']['showposts'] = 1;

	// Sub queries can not have SQL_CALC_FOUND_ROWS
	$wp_query->query_vars['post_parent']['no_found_rows'] = true;

	// set some stuff so we can strip it out explicitly
	$wp_query->query_vars['post_parent']['order'] = 'DESC';
	$wp_query->query_vars['post_parent']['order_by'] = 'date';

	// only select IDs as it's used in a subquery
	$wp_query->query_vars['post_parent']['fields'] = 'ids';

	// WP_Query is crap, so doesn't let you get the query without actually running it (which is why we set showposts = 1 above)
	$query = new WP_Query( $wp_query->query_vars['post_parent'] );

	unset( $wp_query->query_vars['post_parent'] );

	$wp_query->_post_parent_query = $query;

	$sql = str_replace( 'ORDER BY wp_posts.post_date DESC LIMIT 0, 1' , '', $query->request );

	add_filter( 'posts_where_request', function( $where, $query ) use ( $sql, $wp_query ) {

		// blah
		if ( $query != $wp_query )
			return $where;

		global $wpdb;

		$where .= " AND $wpdb->posts.post_parent IN (" . $sql . ")";

		return $where;
	}, 10, 2 );

} );


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

	if ( !is_array( $wp_query->query_vars['meta_key'] ) )
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

/**
 * Allows the user of orderby=post__in to oder the posts in the order you queried by
 *
 * @access public
 * @param string $sortby
 * @param object $thequery
 * @return string
 */
function hma_sort_query_by_post_in( $sortby, $thequery ) {
	if ( !empty($thequery->query['post__in']) && isset($thequery->query['orderby']) && $thequery->query['orderby'] == 'post__in' )
		$sortby = "find_in_set(ID, '" . implode( ',', $thequery->query['post__in'] ) . "')";

	return $sortby;
}
add_filter( 'posts_orderby', 'hma_sort_query_by_post_in', 10, 2 );

function hm_allow_any_orderby_to_wp_query( $orderby, $wp_query ) {

	global $wpdb;
	$query = wp_parse_args( $wp_query->query );
	$orders = explode( ' ', isset( $query['orderby'] ) ? $query['orderby'] : '' );

	if( count( $orders ) <= 1  )
		return $orderby;

	// Some internal WordPress queries incorrectly add DESC or ASC to the orderby instead of order
	foreach( $orders as $key => $order ) :
		$order = rtrim( $order, ',' );
		if ( in_array( strtoupper( $order ), array( 'DESC', 'ASC' ) ) ) :
			$orders[$key - 1] .= ' ' . strtoupper( $order );
			unset( $orders[$key] );
		endif;
	endforeach;

	$one_before = '';

	foreach( $orders as $key => $order ) {

		$order = str_replace( $wpdb->posts . '.post_', '', $order );

		$bits = explode( ' ', $order );
		$table_column = in_array( reset( $bits ), array( 'menu_order', 'ID' ) ) ? $order : 'post_' . $order;

		if( strpos( $orderby, $wpdb->posts . '.' . $table_column ) !== false ) {
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

	while( strpos( $orderby, ', ,' ) !== false )
		$orderby = str_replace( ', ,', ', ', $orderby );

	while( strpos( $orderby, ',,' ) !== false )
	$orderby = str_replace( ',,', ', ', $orderby );

	return $orderby;
}
add_filter( 'posts_orderby_request', 'hm_allow_any_orderby_to_wp_query', 10, 2 );
