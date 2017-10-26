<?php

namespace WSUWP\Admin\Restricted_Site_Access;

add_action( 'admin_init', 'WSUWP\Admin\Restricted_Site_Access\remove_admin_notices', 2 );
add_filter( 'restricted_site_access_is_restricted', 'WSUWP\Admin\Restricted_Site_Access\restrict_to_site_members' );
add_filter( 'restricted_site_access_approach', 'WSUWP\Admin\Restricted_Site_Access\adjust_approach' );

/**
 * Remove the admin notices registered by Restricted Site Access
 * after 6.0.0.
 *
 * @since 1.1.5
 */
function remove_admin_notices() {
	remove_action( 'network_admin_notices', array( 'Restricted_Site_Access', 'page_cache_notice' ) );
	remove_action( 'admin_notices', array( 'Restricted_Site_Access', 'page_cache_notice' ) );
}

/**
 * Restrict access to site members rather than logged in users across
 * multiple networks.
 *
 * @since 1.2.0
 *
 * @param bool $is_restricted
 *
 * @return bool
 */
function restrict_to_site_members( $is_restricted ) {
	if ( is_admin() ) {
		return $is_restricted;
	}

	$mode = 'default';
	if ( defined( 'RSA_IS_NETWORK' ) && RSA_IS_NETWORK ) {
		$mode = get_site_option( 'rsa_mode', 'default' );
	}

	if ( defined( 'RSA_IS_NETWORK' ) && RSA_IS_NETWORK && 'enforce' === $mode ) {
		$blog_public = get_site_option( 'blog_public', 2 );
	} else {
		$blog_public = get_option( 'blog_public', 2 );
	}

	if ( 2 === $blog_public && ! is_user_member_of_blog() ) {
		return true;
	}

	return $is_restricted;
}

/**
 * Adjust the restriction approach if an authenticated user who is not a
 * member of the current site. In these cases, we must not try to redirect
 * to the login URL or an infinite redirect will occur.
 *
 * @since 1.2.0
 *
 * @param int $restrict_approach
 *
 * @return int
 */
function adjust_approach( $restrict_approach ) {
	// Non-authenticated users are okay with any of the approaches.
	if ( ! is_user_logged_in() ) {
		return $restrict_approach;
	}

	// Authenticated users can work with any approach except for redirecting to the login URL.
	if ( in_array( $restrict_approach, array( 2, 3, 4 ), true ) ) {
		return $restrict_approach;
	}

	// Show the access restricted message instead of redirecting.
	return 3;
}
