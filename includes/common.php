<?php

namespace WSUWP\Admin\Common;

/**
 * Check if the entry of a password is still required for a document.
 *
 * This is a duplicate of the WordPress function and is used only because
 * we don't have a proper `$post` object built with `post_password`
 * included when we need to do our check.
 *
 * @see WordPress post_password_required()
 *
 * @param string $post_password Password possibly assigned to a post.
 *
 * @return bool True if a password is required. False if not.
 */
function post_password_required( $post_password ) {
	if ( empty( $post_password ) ) {
		return false;
	}

	if ( ! isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) ) {
		return true;
	}

	require_once ABSPATH . WPINC . '/class-phpass.php';
	$hasher = new \PasswordHash( 8, true );

	$hash = wp_unslash( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
	if ( 0 !== strpos( $hash, '$P$B' ) ) {
		return true;
	}

	return ! $hasher->CheckPassword( $post_password, $hash );
}
