# Related Posts by Taxonomy Cache

A persistent Cache layer settings page for the [Related Posts by Taxonomy](https://wordpress.org/plugins/related-posts-by-taxonomy/) plugin. It caches related posts in batches with Ajax.

For more information see the [cache documentation](https://keesiemeijer.wordpress.com/related-posts-by-taxonomy/cache/)

Version:           2.7.3  
Requires at least: 4.3  
Tested up to:      5.3  

You can only cache posts if the version of this plugin is the same as the version of the Related Posts by Taxonomy plugin. If you want to use it with other versions you'll have to set the version [here](https://github.com/keesiemeijer/related-posts-by-taxonomy-cache/blob/a88b040bb497732deb8f0a9b0b6ce25545794ae5/related-posts-by-taxonomy-cache.php#L29). Be aware that using it with older versions it could result in errors as some functions might not exist.

The settings page for this plugin is at `Settings` -> `Related Posts by Taxonomy`.

### WP-CLI
Cache posts or flush the cache with WP-CLI commands.

Example flushing the cache

```
wp rpbt-cache flush
```

Example caching all posts from the post type events

```
wp rpbt-cache cache all --post_types=events
```

Use `wp rpbt-cache cache --help` to see what parameters are available.

### Settings Page

![Settings Page](/../screenshots/screenshots/screenshot-1.png?raw=true)

### Progress Bar

![progress bar](/../screenshots/screenshots/screenshot-2.png?raw=true)
