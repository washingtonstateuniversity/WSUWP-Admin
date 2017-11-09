<?php

namespace WSUWP\Admin\Response_Headers;

add_filter( 'wp_headers', 'WSUWP\Admin\Response_Headers\filter_frame_headers', 20 );
add_filter( 'nocache_headers', 'WSUWP\Admin\Response_Headers\filter_404_no_cache_headers', 10 );
add_filter( 'document_revisions_mimetype', '__return_false' );
add_filter( 'document_revisions_serve_file_headers', 'WSUWP\Admin\Response_Headers\document_revisions_serve_file_headers', 10, 2 );

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

/**
 * Filter the headers used when serving a file in WP Document Revisions.
 *
 * @since 1.2.1
 *
 * @param array $headers
 * @param string $file
 *
 * @return array
 */
function document_revisions_serve_file_headers( $headers, $file ) {

	// mime_content_type() is not handled by the S3 stream wrapper used by S3 Uploads,
	// so we handle content type detection differently when S3 Uploads is enabled.
	if ( function_exists( 's3_uploads_enabled' ) && s3_uploads_enabled() ) {
		include_once __DIR__ . '/upstream-file-mime-type-mapping.php';
		$mime_type_mapping = wsuwp_file_default_mimetype_mapping();
		$mime_type = \S3_Uploads_Local_Stream_Wrapper::getMimeType( $file, $mime_type_mapping );
	} else {
		$mime_type = mime_content_type( $file );
	}

	$headers['Content-Type'] = $mime_type;
	$headers['Accept-Ranges'] = 'bytes';

	// Remove the expires header completely so that revisions appear to the public quickly.
	unset( $headers['Expires'] );

	return $headers;
}

/**
 * Remove default "no cache headers" added by WordPress for 404 pages.
 *
 * @since 0.6.4
 *
 * @global \WP_Query $wp_query
 *
 * @param $headers
 *
 * @return mixed
 */
function filter_404_no_cache_headers( $headers ) {
	global $wp_query;

	if ( $wp_query->is_404 && isset( $headers['Pragma'] ) ) {
		unset( $headers['Expires'] );
		unset( $headers['Cache-Control'] );
		unset( $headers['Pragma'] );
	}

	return $headers;
}
