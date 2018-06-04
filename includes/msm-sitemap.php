<?php

namespace WSUWP\Admin\MSM_Sitemap;

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
