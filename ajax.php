<?php
add_action( 'wp_ajax_rpbt_cache_get_cache_settings', 'km_rpbt_cache_get_cache_settings' );

/**
 * Returns the Settings page Options and total post count.
 *
 * Used by ajax action: rpbt_cache_get_cache_parameters
 */
function km_rpbt_cache_get_cache_settings() {
	global $wpdb;

	check_ajax_referer( 'rpbt_cache_nonce_ajax', 'security' );

	$plugin = km_rpbt_plugin();

	if ( !$plugin ) {
		wp_send_json_error( __( 'Plugin not activated', 'rpbt-cache' ) );
	}

	if ( !( isset( $_POST['data'] ) && $_POST['data'] ) ) {
		wp_send_json_error( __( 'No form data', 'rpbt-cache' ) );
	}

	// Parse the settings page form fields.
	wp_parse_str( $_POST['data'], $form_data );

	if ( !( isset( $form_data['post_types'] ) &&  $form_data['post_types'] ) ) {
		wp_send_json_error( __( 'No post types found', 'rpbt-cache' ) );
	}

	$batch = 50;
	if ( isset( $form_data['batch'] ) &&  absint( $form_data['batch'] ) ) {
		$batch = absint( $form_data['batch'] );
	}

	$total = -1;
	if ( isset( $form_data['total'] ) &&  $form_data['total'] ) {
		$total = (int) $form_data['total'];
	}

	$defaults = km_rpbt_get_query_vars();
	$data     = array_merge( $defaults, $form_data );

	$taxonomies  = isset( $data['taxonomies'] ) ? $data['taxonomies'] : $plugin->all_tax;

	$data['batch'] = $batch;
	$data['total'] = $total;
	$data['count'] = km_rpbtc_get_post_types_count( $data['post_types'] );

	// Settings
	$options_data               = array_intersect_key( $data, $defaults );
	$options_data['taxonomies'] = $taxonomies;
	$options_data['batch']      = $data['batch'];
	$options_data['total']      = $data['total'];

	unset( $options_data['post_id'] );
	update_option( 'rpbt_related_posts_cache_args', $options_data );

	// Create parameter list for display with Javascript.
	unset( $options_data['count'] );
	foreach ( array( 'related', 'post_thumbnail' ) as $field ) {
		$options_data[ $field ] = $options_data[ $field ] ? 1 : 0;
	}

	$parameters = '<h3>' . __( 'Cache Settings', 'rpbt-cache' ). '</h3><ul>';
	foreach (  $options_data as $key => $value ) {
		$parameters .= '<li>' . $key . ': ' . $value . '</li>';
	}

	$data['parameters'] = $parameters . '</ul>';

	wp_send_json_success( $data );
}


add_action( 'wp_ajax_rpbt_cache_posts', 'km_rpbt_cache_posts' );


/**
 * Cache related posts in batches.
 *
 * Used by ajax action: rpbt_cache_posts.
 */
function km_rpbt_cache_posts() {

	check_ajax_referer( 'rpbt_cache_nonce_ajax', 'security' );

	$plugin = km_rpbt_plugin();

	if ( !$plugin ) {
		wp_send_json_error( __( 'Plugin not activated', 'rpbt-cache' ) );
	}

	if ( !( isset( $_POST['data'] ) && $_POST['data'] ) ) {
		wp_send_json_error( __( 'No form data', 'rpbt-cache' ) );
	}

	$post_data    = $_POST['data'];
	$total        = isset( $post_data['total'] )  ? (int) $post_data['total']  : -1;
	$total        = ( -1 === $total )             ? (int) $post_data['count']  : $total;
	$batch        = isset( $post_data['batch'] )  ? (int) $post_data['batch']  : 5;
	$offset       = isset( $post_data['offset'] ) ? (int) $post_data['offset'] : 0;
	$data['done'] = false;
	$data['form'] = $post_data;
	$taxonomies   = isset( $post_data['taxonomies'] )  ? $post_data['taxonomies'] : $plugin->all_tax;

	$form_data    = array_merge( km_rpbt_get_query_vars(), $_POST['data'] );

	$args = array(
		'posts_per_page' => $batch,
		'post_type'     => explode( ',', $form_data['post_types'] ),
		'fields'         => 'ids',
	);

	// Add an offset to get a batch if it's not the first batch.
	if ( 0 !== $offset ) {
		$args['offset'] = $offset;
		// Check if new batch exceeds the total posts to cache.
		if ( ( $batch + $offset ) > $total ) {
			$args['posts_per_page'] = $total - $offset;
		}
	} else {
		// Check if batch is smaller as total posts to cache.
		if ( $batch >= $total ) {
			$args['posts_per_page'] = $total;
		}
	}

	// Get the post ids to cache related posts for.
	$post_ids       = get_posts( $args );
	$data['cached'] = count( $post_ids );

	// Check if cached posts has reached total posts to cache.
	if ( ( $data['cached'] + $offset ) >= $total ) {
		$data['done'] = true;
	}

	if ( !empty( $post_ids ) ) {
		// Cache related posts.
		km_rpbtc_cache_related_posts( $form_data, $batch, $post_ids );
	} else {
		// No posts found for offset.
		$data['done'] = true;
	}

	wp_send_json_success( $data );
}