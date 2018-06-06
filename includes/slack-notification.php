<?php

namespace WSUWP\Admin\Slack_Notification;

add_filter( 'pre_update_option_blog_public', __NAMESPACE__ . '\\option_change', 10, 3 );

/**
 * Send a Slack notification whenever a watched option has changed.
 *
 * @since 1.8.0
 *
 * @param mixed $value
 * @param mixed $old_value
 * @param string $option
 * @return mixed
 */
function option_change( $value, $old_value, $option ) {
	if ( $value === $old_value ) {
		return $value;
	}

	$current_user = wp_get_current_user();
	$current_site = get_site();
	$page_view = $_SERVER['REQUEST_URI'];

	if ( 0 === absint( $current_user->ID ) ) {
		$user = 'no-user';
	} else {
		$user = $current_user->user_login;
	}

	$data = array(
		'channel' => '#wsuwp',
		'username' => 'wsuwp-admin',
		'text' => 'Watched Option Update',
		'icon_emoji' => ':flags:',
		'attachments' => array(
			[
				'fallback' => 'Something about the notification to Slack broke, this is fallback text.',
				'pretext'  => 'The following option has been updated on WSUWP. Please verify it was intentional.',
				'color'    => '#33388a',
				'fields'   => array(
					[
						'title' => 'User',
						'value' => $user,
						'short' => true,
					],
					[
						'title' => 'Site Address',
						'value' => esc_url( $current_site->domain . $current_site->path ),
						'short' => true,
					],
					[
						'title' => 'Originating URL',
						'value' => esc_attr( $_SERVER['REQUEST_URI'] ),
						'short' => true,
					],
					[
						'title' => 'Option Changed',
						'value' => esc_attr( $option ),
					],
					[
						'title' => 'Old Value',
						'value' => esc_attr( $old_value ),
						'short' => true,
					],
					[
						'title' => 'New Value',
						'value' => esc_attr( $value ),
						'short' => true,
					],
				),
			],
		),
	);

	$result = wp_remote_post( 'https://hooks.slack.com/services/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', array(
		'body' => wp_json_encode( $data ),
	) );

	if ( is_wp_error( $result ) ) {
		error_log( 'Watched option changed, but Slack notification failed - ' . esc_url( $current_site->domain . $current_site->path ) ); // @codingStandardsIgnoreLine
	}

	return $value;
}
