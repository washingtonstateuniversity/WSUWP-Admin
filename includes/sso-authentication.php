<?php

namespace WSUWP\Admin\SSOAuthentication;

add_filter( 'wsuwp_sso_create_new_user', 'WSUWP\Admin\SSOAuthentication\create_auto_users' );
add_filter( 'wsuwp_sso_create_new_network_user', '__return_true' );
add_filter( 'wsuwp_sso_allow_wp_auth', '__return_true' );

/**
 * Account for a manual flag to enable auto user creation when a user has a
 * valid WSU NID. This will only work currently if the option has been set for
 * a site via WP-CLI.
 *
 * @since 0.5.7
 *
 * @return bool
 */
function create_auto_users() {
	$auto_users_enabled = get_option( 'wsu_enable_auto_users', false );

	if ( 'enabled' === $auto_users_enabled ) {
		return true;
	}

	return false;
}
