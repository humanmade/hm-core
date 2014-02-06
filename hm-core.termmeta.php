<?php

/**
 * Hook in on init and setup the termmeta table
 */
function hm_add_term_meta_table() {

	global $wpdb;

	if ( ! current_theme_supports( 'term-meta' ) )
		return;

	// Create the table if it doesn't already exist
	hm_create_term_meta_table();

	$wpdb->tables[] = 'termmeta';
	$wpdb->termmeta = $wpdb->prefix . 'termmeta';

}
add_action( 'init', 'hm_add_term_meta_table' );

/**
 * Creates the termmeta table if it deos not exist
 *
 * @todo should we be specifying MyISAM, can't it just use default?
 */
function hm_create_term_meta_table() {

	global $wpdb;

	// Check if the table is already exists
	if ( get_option( 'hm_created_term_meta_table' ) )
		return;

	$wpdb->query( "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}termmeta` (
		  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
		  `meta_key` varchar(255) DEFAULT NULL,
		  `meta_value` longtext,
		  PRIMARY KEY (`meta_id`),
		  KEY `term_id` (`term_id`),
		  KEY `meta_key` (`meta_key`)
		) ENGINE=`DEFAULT` AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;" );

	update_option( 'hm_created_term_meta_table', true );

	return true;

}

if ( ! function_exists( 'add_term_meta' ) ) :
/**
 * Add meta data field to a term.
 *
 * @param int $term_id term ID.
 * @param string $key Metadata name.
 * @param mixed $value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
    return add_metadata( 'term', $term_id, $meta_key, $meta_value, $unique );
}
endif;

if ( ! function_exists( 'delete_term_meta' ) ) :
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
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
    return delete_metadata( 'term', $term_id, $meta_key, $meta_value );
}
endif;

if ( ! function_exists( 'get_term_meta' ) ) :
/**
 * Retrieve term meta field for a term.
 *
 * @param int $term_id term ID.
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function get_term_meta( $term_id, $key, $single = false ) {
    return get_metadata( 'term', $term_id, $key, $single );
}
endif;

if ( ! function_exists( 'update_term_meta' ) ) :
/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
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
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
    return update_metadata( 'term', $term_id, $meta_key, $meta_value, $prev_value );
}
endif;

if ( ! function_exists( 'get_term_custom' ) ) :
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

    if ( ! wp_cache_get( $term_id, 'term_meta' ) )
        update_termmeta_cache( $term_id );

    return wp_cache_get( $term_id, 'term_meta' );
}
endif;

if ( ! function_exists( 'update_termmeta_cache' ) ) :
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
function update_termmeta_cache( $term_ids ) {
    return update_meta_cache( 'term', $term_ids );
}
endif;