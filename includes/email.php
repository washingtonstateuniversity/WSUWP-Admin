<?php

namespace WSUWP\Admin\Email;

add_filter( 'update_welcome_user_email', 'WSUWP\Admin\Email\network_welcome_user_email', 10, 4 );

/**
 * Provide a default email to send when welcoming a user to a network.
 *
 * @param string $welcome_email The network welcome email.
 * @param int    $user_id       The user's ID. Unused.
 * @param string $password      The user's password. Unused.
 * @param array  $meta          Meta information about the new site.
 *
 * @return string The modified network welcome email.
 */
function network_welcome_user_email( $welcome_email, $user_id, $password, $meta ) {
	$welcome_email = sprintf(
		'Hi,

A new account has been set up for your WSU Network ID (USERNAME) on SITE_NAME.

This account was created when you were added as a member of %1$s, located at %2$s.

You can login to %1$s with your WSU Network ID and password at %3$s

Welcome!

- WSUWP Platform (wp.wsu.edu)', $meta['site_name'], $meta['home_url'], $meta['admin_url']
	);

	return $welcome_email;
}
