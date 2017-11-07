<?php

namespace WSUWP\Admin\Response_Headers;

add_filter( 'wp_headers', 'WSUWP\Admin\Response_Headers\filter_frame_headers', 20 );

/**
 * Removes the `X-Frame-Options: ALLOW-FROM` rule added by the Customizer Manager to avoid
 * a conflict with WSU's nginx config of `X-Frame-Options: SAMEORIGIN`.
 *
 * The Customizer also adds `frame-ancestors` which offers better protection than
 * `X-Frame-Options` in general.
 *
 * @since 1.2.0
 *
 * @param array $headers The current headers to send with a response.
 *
 * @return array Modified headers.
 */
function filter_frame_headers( $headers ) {
	if ( isset( $headers['X-Frame-Options'] ) && 'ALLOW-FROM' === substr( $headers['X-Frame-Options'], 0, 10 ) ) {
		unset( $headers['X-Frame-Options'] );
	}

	return $headers;
}
