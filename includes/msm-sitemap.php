<?php

namespace WSUWP\Admin\MSM_Sitemap;

add_filter( 'msm_sitemap_entry_post_type', __NAMESPACE__ . '\\supported_post_types' );
add_action( 'init', __NAMESPACE__ . '\\remove_msm_sitemap_admin', 11 );

/**
 * Filter the post types supported by the MSM Sitemap plugin.
 *
 * @since 1.7.0
 *
 * @return array
 */
function supported_post_types() {
	$post_types = array(
		'post',
		'page',
	);

	return $post_types;
}

/**
 * Hide the admin menu and screen for the MSM Sitemap plugin. The building of
 * sitemaps through this plugin is controled solely through WP-CLI and cron.
 *
 * @since 1.4.3
 */
function remove_msm_sitemap_admin() {
	remove_action( 'admin_menu', array( 'Metro_Sitemap', 'metro_sitemap_menu' ) );
}
