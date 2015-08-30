<?php
/*
Plugin Name: Related Posts By Taxonomy Cache
Version: 1.0-beta1
Plugin URI:
Description: Persistant Cache layer settings page for the Related Posts by Taxonomy plugin. Caches related posts in batches with Ajax.
Author URI:
License: GPLv2 or later
Text Domain: rpbt-cache

Related Posts By Taxonomy
Copyright 2015  Kees Meijer  (email : keesie.meijer@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version. You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

add_action( 'admin_menu', 'km_rpbt_cache_admin_menu' );

/**
 * Adds a settings page for this plugin.
 */
function km_rpbt_cache_admin_menu() {
	$page_hook = add_options_page(
		__( 'Related Posts by Taxonomy Cache', 'shortcode-regex-finder' ),
		__( 'Related Posts by Taxonomy Cache', 'shortcode-regex-finder' ),
		'manage_options',
		'related-posts-by-taxonomy-cache.php',
		'km_rpbt_cache_admin' );

	add_action( 'admin_print_scripts-' . $page_hook, 'km_rpbt_cache_admin_scripts' );
}


/**
 * Register and enqueue scripts
 */
function km_rpbt_cache_admin_scripts() {

	/* Register our script. */
	wp_register_script( 'rpbt_cache', plugins_url( '/cache.js', __FILE__ ), array( 'jquery', 'wp-util' ), false, true );
	wp_enqueue_script( 'rpbt_cache' );

	$defaults = '';

	// Defaults for reset by Javascript.
	if ( function_exists( 'km_rpbt_plugin' ) ) {
		$defaults = km_rpbt_get_default_args();
		// Normalise values for use in form fields.
		unset( $defaults['post_id'] );
		$defaults['total'] = -1;
		$defaults['batch'] = 10;
		foreach ( array( 'related', 'post_thumbnail' ) as $field ) {
			$defaults[ $field ] = $defaults[ $field ] ? 1 : 0;
		}
	}



	$translation_array = array(
		'settings_page' => plugins_url ( __FILE__ ),
		'show'          => __( 'Show Cache Settings', 'rpbt-cache' ),
		'hide'          => __( 'Hide Cache Settings', 'rpbt-cache' ),
		'reset'         => __( 'Reset Default Values', 'rpbt-cache' ),
		'defaults'      => $defaults,
		'nonce'         => wp_create_nonce( 'rpbt_cache_nonce' ),
	);

	wp_localize_script( 'rpbt_cache', 'rpbt_cache', $translation_array );
}


add_action( 'wp_ajax_rpbt_cache_get_cache_settings', 'km_rpbt_cache_get_cache_settings' );

/**
 * Returns the Settings page Options and total post count.
 *
 * Used by ajax action: rpbt_cache_get_cache_parameters
 */
function km_rpbt_cache_get_cache_settings() {
	global $wpdb;

	check_ajax_referer( 'rpbt_cache_nonce', 'nonce' );

	$plugin = km_rpbt_plugin();

	if(!$plugin) {
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

	$batch = 5;
	if ( isset( $form_data['batch'] ) &&  absint( $form_data['batch'] ) ) {
		$batch = absint( $form_data['batch'] );
	}

	$total = -1;
	if ( isset( $form_data['total'] ) &&  $form_data['total'] ) {
		$total = (int) $form_data['total'];
	}

	$data          = $plugin->cache->sanitize_cache_args( $form_data );
	$data['batch'] = $batch;
	$data['total'] = $total;

	// Create post types sql
	$post_types    = explode( ',', $data['post_types'] );
	$post_types    = array_map( 'esc_sql', $post_types );
	$post_type_sql = "'" . implode( "', '", $post_types ) . "'";

	// Count all posts in post types.
	$query = "SELECT COUNT(ID) as count
		FROM $wpdb->posts
		WHERE $wpdb->posts.post_type IN ({$post_type_sql})
		AND $wpdb->posts.post_status = 'publish'";

	$data['count'] = $wpdb->get_var( $query );

	// Safe the settings page options.
	unset( $data['post_id'] );
	update_option( 'rpbt_related_posts_cache_args', $data );

	// Create parameter list for display with Javascript.
	$list = $data;
	unset( $list['count'] );
	foreach ( array( 'related', 'post_thumbnail' ) as $field ) {
		$list[ $field ] = $list[ $field ] ? 1 : 0;
	}

	$parameters = '<h3>' . __( 'Cache Settings', 'rpbt-cache' ). '</h3><ul>';
	foreach (  $list as $key => $value ) {
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

	check_ajax_referer( 'rpbt_cache_nonce', 'nonce' );

	$plugin = km_rpbt_plugin();

	if(!$plugin) {
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

	$form_data = $plugin->cache->sanitize_cache_args( $_POST['data'] );

	$args = array(
		'posts_per_page' => $batch,
		'post_types'     => explode( ',', $form_data['post_types'] ),
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
		foreach ( $post_ids as $post_id ) {
			$form_data['post_id'] = $post_id;
			$plugin->cache->get_related_posts( $form_data );
		}
	} else {
		// No posts found for offset.
		$data['done'] = true;
	}

	wp_send_json_success( $data );
}


add_action( "admin_footer", "km_rpbt_cache_template" );

/**
 * Add a progress bar template for Javascript to the footer.
 */
function km_rpbt_cache_template() {
	include_once plugin_dir_path( __FILE__ ) . "/template.php";
}


/**
 * Admin page output.
 */
function km_rpbt_cache_admin() {

	echo '<div class="wrap rpbt_cache">';
	echo '<h1>' . __( 'Related Posts by Taxonomy Cache', 'rpbt-cache' ) . '</h1>';

	$plugin = false;
	if ( function_exists( 'km_rpbt_plugin' ) ) {
		$plugin = km_rpbt_plugin();
	}

	if ( !( $plugin instanceof Related_Posts_By_Taxonomy_Defaults ) ) {
		$error = __( 'Related Posts by Taxonomy plugin is not installed or activated!', 'rpbt-cache' );
		echo '<div class="error"><p>' . $error . '</p></div></div>';
		return;
	}

	$cache_exists = $plugin->cache instanceof Related_Posts_By_Taxonomy_Cache;

	// Check if cache is enabled.
	if ( !$cache_exists || !class_exists( 'Related_Posts_By_Taxonomy_Cache' ) ) {
		$error = __( 'The cache for the Related Posts by Taxonomy plugin is not enabled!', 'rpbt-cache' );
		echo '<div class="error"><p>' . $error . '</p></div></div>';
		return;
	}

	$cache      = $plugin->cache;
	$taxonomies = implode( ', ', array_keys( $plugin->taxonomies ) );
	$post_types = implode( ', ', array_keys( $plugin->post_types ) );

	// Description for the settings page form fields.
	$desc = array(
		'order'          => __( 'DESC, ASC, or RAND. Default DESC', 'rpbt-cache' ),
		'taxonomies'     => $taxonomies,
		'batch'          => __( 'How many posts to cache in batches. Default 10', 'rpbt-cache' ),
		'total'          => __( 'Amount of posts to cache. Default -1 (cache all posts)', 'rpbt-cache' ),
		'post_types'     => $post_types,
		//'fields'         => __( 'Defaut empty (post objects). Other values ids, names or slugs', 'rpbt-cache' ),
		'limit_posts'    => __( 'Default -1 (don\'t limit posts)', 'rpbt-cache' ),
		'limit_year'     => __( 'Default empty or 0 (don\'t limit by years)', 'rpbt-cache' ),
		'limit_month'    => __( 'Default empty or 0 (don\'t limit by months)', 'rpbt-cache' ),
		'orderby'        => __( 'post_date or post_modified. Default post_date', 'rpbt-cache' ),
		'post_thumbnail' => __( 'boolean: 1 or 0. Default 0', 'rpbt-cache' ),
		'related'        => __( 'boolean: 1 or 0. Default 1', 'rpbt-cache' ),
		'exclude_terms'  => __( 'Comma separated list of term ids. Default empty.', 'rpbt-cache' ),
		'include_terms'  => __( 'Comma separated list of term ids. Default empty.', 'rpbt-cache' ),
		'exclude_posts'  => __( 'Comma separated list of post ids. Default empty.', 'rpbt-cache' ),
		'posts_per_page' => __( 'Default 5.', 'rpbt-cache' ),
	);

	$fields = array(
		'total'      => -1,
		'batch'      => 10,
		'taxonomies' => $taxonomies );

	$defaults = km_rpbt_get_default_args();

	// Not used by the widget or shortcode
	unset( $defaults['fields'] );

	$fields = array_merge( $fields, $defaults );
	$get_option = false;

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		check_admin_referer( 'rpbt_cache_nonce' );

		$_POST     = stripslashes_deep( $_POST );
		$post_args = wp_parse_args( $_POST, $fields );
		$fields    = array_intersect_key( $post_args, $fields );

		$get_option = isset( $_POST['flush_cache'] ) ? true : false;

	} else {
		$get_option = true;
	}

	// Get the settings from the database.
	if ( $get_option ) {
		$option = get_option( 'rpbt_related_posts_cache_args' );
		if ( !empty( $option ) && is_array( $option ) ) {
			unset( $option['post_id'], $option['count'], $option['fields'] );
			$fields = array_merge( $fields, $option );
		}
	}

	foreach ( array( 'related', 'post_thumbnail' ) as $field ) {
		$fields[ $field ] = $fields[ $field ] ? 1 : 0;
	}

	// Start output

	echo '<p>' . __( 'Flush the cache or cache related posts in batches.', 'rpbt-cache' ) . '</p>';
	settings_errors();

	// Add notice if cache was flushed.
	if ( isset( $_POST['flush_cache'] ) ) {
		$cache->flush_cache();
		echo '<div class="updated"><p>' . __( 'Flushed the Cache', 'rpbt-cache' ) . '</p></div>';
	}

	echo '<div id="rpbt_cache_forms" aria-hidden="false">';

	echo '<h3>' . __( 'Flush Cache', 'rpbt-cache' ) . '</h3>';
	echo '<p>' . __( 'Flush the cache manually', 'rpbt-cache' ) . '</p>';
	echo '<form method="post" action="" style="border-bottom: 1px solid #dedede">';
	wp_nonce_field( 'rpbt_cache_nonce' );
	echo '<input type="hidden" name="flush_cache" value="1" />';
	submit_button( __( 'Flush Cache!', 'rpbt-cache' ) );
	echo '</form>';

	echo '<h3>' . __( 'Cache Parameters', 'rpbt-cache' ) . '</h3>';
	echo '<form method="post" action="" id="cache_form">';
	wp_nonce_field( 'rpbt_cache_nonce' );
	echo "<table class='form-table'>";

	// Form field output
	foreach ( $fields as $key => $value ) {
		$value = esc_attr($value);
		echo "<tr valign='top'><th scope='row'>{$key}</th>";
		echo "<td><input class='regular-text' type='text' name='{$key}' value='{$value}'>";
		if ( isset( $desc[$key] ) ) {
			echo '<p class="description">' . $desc[$key] . '</p>';
		}
		echo "</td></tr>";
		if ( 'batch' === $key ) {
			echo '</table>';
			echo '<h3>' . __( 'Widget and Shortcode Settings', 'rpbt-cache' ) . '</h3>';
			echo "<table class='form-table'>";
		}
	}

	// Not used by the widget or shortcode
	echo '<input type="hidden" name="fields" value="" />';
	echo "</table>\n";

	echo '<p class="submit">';
	echo '<input id="cache_posts" class="button button-primary" type="submit" ';
	echo 'aria-expanded="false" aria-controls="rpbt_cache_progress_bar_container" ';
	echo 'value="' . __( 'Cache Related Posts!', 'rpbt-cache' ) . '" name="cache_posts"></p>';

	echo '</form>';
	echo '</div>';
	echo '</div>';
}