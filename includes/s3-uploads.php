<?php

namespace WSUWP\Admin\S3_Uploads;

// Hook in before S3 Uploads filters the data.
add_filter( 'wp_delete_file', '\WSUWP\Admin\S3_Uploads\delete_file', 3, 1 );
add_action( 'wsuwp_clear_s3_proxy_cache', '\WSUWP\Admin\S3_Uploads\clear_s3_proxy_cache', 10, 2 );

/**
 * Schedule an event to clear nginx's proxy cache 30 seconds after
 * file deletion. This should then occur after the file is removed
 * from AWS S3.
 *
 * @since 1.3.2
 *
 * @param string $file
 *
 * @return string
 */
function delete_file( $file ) {
	if ( ! defined( 'S3_UPLOADS_BUCKET_URL' ) || ! defined( 'S3_UPLOADS_BUCKET' ) || ! defined( 'S3_PROXY_CACHE_HEADER' ) ) {
		return $file;
	}

	$url = str_replace( 's3:// ' . S3_UPLOADS_BUCKET, S3_UPLOADS_BUCKET_URL, $file );

	wp_schedule_single_event( time() + 30, 'wsuwp_clear_s3_proxy_cache', array(
		$url,
	) );

	return $file;
}

/**
 * Make a cache breaking request to the S3 bucket URL for a file.
 *
 * @since 1.3.2
 *
 * @param string $url
 */
function clear_s3_proxy_cache( $url ) {
	if ( ! defined( 'S3_PROXY_CACHE_HEADER' ) ) {
		return;
	}

	wp_safe_remote_head( $url, array(
		'headers' => array(
			S3_PROXY_CACHE_HEADER => true,
		),
	) );
}
