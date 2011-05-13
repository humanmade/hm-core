<?php
function cwp_filter_custom_post_status( $where ) {
	global $cwp_custom_post_status;
	if( $cwp_custom_post_status ) {
		$stati = array_filter( explode( ',', $cwp_custom_post_status ) );
		if( count( $stati ) > 1 ) {
			foreach( $stati as $key => $status ) {
				trim(&$status);
				if( $key === 0 ) {
					$where .= " AND (wp_posts.post_status = '$status'";
				} else {
					$where .=  " OR wp_posts.post_status = '$status'";
				}
				if( $key + 1 === count( $stati ) )
					$where .= ')';
			}
				
		} else {
			$where .= " AND (wp_posts.post_status = '$cwp_custom_post_status')";
		}
		$cwp_custom_post_status = null;
	}
	
	
	return $where;
}
add_filter( 'posts_where', 'cwp_filter_custom_post_status' );
?>