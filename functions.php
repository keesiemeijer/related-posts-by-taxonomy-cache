<?php

function km_rpbtc_get_post_types_count( $post_types ) {

	global $wpdb;

	$post_types = km_rpbt_validate_post_types( $post_types  );

	if ( empty( $post_types ) ) {
		return 0;
	}

	$post_types    = array_map( 'esc_sql', $post_types );
	$post_type_sql = "'" . implode( "', '", $post_types ) . "'";

	// Count all posts in post types.
	$query = "SELECT COUNT(ID) as count
		FROM $wpdb->posts
		WHERE $wpdb->posts.post_type IN ({$post_type_sql})
		AND $wpdb->posts.post_status = 'publish'";

	return $wpdb->get_var( $query );
}
