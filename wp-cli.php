<?php
/**
 * Cache related posts by taxonomy.
 */
class Related_Posts_By_Taxonomy_CLI extends WP_CLI_Command {

	/**
	 * Flush cache.
	 *
	 * ## EXAMPLES
	 *
	 *    wp rpbt-cache flush
	 */
	public function flush() {
		km_rpbt_flush_cache();
		WP_CLI::success( "Cache Flushed." );
	}


	/**
	 * Cache posts in batches.
	 *
	 * ## OPTIONS
	 *
	 * <number>
	 * : How many posts to cache Default none. Choose a number or use 'all'.
	 *
	 * [--batch=<number>]
	 * : How many posts to cache in each batch. Default: 50 (-1 to not cache in batches)
	 *
	 * [--sleep=<number>]
	 * : How many seconds of sleep after a batch. Default: 6 (set to 0 for no sleep)
	 *
	 * [--taxonomies=<taxonomies>]
	 * : Comma separated list of taxonomies. Default: all (all registered taxonomies)
	 *
	 * [--post_types=<post-types>]
	 * : post_types parameter. Comma separated list of post types. Default: post
	 *
	 * [--posts_per_page=<posts-per-page>]
	 * : posts_per_page parameter. Default: 5
	 *
	 * [--order=<order>]
	 * : order parameter. Default DESC (DESC, ASC, or RAND)
	 *
	 * [--limit_posts=<limit-posts>]
	 * : limit_posts parameter. Default: -1 (don't limit posts)
	 *
	 * [--limit_year=<limit-year>]
	 * : limit_year parameter. Default: 0 (bool)
	 *
	 * [--limit_month=<limit-month>]
	 * : limit_month parameter. Default: 0 (bool)
	 *
	 * [--orderby=<orderby>]
	 * : orderby parameter. Default: post_date (post_date or post_modified)
	 *
	 * [--exclude_terms=<exclude-terms>]
	 * : exclude_terms parameter. Comma separated list of term IDs. Default none
	 *
	 * [--include_terms=<include-terms>]
	 * : include_terms parameter. Comma separated list of term IDs. Default none
	 *
	 * [--exclude_posts=<exclude-posts>]
	 * : exclude_posts parameter. Comma separated list of post IDs. Default none
	 *
	 * [--post_thumbnail=<post-thumbnail>]
	 * : post_thumbnail parameter. Default: 0 (bool)
	 *
	 * [--related=<related>]
	 * : related parameter. Default: 1 (bool)
	 *
	 * ## EXAMPLES
	 *
	 *     wp rpbt-cache cache 1000 --posts_per_page=9
	 */
	public function cache( $args, $assoc_args ) {
		list ( $count ) = $args;

		$plugin   = km_rpbt_plugin();
		$defaults = km_rpbt_get_query_vars();

		if ( !$plugin ) {
			WP_CLI::error( 'Error: Could not find plugin instance' );
		}

		$args = wp_parse_args( $assoc_args, $defaults );

		if ( !isset( $args['taxonomies'] ) ) {
			$args['taxonomies'] =  '';
		}

		$post_types = explode( ',', $args['post_types'] );
		$args       = km_rpbt_sanitize_args( $args );

		$taxonomies = $args['taxonomies'];

		if ( $post_types != $args['post_types'] ) {
			WP_CLI::error( sprintf( "Error: invalid post type in post_types: %s.", implode( ', ', $post_types ) ) );
		}

		$post_type_count = km_rpbtc_get_post_types_count( $args['post_types'] );

		if ( !$post_type_count ) {
			WP_CLI::error( sprintf( "Error: No posts found for post types: %s.", implode( ', ', $args['post_types'] ) ) );
		}

		$count = ( 'all' === $count ) ? $post_type_count : absint( $count );

		if ( !$count ) {
			WP_CLI::error( "Error: please provide a valid number or 'all'" );
		}

		$count  = ( $post_type_count >= $count ) ? $count : $post_type_count;
		$notify = \WP_CLI\Utils\make_progress_bar( 'Caching related posts', $count );

		$batch  = 50; // default batch
		$sleep  = ( isset( $args['sleep'] ) ) ? absint( $args['sleep'] ) : 2;

		if ( isset( $args['batch'] ) ) {

			if ( ( -1 === (int) $args['batch'] ) ) {
				$batch = $count;
				$sleep = 0;
			} else {
				$batch = absint( $args['batch'] );
				$batch = $batch ? $batch : 50;
			}
		}

		$batch  = ( $batch > $count  ) ? $count : $batch;

		//unset( $args['taxonomies'], $args['batch'], $args['sleep'] );

		for ( $i=0; $i < $count; $i +=$batch ) {

			$_args = array(
				'posts_per_page' => $batch,
				'post_types'     => $args['post_types'],
				'offset'         => $i,
				'fields'         => 'ids',
			);

			$post_ids = get_posts( $_args );

			if ( !empty( $post_ids ) ) {
				// Cache related posts.
				km_rpbtc_cache_related_posts( $args, $batch, $post_ids, $notify, $sleep );
			}
		}

		$notify->finish();

		WP_CLI::success( sprintf( "%s posts cached.", $count ) );
	}

}

WP_CLI::add_command( 'rpbt-cache', 'Related_Posts_By_Taxonomy_CLI' );