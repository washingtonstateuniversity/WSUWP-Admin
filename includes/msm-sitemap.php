<?php

namespace WSUWP\Admin\MSM_Sitemap;

add_filter( 'msm_sitemap_entry_post_type', __NAMESPACE__ . '\\supported_post_types' );

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
