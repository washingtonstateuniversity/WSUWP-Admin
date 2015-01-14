<?php
/*
Plugin Name: WSU Admin
Plugin URI: http://web.wsu.edu
Description: Customized portions of the admin area of WordPress for Washington State University
Author: washingtonstateuniversity, jeremyfelt
Version: 0.3.1
*/

class WSU_Admin {
	/**
	 * Setup hooks.
	 */
	public function __construct() {
		add_filter( 'manage_pages_columns', array( $this, 'add_last_updated_column' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_last_updated_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'last_updated_column_data' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'last_updated_column_data' ), 10, 2 );
		add_filter( 'srm_max_redirects', array( $this, 'srm_max_redirects' ), 10, 1 );
		add_filter( 'document_revisions_enable_webdav', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'document_revisions_headers' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'remove_events_calendar_actions' ), 9 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_project_site' ), 10, 3 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_sites_site' ), 10, 3 );
		add_action( 'wsuwp_project_flush_rewrite_rules', array( $this, 'flush_rewrite_rules' ), 10 );
	}

	/**
	 * Add a column to posts and pages for Last Updated.
	 *
	 * @param array $columns List of columns.
	 *
	 * @return array Modified list of columns.
	 */
	public function add_last_updated_column( $columns ) {
		$columns = array_merge( $columns, array( 'wsu_last_updated' => 'Last Updated' ) );

		return $columns;
	}

	/**
	 * Display last updated data in our custom posts and page table column.
	 *
	 * @param string $column  Column being output.
	 * @param int    $post_id ID of the post row being output.
	 */
	public function last_updated_column_data( $column, $post_id ) {
		if ( 'wsu_last_updated' !== $column ) {
			return;
		}

		// Retrieve the last revision for this post, which should also be the last updated record.
		$revisions = wp_get_post_revisions( $post_id, array( 'numberposts' => 1 ) );

		foreach ( $revisions as $revision ) {
			echo get_the_author_meta('display_name', $revision->post_author );
			echo '<br>';

			// If within 24 hours, show a human readable version instead
			if ( ( time() - strtotime( $revision->post_date ) ) < DAY_IN_SECONDS ) {
				echo human_time_diff( time(), strtotime( $revision->post_date ) ) . ' ago';
			} else {
				echo date( 'Y/m/d', strtotime( $revision->post_date ) );
			}
			break;
		}
	}

	/**
	 * Filter the number of redirects supported by Safe Redirect Manager from the default of 150.
	 *
	 * @return int Number of redirects supported.
	 */
	public function srm_max_redirects() {
		return 500;
	}

	/**
	 * The Events Calendar Pro offers geolocation for venues. While we'll use that, we don't want
	 * to show a notice on every page of the admin when geopoints need to be generated.
	 */
	public function remove_events_calendar_actions() {
		if ( class_exists( 'TribeEventsGeoLoc' ) ) {
			$tribe_events = TribeEventsGeoLoc::instance();
			remove_action( 'admin_init', array( $tribe_events, 'maybe_generate_geopoints_for_all_venues' ) );
			remove_action( 'admin_init', array( $tribe_events, 'maybe_offer_generate_geopoints' ) );
		}
	}

	/**
	 * Preconfigure a Project site to reduce the overall setup experience.
	 *
	 *     - Use latest posts instead of page on front.
	 *     - Restrict to logged in users by default.
	 *     - Use the WSU Project (P2) theme rather than the Spine.
	 *     - Force HTTPS
	 *     - Configure default P2 related widgets in the sidebar.
	 *     - Flush rewrite rules.
	 *
	 * @param int    $blog_id ID of the site being created.
	 * @param int    $user_id ID of the user creating the site.
	 * @param string $domain  Domain of the site being created.
	 */
	public function preconfigure_project_site( $blog_id, $user_id, $domain ) {
		// Only apply these defaults to project sites.
		if ( ! in_array( $domain, array( 'project.wsu.edu', 'project.wsu.dev' ) ) ) {
			return;
		}

		switch_to_blog( $blog_id );

		// Show posts on the front page rather than a page.
		update_option( 'show_on_front', 'posts' );

		// Activate the WSU Project theme by default.
		update_option( 'stylesheet', 'p2-wsu' );
		update_option( 'template', 'p2-wsu' );

		// Restrict access to logged in users only.
		update_option( 'blog_public', 2 );

		// We're only prepared for SSL on production.
		if ( 'project.wsu.edu' === $domain ) {
			// Replace HTTP with HTTPS in the site and home URLs.
			$site_url = get_option( 'siteurl' );
			$site_url = str_replace( 'http://', 'https://', $site_url );
			update_option( 'siteurl', $site_url );
			$home_url = get_option( 'home' );
			$home_url = str_replace( 'http://', 'https://', $home_url );
			update_option( 'home', $home_url );
		}

		// Setup common P2 widgets.
		update_option( 'widget_mention_me', array( 2 => array( 'title' => '', 'num_to_show' => 5, 'avatar_size' => 32, 'show_also_post_followups' => false, 'show_also_comment_followups' => false ), '_multiwidget' => 1 ) );
		update_option( 'widget_p2_recent_tags', array( 2 => array( 'title' => '', 'num_to_show' => 15 ), '_multiwidget' => 1 ) );
		update_option( 'widget_p2_recent_comments', array( 2 => array( 'title' => '', 'num_to_show' => 5, 'avatar_size' => 32 ), '_multiwidget' => 1 ) );
		update_option( 'sidebars_widgets',       array ( 'wp_inactive_widgets' => array (), 'sidebar-1' => array ( 0 => 'search-2', 1 => 'mention_me-2', 2 => 'p2_recent_tags-2', 3 => 'p2_recent_comments-2', 4 => 'recent-posts-2' ), 'sidebar-2' => array (), 'sidebar-3' => array (), 'array_version' => 3 ) );

		wp_schedule_single_event( time() + 5, 'wsuwp_project_flush_rewrite_rules' );
		wp_cache_delete( 'alloptions', 'options' );
		restore_current_blog();

		refresh_blog_details( $blog_id );
	}

	/**
	 * Preconfigure a student portfolio site to reduce the overall setup experience.
	 *
	 *     - Use latest posts instead of page on front.
	 *     - Restrict to logged in users by default.
	 *     - Force HTTPS
	 *
	 * @param int    $blog_id ID of the site being created.
	 * @param int    $user_id ID of the user creating the site.
	 * @param string $domain  Domain of the site being created.
	 */
	public function preconfigure_sites_site( $blog_id, $user_id, $domain ) {
		// Only apply these defaults to sites sites. ;)
		if ( ! in_array( $domain, array( 'sites.wsu.edu', 'sites.wsu.dev' ) ) ) {
			return;
		}

		switch_to_blog( $blog_id );

		// Show posts on the front page rather than a page. Sites are primarily for
		// student portfolios and will likely have a log format.
		update_option( 'show_on_front', 'posts' );

		// Restrict access to logged in users only.
		update_option( 'blog_public', 2 );

		// We're prepared for SSL everywhere on production.
		if ( 'sites.wsu.edu' === $domain ) {
			// Replace HTTP with HTTPS in the site and home URLs.
			$site_url = get_option( 'siteurl' );
			$site_url = str_replace( 'http://', 'https://', $site_url );
			update_option( 'siteurl', $site_url );
			$home_url = get_option( 'home' );
			$home_url = str_replace( 'http://', 'https://', $home_url );
			update_option( 'home', $home_url );
		}

		wp_cache_delete( 'alloptions', 'options' );
		restore_current_blog();

		refresh_blog_details( $blog_id );
	}

	/**
	 * Flush the rewrite rules on the site.
	 */
	public function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Determine what Content-Type header should be sent with a document revisions
	 * request. If we don't filter this, text/html is used by default as WordPress
	 * sets the header before the WP Document Revisions plugin is able to.
	 *
	 * @param array $headers List of headers currently set for this request.
	 *
	 * @return array Modified list of headers.
	 */
	public function document_revisions_headers( $headers ) {
		/* @var WPDB $wpdb */
		global $wpdb, $wp;

		// Only modify headers for document revisions.
		if ( isset( $wp->query_vars['post_type'] ) && 'document' !== $wp->query_vars['post_type'] ) {
			return $headers;
		}

		// Retrieve post_content for the post matching this document request. This post_content is really
		// the ID of the attachment the document is a mask for.
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE post_type='document' AND post_name = %s", sanitize_title( $wp->query_vars['name'] ) ) );
		if ( empty( absint( $post_id ) ) ) {
			return $headers;
		}

		// Retrieve the mime type assigned to the attachment originally.
		$mime_type = $wpdb->get_var( $wpdb->prepare( "SELECT post_mime_type FROM $wpdb->posts WHERE ID = %d", $post_id ) );
		if ( empty( $mime_type ) ) {
			return $headers;
		}

		$headers['Content-Type'] = esc_attr( $mime_type ) . '; charset=UTF-8';

		return $headers;
	}
}
new WSU_Admin();