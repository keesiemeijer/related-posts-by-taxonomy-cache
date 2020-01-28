<?php
/*
Plugin Name: Related Posts By Taxonomy Cache
Version: 2.7.3
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

if ( ! defined( 'RELATED_POSTS_BY_TAXONOMY_CACHE_VERSION' ) ) {
	define( 'RELATED_POSTS_BY_TAXONOMY_CACHE_VERSION', '2.7.3' );
}

include plugin_dir_path( __FILE__ ) . 'functions.php';
include plugin_dir_path( __FILE__ ) . 'ajax.php';
include plugin_dir_path( __FILE__ ) . 'admin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include plugin_dir_path( __FILE__ ) . 'wp-cli.php';
}
