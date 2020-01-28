<?php
add_action( 'admin_menu', 'km_rpbt_cache_admin_menu' );

/**
 * Adds a settings page for this plugin.
 */
function km_rpbt_cache_admin_menu() {
	$page_hook = add_options_page(
		__( 'Related Posts by Taxonomy Cache', 'rpbt-cache' ),
		__( 'Related Posts by Taxonomy Cache', 'rpbt-cache' ),
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
		$plugin   = km_rpbt_plugin();
		$defaults = km_rpbt_get_query_vars();

		$defaults['taxonomies'] = '';

		// Normalise values for use in form fields.
		unset( $defaults['post_id'] );
		$defaults['total'] = -1;
		$defaults['batch'] = 50;

		$booleans = array_filter( (array) $defaults, 'is_bool' );
		foreach ( $booleans as $key => $field ) {
			$defaults[ $key ] = $defaults[ $key ] ? 1 : 0;
		}
	}

	$translation_array = array(
		'settings_page' => plugins_url ( __FILE__ ),
		'show'          => __( 'Show Cache Settings', 'rpbt-cache' ),
		'hide'          => __( 'Hide Cache Settings', 'rpbt-cache' ),
		'reset'         => __( 'Reset Default Values', 'rpbt-cache' ),
		'defaults'      => $defaults,
		'nonce_flush'   => wp_create_nonce( 'rpbt_cache_nonce_flush' ),
		'nonce_ajax'    => wp_create_nonce( 'rpbt_cache_nonce_ajax' ),
	);

	wp_localize_script( 'rpbt_cache', 'rpbt_cache', $translation_array );
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

	if ( ! ( $plugin instanceof Related_Posts_By_Taxonomy_Defaults ) ) {
		$error = __( 'Related Posts by Taxonomy plugin is not installed or activated!', 'rpbt-cache' );
		echo '<div class="error"><p>' . $error . '</p></div></div>';
		return;
	}

	// Check if cache is enabled.
	if ( ! ( km_rpbt_plugin_supports( 'cache' ) && km_rpbt_is_cache_loaded() ) ) {
		$error = __( 'The cache for the Related Posts by Taxonomy plugin is not enabled!', 'rpbt-cache' );
		echo '<div class="error"><p>' . $error . '</p></div></div>';
		return;
	}

	$cache      = $plugin->cache;
	$taxonomies = implode( ', ', array_keys( $plugin->taxonomies ) );
	$post_types = implode( ', ', array_keys( $plugin->post_types ) );

	// Description for the settings page form fields.
	$desc = array(
		'order'            => __( 'DESC, ASC, or RAND. Default: DESC', 'rpbt-cache' ),
		'taxonomies'       => __( "empty or comma separated list of taxonomies", 'rpbt-cache' )
		. '<br/>' . sprintf( __( "Available taxonomies: %s", 'rpbt-cache' ), $taxonomies )
		. '<br/>' . __( "Default: empty (all public taxonomies)", 'rpbt-cache' ),
		'batch'            => __( 'How many posts to cache in batches. Default: 50', 'rpbt-cache' ),
		'total'            => __( 'Amount of posts to cache. Default: -1 (cache all posts)', 'rpbt-cache' ),
		'post_types'       => __( "Comma separated list of post types", 'rpbt-cache' )
		. '<br/>' . sprintf( __( "Available post types: %s", 'rpbt-cache' ), $post_types )
		. '<br/>' . __( "Default: post", 'rpbt-cache' ),
		'limit_posts'      => __( 'Default: -1 (don\'t limit posts)', 'rpbt-cache' ),
		'limit_month'      => __( 'Default: empty or 0 (don\'t limit by months)', 'rpbt-cache' ),
		'orderby'          => __( 'post_date or post_modified. Default: post_date', 'rpbt-cache' ),
		'post_thumbnail'   => __( 'boolean: 1 or 0. Default: 0', 'rpbt-cache' ),
		'exclude_terms'    => __( 'Comma separated list of term ids. Default: empty.', 'rpbt-cache' ),
		'include_terms'    => __( 'Comma separated list of term ids. Default: empty.', 'rpbt-cache' ),
		'include_parents'  => __( 'boolean: 1 or 0. Default: 0', 'rpbt-cache' ),
		'include_children' => __( 'boolean: 1 or 0. Default: 0', 'rpbt-cache' ),
		'exclude_posts'    => __( 'Comma separated list of post ids. Default: empty.', 'rpbt-cache' ),
		'posts_per_page'   => __( 'Default 5.', 'rpbt-cache' ),
		'public_only'      => __( 'boolean: 1 or 0. Default: 0', 'rpbt-cache' ),
		'include_self'     => __( 'boolean: 1 or 0. Default: 0', 'rpbt-cache' ),
		'meta_key'         => __( 'string: default empty string', 'rpbt-cache' ),
		'meta_value'       => __( 'string: default empty string. Use a comma separeted list for array values', 'rpbt-cache' ),
		'meta_compare'     => __( 'string: default empty string', 'rpbt-cache' ),
		'meta_type'        => __( 'string: default empty string', 'rpbt-cache' ),
		'fields'           => __( "'ids', 'names' or 'slugs' Default empty (returns post objects)", 'rpbt-cache' ),
		'limit_year'       => __( 'Deprecated argument. Default: empty or 0 (don\'t limit by years)', 'rpbt-cache' ),
		'related'          => __( 'Deprecated argument. empty or boolean: 1 or 0. Default: empty', 'rpbt-cache' ),
		'terms'            => __( 'Deprecated argument. Comma separated list of term ids. Default: empty.', 'rpbt-cache' ),
	);

	$fields = array(
		'total'      => -1,
		'batch'      => 50,
		'taxonomies' => $taxonomies );

	$defaults = km_rpbt_get_query_vars();

	// Not used by the widget or shortcode
	// unset( $defaults['fields'] );
	// unset($defaults['related'], $defaults['terms']);

	$fields = array_merge( $fields, $defaults );
	$get_option = false;

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {

		if ( isset( $_POST['cache_posts'] ) ) {
			check_admin_referer( 'rpbt_cache_nonce_ajax' );
			echo '<div class="notice"><p>' . __( 'Please enable Javascript to cache posts', 'rpbt-cache' ) . '</p></div>';
			return;
		}

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
		if ( ! empty( $option ) && is_array( $option ) ) {
			unset( $option['post_id'], $option['count'], $option['fields'] );
			$fields = array_merge( $fields, $option );
		}
	}

	$booleans = array_filter( (array) $defaults, 'is_bool' );
	foreach ( $booleans as $key => $field ) {
		$fields[ $key ] = $fields[ $key ] ? 1 : 0;
	}

	// Start output

	echo '<p>' . __( 'Flush the cache or cache related posts in batches.', 'rpbt-cache' ) . '</p>';
	settings_errors();

	// Add notice if cache was flushed.
	if ( isset( $_POST['flush_cache'] ) ) {
		check_admin_referer( 'rpbt_cache_nonce_flush' );
		$cache->flush_cache();
		echo '<div class="updated"><p>' . __( 'Flushed the Cache', 'rpbt-cache' ) . '</p></div>';
	}

	echo '<div id="rpbt_cache_forms" aria-hidden="false">';

	echo '<h3>' . __( 'Flush Cache', 'rpbt-cache' ) . '</h3>';
	echo '<p>' . __( 'Flush the cache manually', 'rpbt-cache' ) . '</p>';
	echo '<form method="post" action="" style="border-bottom: 1px solid #dedede">';
	wp_nonce_field( 'rpbt_cache_nonce_flush' );
	echo '<input type="hidden" name="flush_cache" value="1" />';
	submit_button( __( 'Flush Cache!', 'rpbt-cache' ) );
	echo '</form>';

	$version = km_rpbt_cache_get_plugin_version();

	if ( RELATED_POSTS_BY_TAXONOMY_CACHE_VERSION !== $version ) {
		$error = __( 'Please update this plugin to the same version as the Related Posts By Taxonomy plugin to cache posts in batches.', 'rpbt-cache' );
		echo '<div class="plugin-error"><p>' . $error . '</p></div></div></div>';
		return;
	}

	echo '<h3>' . __( 'Cache Parameters', 'rpbt-cache' ) . '</h3>';
	echo '<form method="post" action="" id="cache_form">';
	wp_nonce_field( 'rpbt_cache_nonce_ajax' );
	echo "<table class='form-table'>";

	// Form field output
	foreach ( $fields as $key => $value ) {
		$value = esc_attr( $value );
		echo "<tr valign='top'><th scope='row'>{$key}</th>";
		echo "<td><input class='regular-text' type='text' name='{$key}' value='{$value}'>";
		if ( isset( $desc[ $key ] ) ) {
			echo '<p class="description">' . $desc[ $key ] . '</p>';
		}
		echo "</td></tr>";
		if ( 'batch' === $key ) {
			echo '</table>';
			echo '<h3>' . __( 'Widget and Shortcode Settings', 'rpbt-cache' ) . '</h3>';
			echo "<table class='form-table'>";
		}
	}

	// Not used by the widget or shortcode
	//echo '<input type="hidden" name="fields" value="" />';
	echo "</table>\n";

	echo '<p class="submit">';
	echo '<input id="cache_posts" class="button button-primary" type="submit" ';
	echo 'aria-expanded="false" aria-controls="rpbt_cache_progress_bar_container" ';
	echo 'value="' . __( 'Cache Related Posts!', 'rpbt-cache' ) . '" name="cache_posts"></p>';

	echo '</form>';
	echo '</div>';
	echo '</div>';
}
