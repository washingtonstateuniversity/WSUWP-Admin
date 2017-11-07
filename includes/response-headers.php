<?php

namespace WSUWP\Admin\Response_Headers;

add_filter( 'wp_headers', 'WSUWP\Admin\Response_Headers\filter_frame_headers', 20 );
add_filter( 'wp_headers', 'WSUWP\Admin\Response_Headers\document_revisions_headers', 10 );
add_filter( 'nocache_headers', 'WSUWP\Admin\Response_Headers\filter_404_no_cache_headers', 10 );

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
 * Determine what Content-Type header should be sent with a document revisions
 * request. If we don't filter this, text/html is used by default as WordPress
 * sets the header before the WP Document Revisions plugin is able to.
 *
 * @since 0.3.1
 *
 * @global \wpdb $wpdb
 * @global \wp   $wp
 *
 * @param array $headers List of headers currently set for this request.
 *
 * @return array Modified list of headers.
 */
function document_revisions_headers( $headers ) {
	global $wpdb, $wp;

	if ( 'documents/([0-9]{4})/([0-9]{1,2})/([^.]+)\.[A-Za-z0-9]{3,4}/?$' !== $wp->matched_rule ) {
		return $headers;
	}

	// Only modify headers for document revisions.
	if ( isset( $wp->query_vars['post_type'] ) && 'document' !== $wp->query_vars['post_type'] ) {
		return $headers;
	}

	// Retrieve post_content for the post matching this document request. This post_content is really
	// the ID of the attachment the document is a mask for.
	$post_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_content, post_password FROM $wpdb->posts WHERE post_type='document' AND post_name = %s", sanitize_title( $wp->query_vars['name'] ) ) );
	$post_id = absint( $post_data->post_content );
	$post_password = $post_data->post_password;

	if ( empty( absint( $post_id ) ) ) {
		return $headers;
	}

	// If the document has a password assigned and the cookie does not exist, don't modify.
	if ( $this->post_password_required( $post_password ) ) {
		return $headers;
	}

	// Remove the default WordPress Link header.
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

	// Remove the WP-API LINK header.
	remove_action( 'template_redirect', 'json_output_link_header', 11 );

	$file = get_attached_file( $post_id );

	// No file exists to load.
	if ( empty( $file ) ) {
		return $headers;
	}

	/**
	 * mime_content_type() is not handled by the S3 stream wrapper, so we
	 * handle content type detection differently when S3 uploads are enabled.
	 */
	if ( function_exists( 's3_uploads_enabled' ) && s3_uploads_enabled() ) {
		include_once __DIR__ . '/upstream-file-mime-type-mapping.php';
		$mime_type_mapping = wsuwp_file_default_mimetype_mapping();
		$mime_type = \S3_Uploads_Local_Stream_Wrapper::getMimeType( $file, $mime_type_mapping );
	} else {
		$mime_type = mime_content_type( $file );
	}

	$file_size = filesize( $file );
	if ( empty( $mime_type ) ) {
		return $headers;
	}

	$headers['Content-Length'] = $file_size;
	$headers['Content-Type'] = $mime_type;
	$headers['Accept-Ranges'] = 'bytes';

	unset( $headers['X-Pingback'] );
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
