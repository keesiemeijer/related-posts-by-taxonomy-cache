<?php

/**
 * Get post count for multiple or single post types.
 *
 * @param array   $post_types Array with post types.
 * @return int   Post count for single or multiple post types.
 */
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


/**
 * Removes nonce data from $_POST variable.
 *
 * @param array   $data $_POST
 * @return array       $_POST data with nonce removed.
 */
function km_rpbtc_remove_nonce_data( $data ) {
	unset( $data['_wpnonce'], $data['_wp_http_referer'] );
	return $data;
}


/**
 * Applies the widget or shortcode filters to arguments
 *
 * @param string  $filter  'widget' or 'shortcode'.
 * @param array   $args    Array with arguments
 * @param integer $post_id Current post id
 * @return array           Filtered arguments
 */
function km_rpbtc_apply_filters( $filter, $args, $post_id = 0 ) {

	if ( 'shortcode' === $filter ) {
		$defaults = km_rpbt_get_shortcode_atts();
		$defaults = apply_filters( 'related_posts_by_taxonomy_shortcode_defaults', $defaults );
		$args = shortcode_atts( (array) $defaults, $args, 'related_posts_by_tax' );
		$args = apply_filters( 'related_posts_by_taxonomy_shortcode_atts', $args );
		$args['post_id'] = $post_id;
	}

	if ( 'widget' === $filter ) {
		$args['post_id'] = $post_id;
		$args['post_types'] = explode( ',', $args['post_types'] );
		$args = apply_filters( 'related_posts_by_taxonomy_widget_args', $args );
		$args['post_types'] = implode( ',', $args['post_types'] );
	}

	return $args;
}


/**
 * Sleep if batch count is reached.
 * If widget or shortcode filters are used multiple cache entries are created.
 *
 * @param int     $count Count of cached posts in a batch.
 * @param int     $batch Batch number
 * @return int        Count of cached posts in a batch. Could be 0 if the batch was reached.
 */
function km_rpbtc_sleep( $count , $batch ) {

	if ( $count > $batch ) {
		sleep( 2 );
		$count = 0; // Reset count
	}

	return $count;
}


/**
 * Cache posts in batches.
 * Sleep between batches.
 * 
 * @param  array   $data     Arguments to cache related posts for.
 * @param  integer $batch    Batch number.
 * @param  array   $post_ids Array with post ids to cache related posts for.
 * @return void
 */
function km_rpbtc_cache_related_posts( $data, $batch = 50, $post_ids = array() ) {

	$plugin = km_rpbt_plugin();

	if ( !$plugin || empty( $post_ids ) ) {
		return;
	}

	$sanitized_data = $plugin->cache->sanitize_cache_args( $data );
	$i              = 0;

	foreach ( $post_ids as $post_id ) {
		$widget = $shortcode = false;
		$sanitized_data['post_id'] = $post_id;

		$shortcode_data   = km_rpbtc_apply_filters( 'shortcode', $data, $post_id );
		$shortcode_data_s = $plugin->cache->sanitize_cache_args( $shortcode_data );

		$widget_data      = km_rpbtc_apply_filters( 'widget', $data, $post_id );
		$widget_data_s    = $plugin->cache->sanitize_cache_args( $widget_data );

		if ( $shortcode_data_s != $sanitized_data ) {
			// Shortcode args was filtered
			$shortcode = true;

			$plugin->cache->get_related_posts( $shortcode_data );
			$i = km_rpbtc_sleep( ++$i , $batch );
		}

		if ( $widget_data_s != $sanitized_data ) {
			// Widget args was filtered
			$widget = true;

			if ( $shortcode ) {

				// Don't cache widget if it has the same $args as the shortcode
				if ( $widget_data_s != $shortcode_data_s ) {
					$plugin->cache->get_related_posts( $widget_data );
					$i = km_rpbtc_sleep( ++$i , $batch );
				}
			} else {

				$plugin->cache->get_related_posts( $widget_data );
				$i = km_rpbtc_sleep( ++$i , $batch );
			}
		}

		if ( !( $widget && $shortcode ) ) {
			// Widget AND shortcode not filtered.
			$plugin->cache->get_related_posts( $sanitized_data );
			$i = km_rpbtc_sleep( ++$i , $batch );
		}
	}
}