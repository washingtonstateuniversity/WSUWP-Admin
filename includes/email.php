<?php

namespace WSUWP\Admin\Email;

add_filter( 'update_welcome_user_email', 'WSUWP\Admin\Email\network_welcome_user_email', 10, 4 );
add_filter( 'wsuwp_add_user_to_site_email', 'WSUWP\Admin\Email\new_site_user_email', 10 );
add_filter( 'wp_new_user_notification_email_admin', 'WSUWP\Admin\Email\new_user_admin_notification', 10, 3 );

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

/**
 * Filter the email message sent to new users added to sites in WSUWP Multiple Networks.
 *
 * @return string
 */
function new_site_user_email() {
	// 1 = site name, 2 = URL, 3 = role, 4 = login URL, 5 = a vs an
	$message = 'Hello,

You are now %5$s %3$s at %1$s.

Visit this site at %2$s and login with your WSU Network ID at %4$s

Welcome!

- WSUWP Platform (wp.wsu.edu)
';

	return $message;
}

/**
 * Filter the new user notification sent to site admins.
 *
 * @param array    $email    Contains to, subject, message, and headers.
 * @param \WP_User $user     The user just added.
 * @param string   $blogname The name of the site the user was added to.
 *
 * @return array Modified admin email.
 */
function new_user_admin_notification( $email, $user, $blogname ) {
	$message  = sprintf( __( 'A new user has been added to %s:' ), $blogname ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
	$message .= sprintf( __( 'E-mail: %s' ), $user->user_email ) . "\r\n\r\n";
	$message .= 'No action is necessary. This message is purely informative.' . "\r\n\r\n";
	$message .= '- WSUWP Platform (wp.wsu.edu)';

	$email['message'] = $message;
	$email['subject'] = '[%s] New User Added';

	return $email;
}
